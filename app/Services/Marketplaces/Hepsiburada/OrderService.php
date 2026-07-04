<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Exceptions\SubscriptionLimitException;
use App\Jobs\EstimateOrderProfitJob;
use App\Models\Order;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Hepsiburada\Mapper\OrderMapper;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Hepsiburada sipariş senkronizasyonu.
 *
 * Trendyol OrderService paterni: idempotent updateOrCreate + kalemler +
 * abonelik sipariş limiti + kalem bazlı tahmini kâr job'ı.
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
        return $this->client->get('/order/api/orders', $filters);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function updateStatus(int $packageId, string $status): ServiceResult
    {
        return $this->client->put('/order/api/orders/'.$packageId, ['status' => $status]);
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncOrders(int $credentialId, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];

        $credential = UserMarketplaceCredential::with(['user', 'marketplace'])->find($credentialId);

        if ($credential === null) {
            return $stats;
        }

        $userId = $credential->user_id;
        $marketplaceId = $credential->marketplace_id;
        $limit = $credential->user?->getSubscriptionLimit('orders') ?? 100;

        $mapper = new OrderMapper;
        $page = 0;
        $size = 50;

        do {
            $result = $this->getOrders(['page' => $page, 'size' => $size]);

            if (! $result->ok) {
                Log::error('Hepsiburada sipariş sync hatası: '.($result->errorMessage ?? 'bilinmeyen'));

                break;
            }

            $content = $result->data['content'] ?? $result->data['items'] ?? [];

            if (empty($content)) {
                break;
            }

            foreach ($content as $orderData) {
                $orderNumber = (string) ($orderData['orderNumber'] ?? $orderData['orderId'] ?? '');

                try {
                    if ($orderNumber === '') {
                        $stats['failed']++;

                        continue;
                    }

                    $syncedOrderId = null;

                    DB::transaction(function () use ($orderData, $orderNumber, $mapper, $marketplaceId, $userId, $credentialId, $limit, &$stats, &$syncedOrderId) {
                        $exists = Order::where('marketplace_id', $marketplaceId)
                            ->where('user_id', $userId)
                            ->where('order_number', $orderNumber)
                            ->exists();

                        if (! $exists) {
                            $currentMonthOrdersCount = Order::where('user_id', $userId)
                                ->where('order_date', '>=', now()->startOfMonth())
                                ->count();

                            if ($limit !== -1 && $currentMonthOrdersCount >= $limit) {
                                throw new SubscriptionLimitException(
                                    __('subscription.order_limit_reached', ['limit' => $limit])
                                );
                            }
                        }

                        $order = Order::updateOrCreate(
                            [
                                'marketplace_id' => $marketplaceId,
                                'user_id' => $userId,
                                'order_number' => $orderNumber,
                            ],
                            array_merge($mapper->toOrderAttributes($orderData), [
                                'user_marketplace_credential_id' => $credentialId,
                                'order_date' => $mapper->parseDate($orderData['orderDate'] ?? $orderData['createdDate'] ?? null),
                            ]),
                        );

                        $order->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;

                        $order->items()->delete();

                        $lines = $orderData['items'] ?? $orderData['lines'] ?? [];
                        foreach ($lines as $line) {
                            $order->items()->create($mapper->toOrderItemAttributes($line));
                        }

                        $syncedOrderId = $order->id;
                    });

                    // Kalem bazlı tahmini kâr — items yeniden yazıldığı için her sync'te tazelenir
                    if ($syncedOrderId !== null) {
                        EstimateOrderProfitJob::dispatch($syncedOrderId)->onQueue('sync');
                    }
                } catch (\Exception $e) {
                    if ($e instanceof SubscriptionLimitException) {
                        throw $e;
                    }
                    Log::error('HB sipariş sync başarısız '.$orderNumber.': '.$e->getMessage());
                    $stats['failed']++;
                }
            }

            if ($onProgress) {
                $onProgress($page, null, "Orders page {$page}", $stats);
            }

            $page++;
        } while (count($content) === $size);

        return $stats;
    }
}
