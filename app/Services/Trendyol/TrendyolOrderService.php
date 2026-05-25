<?php

namespace App\Services\Trendyol;

use App\Exceptions\SubscriptionLimitException;
use App\Models\Order;
use App\Models\User;
use App\Services\Contracts\OrderServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolOrderService implements OrderServiceContract
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $apiSecret;

    protected string $sellerId;

    public function __construct(string $apiKey, string $apiSecret, string $sellerId, bool $isStage = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->sellerId = $sellerId;
        $this->baseUrl = $isStage ? 'https://stageapigw.trendyol.com' : 'https://apigw.trendyol.com';
    }

    /**
     * Get shipment packages (orders).
     *
     * @param  array  $filters  (status, startDate, endDate, page, size, etc.)
     */
    public function getOrders(array $filters = []): array
    {
        $url = sprintf('%s/integration/order/sellers/%s/orders', $this->baseUrl, $this->sellerId);

        // Add sellerId to query params as required by some endpoints, though usually auth header is enough
        // $filters['supplierId'] = $this->sellerId;

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->get($url, $filters);

        if ($response->failed()) {
            Log::error('Trendyol Order API Error (getOrders): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Update package status (Picking, Invoiced, Shipped, etc.)
     * Note: Endpoint varies by status type, simplified here for 'Picking'/'Invoiced'.
     */
    public function updateStatus(int $packageId, string $status): array
    {
        // For 'Picking' and 'Invoiced', we use updatePackage.
        // For 'Shipped', we use updateTrackingNumber usually, but let's assume standard status update for now.
        // This is a simplified implementation.

        $url = sprintf('%s/integration/oms/core/v1/shipment-packages/%s', $this->baseUrl, $packageId);

        $body = [
            'status' => $status,
        ];

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->put($url, $body);

        if ($response->failed()) {
            Log::error('Trendyol Order API Error (updateStatus): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Sync orders to database with chunking.
     *
     * @return array stats
     */
    public function syncOrders(int $marketplaceId, int $userId, ?int $startYear = null, ?callable $onProgress = null): array
    {
        $totalStats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        $user = User::find($userId);
        $limit = $user?->getSubscriptionLimit('orders') ?? 100;
        $currentMonthOrdersCount = Order::where('user_id', $userId)
            ->where('order_date', '>=', now()->startOfMonth())
            ->count();

        if ($limit !== -1 && $currentMonthOrdersCount >= $limit) {
            throw new SubscriptionLimitException(__('subscription.order_limit_reached', ['limit' => $limit]));
        }

        // Smart Sync Logic
        $lastOrder = Order::where('marketplace_id', $marketplaceId)
            ->where('user_id', $userId)
            ->orderBy('order_date', 'desc')
            ->first();

        if ($startYear) {
            // Explicit start year
            $startDate = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
        } elseif ($lastOrder) {
            // Fetch from 7 days before last order
            $startDate = $lastOrder->order_date->subDays(7);
        } else {
            // Default: Fetch last 5 years
            $startDate = now()->subYears(5);
        }

        $endDate = now()->addDays(1);
        $chunkSize = 14; // 14 days per chunk

        $currentDate = $startDate->copy();

        while ($currentDate->lessThan($endDate)) {
            $chunkEnd = $currentDate->copy()->addDays($chunkSize);
            if ($chunkEnd->greaterThan($endDate)) {
                $chunkEnd = $endDate->copy();
            }

            // Convert to timestamps (ms) for API
            $apiStartDate = $currentDate->timestamp * 1000;
            $apiEndDate = $chunkEnd->timestamp * 1000;

            $chunkStats = ['created' => 0, 'updated' => 0, 'failed' => 0];
            $page = 0;
            $size = 50;
            $hasMore = true;

            while ($hasMore) {
                $response = $this->getOrders([
                    'startDate' => $apiStartDate,
                    'endDate' => $apiEndDate,
                    'page' => $page,
                    'size' => $size,
                    'orderByDirection' => 'ASC',
                    'orderByField' => 'PackageLastModifiedDate',
                ]);

                if (isset($response['error'])) {
                    Log::error("Sync Orders Failed for chunk {$currentDate->format('Y-m-d')}: ".$response['message']);
                    break;
                }

                $orders = $response['content'] ?? [];
                if (empty($orders)) {
                    $hasMore = false;
                    break;
                }

                foreach ($orders as $orderData) {
                    try {
                        DB::transaction(function () use ($orderData, $marketplaceId, $userId, $limit, &$chunkStats, &$totalStats) {
                            $exists = Order::where('marketplace_id', $marketplaceId)
                                ->where('user_id', $userId)
                                ->where('order_number', $orderData['orderNumber'])
                                ->exists();

                            if (! $exists) {
                                $currentMonthOrdersCount = Order::where('user_id', $userId)
                                    ->where('order_date', '>=', now()->startOfMonth())
                                    ->count();

                                if ($currentMonthOrdersCount >= $limit) {
                                    throw new SubscriptionLimitException(__('subscription.order_limit_reached', ['limit' => $limit]));
                                }
                            }

                            $orderDate = isset($orderData['orderDate']) ? Carbon::createFromTimestampMs($orderData['orderDate']) : null;

                            $order = Order::updateOrCreate(
                                [
                                    'marketplace_id' => $marketplaceId,
                                    'user_id' => $userId,
                                    'order_number' => $orderData['orderNumber'],
                                ],
                                [
                                    'customer_first_name' => $orderData['customerFirstName'] ? mb_convert_case($orderData['customerFirstName'], MB_CASE_TITLE, 'UTF-8') : null,
                                    'customer_last_name' => $orderData['customerLastName'] ? mb_convert_case($orderData['customerLastName'], MB_CASE_TITLE, 'UTF-8') : null,
                                    'customer_email' => $orderData['customerEmail'] ?? null,
                                    'total_amount' => $orderData['totalPrice'] ?? 0,
                                    'currency_code' => $orderData['currencyCode'] ?? 'TRY',
                                    'status' => $orderData['status'] ?? 'Created',
                                    'shipment_package_status' => $orderData['shipmentPackageStatus'] ?? null,
                                    'order_date' => $orderDate,
                                    'cargo_tracking_number' => $orderData['cargoTrackingNumber'] ?? null,
                                    'cargo_provider_name' => $orderData['cargoProviderName'] ?? null,
                                    'raw_data' => $orderData,
                                ]
                            );

                            if ($order->wasRecentlyCreated) {
                                $chunkStats['created']++;
                                $totalStats['created']++;
                            } else {
                                $chunkStats['updated']++;
                                $totalStats['updated']++;
                            }

                            $order->items()->delete();

                            if (isset($orderData['lines'])) {
                                foreach ($orderData['lines'] as $line) {
                                    $order->items()->create([
                                        'product_name' => $line['productName'] ?? 'Unknown',
                                        'merchant_sku' => $line['merchantSku'] ?? null,
                                        'barcode' => $line['barcode'] ?? null,
                                        'quantity' => $line['quantity'] ?? 1,
                                        'price' => $line['price'] ?? 0,
                                        'currency_code' => $line['currencyCode'] ?? 'TRY',
                                        'line_item_status' => $line['orderLineItemStatusName'] ?? null,
                                    ]);
                                }
                            }
                        });
                    } catch (\Exception $e) {
                        if ($e instanceof SubscriptionLimitException) {
                            throw $e;
                        }
                        Log::error('Failed to sync order '.($orderData['orderNumber'] ?? '?').': '.$e->getMessage());
                        $chunkStats['failed']++;
                        $totalStats['failed']++;
                    }
                }

                if (count($orders) < $size) {
                    $hasMore = false;
                } else {
                    $page++;
                }

                usleep(100000); // 0.1s
            }

            if ($onProgress) {
                $onProgress(
                    'Syncing period: '.$currentDate->format('Y-m-d').' to '.$chunkEnd->format('Y-m-d'),
                    $chunkStats
                );
            }

            $currentDate->addDays($chunkSize);
        }

        return $totalStats;
    }

    public function createCommonLabel(string $trackingNumber): array
    {
        $url = sprintf('%s/integration/oms/core/v1/common-label', $this->baseUrl);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post($url, [
                'cargoTrackingNumber' => $trackingNumber,
            ]);

        if ($response->failed()) {
            Log::error('Trendyol Order API Error (createCommonLabel): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Get Common Label (Retrieve content)
     *
     * @return mixed (PDF content or array)
     */
    public function getCommonLabel(string $trackingNumber)
    {
        $url = sprintf('%s/integration/oms/core/v1/common-label/%s', $this->baseUrl, $trackingNumber);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->get($url);

        if ($response->failed()) {
            Log::error('Trendyol Order API Error (getCommonLabel): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->body(); // Returns raw content (e.g. ZPL or PDF stream)
    }
}
