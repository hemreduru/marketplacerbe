<?php

namespace App\Services\Demo;

use App\Models\FinancialDailySummary;
use App\Models\Marketplace;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Support\Enums\ProfitSource;
use App\Support\Enums\ReconciliationStatus;
use Illuminate\Support\Facades\DB;

/**
 * Demo/sandbox veri üretimi — satıcı gerçek API anahtarı girmeden ürünü
 * denesin (Plan WS-3). Demo credential (is_demo) + ürün + 14 günlük finansal
 * özet + sipariş/kalem kâr defteri seed'ler; dashboard + SKU kâr raporu dolar.
 *
 * NOT: bu servis DEMO veri üretir; App\Services\Calculations/Finance saflık
 * kurallarına tabi değildir (round() ile yaklaşık gösterim değerleri).
 */
class DemoDataService
{
    /** @var list<array{0: string, 1: string, 2: float, 3: float, 4: int}> */
    private const PRODUCTS = [
        ['Kablosuz Bluetooth Kulaklık', 'DEMO-KLK-001', 449.90, 210.00, 62],
        ['Akıllı Saat Sport', 'DEMO-SAT-002', 899.00, 480.00, 28],
        ['Powerbank 20000mAh', 'DEMO-PWB-003', 329.50, 145.00, 90],
        ['USB-C Hızlı Şarj Kablosu', 'DEMO-KBL-004', 89.90, 28.00, 240],
    ];

    public function hasDemo(User $user): bool
    {
        return $user->marketplaceCredentials()->where('is_demo', true)->exists();
    }

    /**
     * Kullanıcının hesabını demo veriyle doldurur (idempotent).
     */
    public function populate(User $user): UserMarketplaceCredential
    {
        /** @var UserMarketplaceCredential $demoCredential */
        $demoCredential = DB::transaction(function () use ($user) {
            $existing = $user->marketplaceCredentials()->where('is_demo', true)->first();
            if ($existing !== null) {
                return $existing;
            }

            $marketplace = Marketplace::where('slug', 'trendyol')->firstOrFail();

            $credential = UserMarketplaceCredential::create([
                'user_id' => $user->id,
                'marketplace_id' => $marketplace->id,
                'api_key' => 'DEMO',
                'api_secret' => 'DEMO',
                'additional_credentials' => ['seller_id' => 'DEMO'],
                'is_active' => true,
                'is_demo' => true,
            ]);

            $masters = [];
            foreach (self::PRODUCTS as $i => [$title, $sku, $price, $cost, $stock]) {
                $masters[] = MasterProduct::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'sku' => $sku,
                    'barcode' => '868000000000'.$i,
                    'cost_price' => $cost,
                    'vat_rate' => 20,
                    'current_stock' => $stock,
                    'current_price' => $price,
                    'weight_g' => 300,
                    'desi' => 2,
                ]);
            }

            for ($d = 13; $d >= 0; $d--) {
                $date = now()->subDays($d);
                $gross = 800 + ($d % 5) * 350;
                $commission = round($gross * 0.15, 2);
                $shipping = round($gross * 0.06, 2);
                $cogs = round($gross * 0.42, 4);
                $ad = round($gross * 0.05, 4);
                $stopaj = round($gross / 1.2 * 0.01, 4);

                FinancialDailySummary::create([
                    'user_marketplace_credential_id' => $credential->id,
                    'date' => $date->toDateString(),
                    'gross_sales' => $gross,
                    'commission' => $commission,
                    'shipping_cost' => $shipping,
                    'platform_expense' => 0,
                    'other_expense' => 0,
                    'cogs' => $cogs,
                    'stopaj' => $stopaj,
                    'ad_cost' => $ad,
                    'return_cost' => 0,
                    'net_profit' => round($gross - $commission - $shipping, 2),
                    'true_net_profit' => round($gross - $commission - $shipping - $cogs - $ad - $stopaj, 4),
                    'order_count' => 3 + ($d % 4),
                    'item_count' => 5 + ($d % 6),
                ]);
            }

            foreach (range(0, 7) as $n) {
                $master = $masters[$n % count($masters)];
                $date = now()->subDays($n);
                $price = (float) $master->current_price;

                /** @var Order $order */
                $order = Order::create([
                    'user_id' => $user->id,
                    'marketplace_id' => $marketplace->id,
                    'user_marketplace_credential_id' => $credential->id,
                    'order_number' => 'DEMO-'.(1000 + $n),
                    'customer_first_name' => 'Demo',
                    'customer_last_name' => 'Müşteri',
                    'customer_email' => 'demo@example.com',
                    'total_amount' => $price,
                    'currency_code' => 'TRY',
                    'status' => 'Delivered',
                    'shipment_package_status' => 'Delivered',
                    'order_date' => $date,
                    'raw_data' => [],
                ]);

                /** @var OrderItem $item */
                $item = OrderItem::create([
                    'order_id' => $order->getKey(),
                    'master_product_id' => $master->id,
                    'product_name' => $master->title,
                    'merchant_sku' => $master->sku,
                    'barcode' => $master->barcode,
                    'quantity' => 1,
                    'price' => $price,
                    'currency_code' => 'TRY',
                    'line_item_status' => 'Delivered',
                ]);

                $netRevenue = round($price / 1.2, 4);
                $commission = round($price * 0.15, 4);
                $profit = round($netRevenue - (float) $master->cost_price - $commission - 15, 4);

                OrderItemFinancial::create([
                    'order_item_id' => $item->getKey(),
                    'order_id' => $order->getKey(),
                    'user_marketplace_credential_id' => $credential->id,
                    'master_product_id' => $master->id,
                    'marketplace_code' => 'trendyol',
                    'order_date' => $date->toDateString(),
                    'net_revenue' => $netRevenue,
                    'cogs' => (float) $master->cost_price,
                    'commission' => $commission,
                    'service_fee' => 10,
                    'shipping' => 30,
                    'stopaj' => round($netRevenue * 0.01, 4),
                    'ad_cost' => 5,
                    'return_cost' => 0,
                    'packaging' => 2,
                    'net_profit' => $profit,
                    'margin' => $netRevenue > 0 ? round($profit / $netRevenue * 100, 4) : 0,
                    'source' => ProfitSource::Estimate,
                    'reconciliation_status' => ReconciliationStatus::Estimated,
                    'estimated_net_profit' => $profit,
                    'estimated_at' => now(),
                ]);
            }

            return $credential;
        });

        return $demoCredential;
    }

    /**
     * Demo veriyi temizler (gerçek pazaryeri bağlanınca).
     */
    public function clear(User $user): void
    {
        DB::transaction(function () use ($user) {
            $credentials = $user->marketplaceCredentials()->where('is_demo', true)->get();

            foreach ($credentials as $credential) {
                $orderIds = Order::where('user_marketplace_credential_id', $credential->getKey())->pluck('id');
                OrderItemFinancial::whereIn('order_id', $orderIds)->delete();
                OrderItem::whereIn('order_id', $orderIds)->delete();
                FinancialDailySummary::where('user_marketplace_credential_id', $credential->getKey())->delete();
                Order::whereIn('id', $orderIds)->delete();
                $credential->delete();
            }

            MasterProduct::where('user_id', $user->id)->where('sku', 'like', 'DEMO-%')->delete();
        });
    }
}
