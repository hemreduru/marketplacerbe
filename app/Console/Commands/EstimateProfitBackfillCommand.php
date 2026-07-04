<?php

namespace App\Console\Commands;

use App\Jobs\EstimateOrderProfitJob;
use App\Models\Order;
use Illuminate\Console\Command;

/**
 * Kâr defteri (order_item_financials) olmayan geçmiş siparişler için
 * tahmin job'larını kuyruğa atar. Chunk'lı çalışır — büyük hacimde güvenli.
 */
class EstimateProfitBackfillCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'profit:estimate-backfill
        {--chunk=200 : Chunk başına işlenecek sipariş sayısı}
        {--user= : Yalnızca bu kullanıcının siparişleri}';

    /**
     * @var string
     */
    protected $description = 'Kâr kaydı olmayan siparişler için tahmini kâr hesaplama job\'larını dispatch eder';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $dispatched = 0;

        $query = Order::query()
            ->whereHas('items', fn ($q) => $q->whereDoesntHave('financial'))
            ->when($this->option('user'), fn ($q, $userId) => $q->where('user_id', $userId));

        $query->chunkById($chunk, function ($orders) use (&$dispatched) {
            foreach ($orders as $order) {
                EstimateOrderProfitJob::dispatch($order->id)->onQueue('sync');
                $dispatched++;
            }
        });

        $this->info("Dispatched {$dispatched} estimate job(s).");

        return self::SUCCESS;
    }
}
