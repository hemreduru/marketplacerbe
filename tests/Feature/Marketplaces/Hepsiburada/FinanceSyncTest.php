<?php

use App\Models\FinancialDailySummary;
use App\Models\FinancialTransaction;
use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Hepsiburada\Client;
use App\Services\Marketplaces\Hepsiburada\FinanceService;
use Illuminate\Support\Facades\Http;

/**
 * @return array{0: UserMarketplaceCredential, 1: FinanceService}
 */
function hbFinanceSetup(): array
{
    $marketplace = Marketplace::where('slug', 'hepsiburada')->first()
        ?? Marketplace::factory()->hepsiburada()->create();

    $user = User::factory()->withPlan('growth')->create();

    $credential = UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
        'additional_credentials' => ['seller_id' => 'hb-merchant-9'],
    ]);

    $service = new FinanceService(new Client('key', 'secret', 'hb-merchant-9', true));

    return [$credential, $service];
}

test('HB settlement satırları FinancialTransaction ve günlük özet yazar', function () {
    [$credential, $service] = hbFinanceSetup();

    Http::fake([
        'https://mpop-sit.hepsiburada.com/*' => Http::response([
            'content' => [
                [
                    'transactionType' => 'Sale',
                    'transactionDate' => '2026-06-20T10:00:00+03:00',
                    'orderNumber' => 'HB-5001',
                    'credit' => 240.00,
                    'commissionAmount' => 36.00,
                ],
                [
                    'transactionType' => 'Kargo Faturası',
                    'transactionDate' => '2026-06-20T11:00:00+03:00',
                    'description' => 'Kargo Faturası',
                    'debt' => 42.00,
                ],
                [
                    'transactionType' => 'Hizmet Bedeli',
                    'transactionDate' => '2026-06-20T11:30:00+03:00',
                    'description' => 'Hizmet Bedeli',
                    'debt' => 9.60,
                ],
            ],
        ]),
    ]);

    $stats = $service->syncFinancialData($credential->id, '2026-06-19', '2026-06-21');

    expect($stats['created'])->toBe(3);

    $sale = FinancialTransaction::where('transaction_type', 'Sale')
        ->where('order_number', 'HB-5001')
        ->first();
    expect($sale)->not->toBeNull()
        ->and((float) $sale->amount)->toBe(240.00)
        ->and((float) $sale->commission)->toBe(36.00);

    $summary = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)
        ->whereDate('date', '2026-06-20')
        ->first();
    expect($summary)->not->toBeNull()
        ->and((float) $summary->gross_sales)->toBe(240.00)
        ->and((float) $summary->commission)->toBe(36.00)
        ->and((float) $summary->shipping_cost)->toBe(42.00)
        ->and((float) $summary->platform_expense)->toBe(9.60)
        // net = 240 - 36 - 42 - 9.6 = 152.4 (legacy semantik: COGS'suz)
        ->and((float) $summary->net_profit)->toBe(152.40);
});

test('HB settlement sync idempotenttir — aynı veri iki kez işlenince tek kayıt', function () {
    [$credential, $service] = hbFinanceSetup();

    $payload = [
        'content' => [
            [
                'transactionType' => 'Sale',
                'transactionDate' => '2026-06-20T10:00:00+03:00',
                'orderNumber' => 'HB-5002',
                'credit' => 100.00,
                'commissionAmount' => 15.00,
            ],
        ],
    ];

    Http::fake(['https://mpop-sit.hepsiburada.com/*' => Http::response($payload)]);

    $first = $service->syncFinancialData($credential->id, '2026-06-19', '2026-06-21');
    $second = $service->syncFinancialData($credential->id, '2026-06-19', '2026-06-21');

    expect($first['created'])->toBe(1)
        ->and($second['created'])->toBe(0)
        ->and($second['updated'])->toBe(1)
        ->and(FinancialTransaction::where('order_number', 'HB-5002')->count())->toBe(1);
});

test('classifyDeduction kargo, platform ve diğer kategorilerini ayırır', function () {
    [, $service] = hbFinanceSetup();

    expect($service->classifyDeduction('Kargo Faturası'))->toBe('shipping')
        ->and($service->classifyDeduction('Hizmet Bedeli'))->toBe('platform')
        ->and($service->classifyDeduction('İşlem Bedeli'))->toBe('platform')
        ->and($service->classifyDeduction('Ceza Bedeli'))->toBe('other');
});
