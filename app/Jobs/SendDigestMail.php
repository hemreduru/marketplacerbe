<?php

namespace App\Jobs;

use App\Mail\DailyDigest;
use App\Mail\MonthlyDigest;
use App\Mail\WeeklyDigest;
use App\Models\FinancialDailySummary;
use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDigestMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type,
    ) {}

    public function handle(): void
    {
        $notifications = new NotificationService;
        $users = User::whereHas('subscriptions', fn ($q) => $q->where('stripe_status', 'active'))->get();

        foreach ($users as $user) {
            if (! $notifications->shouldSend($user, $this->type)) {
                continue;
            }

            // Kullanıcının tanımlı marketplace credential'larını al
            $credentialIds = $user->marketplaceCredentials()->pluck('id')->toArray();

            // Döneme göre finansal verileri hesapla
            $data = $this->aggregateFinancialData($credentialIds);

            $mailable = match ($this->type) {
                'weekly_digest' => new WeeklyDigest(
                    user: $user,
                    netProfit: $data['netProfit'],
                    revenue: $data['revenue'],
                    orderCount: $data['orderCount'],
                    margin: $data['margin'],
                    returnRate: $data['returnRate'],
                ),
                'monthly_digest' => new MonthlyDigest(
                    user: $user,
                    netProfit: $data['netProfit'],
                    revenue: $data['revenue'],
                    orderCount: $data['orderCount'],
                    margin: $data['margin'],
                    returnRate: $data['returnRate'],
                ),
                default => new DailyDigest(
                    user: $user,
                    netProfit: $data['netProfit'],
                    revenue: $data['revenue'],
                    orderCount: $data['orderCount'],
                    margin: $data['margin'],
                    returnRate: $data['returnRate'],
                ),
            };

            Mail::to($user)->queue($mailable);
        }
    }

    /**
     * Seçili dönem için kullanıcının finansal özet verilerini hesaplar.
     *
     * @param  array<int>  $credentialIds
     * @return array{netProfit: string, revenue: string, orderCount: int, margin: string, returnRate: string}
     */
    private function aggregateFinancialData(array $credentialIds): array
    {
        if (empty($credentialIds)) {
            return [
                'netProfit' => '0.00',
                'revenue' => '0.00',
                'orderCount' => 0,
                'margin' => '0.00',
                'returnRate' => '0.00',
            ];
        }

        // FinancialDailySummary üzerinden net kâr ve brüt satış toplamlarını al
        $summaryQuery = FinancialDailySummary::query()
            ->whereIn('user_marketplace_credential_id', $credentialIds);

        match ($this->type) {
            'weekly_digest' => $summaryQuery->where('date', '>=', now()->subDays(6)->toDateString()),
            'monthly_digest' => $summaryQuery->whereYear('date', now()->year)->whereMonth('date', now()->month),
            default => $summaryQuery->where('date', now()->toDateString()),
        };

        $aggregated = $summaryQuery->selectRaw('
            COALESCE(SUM(net_profit), 0) as total_net_profit,
            COALESCE(SUM(gross_sales), 0) as total_gross_sales
        ')->first();

        // Aynı dönem için sipariş sayısını al (credential bazlı filtre ile)
        $orderQuery = Order::query()
            ->whereIn('user_marketplace_credential_id', $credentialIds);

        match ($this->type) {
            'weekly_digest' => $orderQuery->where('order_date', '>=', now()->subDays(6)),
            'monthly_digest' => $orderQuery->whereYear('order_date', now()->year)->whereMonth('order_date', now()->month),
            default => $orderQuery->whereDate('order_date', now()->toDateString()),
        };

        $orderCount = $orderQuery->count();

        $netProfit = (float) ($aggregated->total_net_profit ?? 0);
        $grossSales = (float) ($aggregated->total_gross_sales ?? 0);

        // Margin: net_profit / gross_sales * 100 (sıfıra bölme korumalı)
        $margin = $grossSales > 0
            ? number_format(($netProfit / $grossSales) * 100, 2, '.', '')
            : '0.00';

        return [
            'netProfit' => number_format($netProfit, 2, '.', ''),
            'revenue' => number_format($grossSales, 2, '.', ''),
            'orderCount' => $orderCount,
            'margin' => $margin,
            'returnRate' => '0.00',
        ];
    }
}
