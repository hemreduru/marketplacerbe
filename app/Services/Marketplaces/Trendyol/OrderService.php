<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Exceptions\SubscriptionLimitException;
use App\Models\Order;
use App\Models\User;
use App\Support\ServiceResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trendyol sipariş senkronizasyonu — getOrders, updateStatus, common label.
 */
class OrderService
{
    public function __construct(protected Client $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return ServiceResult<array<string, mixed>>
     */
    public function getOrders(array $filters = []): ServiceResult
    {
        $path = '/integration/order/sellers/'.$this->client->getSellerId().'/orders';

        return $this->client->get($path, $filters);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function updateStatus(int $packageId, string $status): ServiceResult
    {
        $path = '/integration/oms/core/v1/shipment-packages/'.$packageId;

        return $this->client->put($path, ['status' => $status]);
    }

    /**
     * @return array<string, int>
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

        $lastOrder = Order::where('marketplace_id', $marketplaceId)
            ->where('user_id', $userId)
            ->orderBy('order_date', 'desc')
            ->first();

        if ($startYear) {
            $startDate = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
        } elseif ($lastOrder) {
            $startDate = $lastOrder->order_date->subDays(7);
        } else {
            $startDate = now()->subYears(5);
        }

        $endDate = now()->addDays(1);
        $chunkSize = 14;
        $currentDate = $startDate->copy();

        while ($currentDate->lessThan($endDate)) {
            $chunkEnd = $currentDate->copy()->addDays($chunkSize);
            if ($chunkEnd->greaterThan($endDate)) {
                $chunkEnd = $endDate->copy();
            }

            $apiStartDate = $currentDate->timestamp * 1000;
            $apiEndDate = $chunkEnd->timestamp * 1000;

            $chunkStats = ['created' => 0, 'updated' => 0, 'failed' => 0];
            $page = 0;
            $size = 50;
            $hasMore = true;

            while ($hasMore) {
                $result = $this->getOrders([
                    'startDate' => $apiStartDate,
                    'endDate' => $apiEndDate,
                    'page' => $page,
                    'size' => $size,
                    'orderByDirection' => 'ASC',
                    'orderByField' => 'PackageLastModifiedDate',
                ]);

                if (! $result->ok) {
                    Log::error("Sync Orders Failed for chunk {$currentDate->format('Y-m-d')}: {$result->errorMessage}");

                    break;
                }

                $orders = $result->data['content'] ?? [];
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
                                    throw new SubscriptionLimitException(
                                        __('subscription.order_limit_reached', ['limit' => $limit])
                                    );
                                }
                            }

                            $orderDate = isset($orderData['orderDate'])
                                ? Carbon::createFromTimestampMs($orderData['orderDate'])
                                : null;

                            $order = Order::updateOrCreate(
                                [
                                    'marketplace_id' => $marketplaceId,
                                    'user_id' => $userId,
                                    'order_number' => $orderData['orderNumber'],
                                ],
                                [
                                    'customer_first_name' => $orderData['customerFirstName']
                                        ? mb_convert_case($orderData['customerFirstName'], MB_CASE_TITLE, 'UTF-8')
                                        : null,
                                    'customer_last_name' => $orderData['customerLastName']
                                        ? mb_convert_case($orderData['customerLastName'], MB_CASE_TITLE, 'UTF-8')
                                        : null,
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

                usleep(100_000);
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

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function createCommonLabel(string $trackingNumber): ServiceResult
    {
        return $this->client->post('/integration/oms/core/v1/common-label', [
            'cargoTrackingNumber' => $trackingNumber,
        ]);
    }

    /**
     * @return ServiceResult<string>
     */
    public function getCommonLabel(string $trackingNumber): ServiceResult
    {
        $path = '/integration/oms/core/v1/common-label/'.$trackingNumber;

        return $this->client->send('get', $path);
    }
}
