<?php

use App\Jobs\EstimateOrderProfitJob;
use App\Models\Claim;
use App\Models\Marketplace;
use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\Question;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Hepsiburada\ClaimService;
use App\Services\Marketplaces\Hepsiburada\Client;
use App\Services\Marketplaces\Hepsiburada\OrderService;
use App\Services\Marketplaces\Hepsiburada\ProductService;
use App\Services\Marketplaces\Hepsiburada\QuestionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * @return array{0: User, 1: UserMarketplaceCredential, 2: Client}
 */
function hepsiburadaSetup(): array
{
    $marketplace = Marketplace::where('slug', 'hepsiburada')->first()
        ?? Marketplace::factory()->hepsiburada()->create();

    $user = User::factory()->withPlan('growth')->create();

    $credential = UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
        'additional_credentials' => ['seller_id' => 'hb-seller-1'],
    ]);

    $client = new Client('key', 'secret', 'hb-seller-1', true);

    return [$user, $credential, $client];
}

test('HB ürün sync Product + MasterProduct + MarketplaceListing yazar ve idempotenttir', function () {
    [, $credential, $client] = hepsiburadaSetup();

    $productPayload = [
        'content' => [
            [
                'hbSku' => 'HBV000ABC',
                'merchantSku' => 'SKU-1',
                'barcode' => '8680000000001',
                'productName' => 'Test Ürün',
                'brand' => 'Marka',
                'price' => ['amount' => 149.90, 'currency' => 'TRY'],
                'availableStock' => 12,
                'isSalable' => true,
            ],
        ],
    ];

    // Sayfa boyutundan az içerik → her sync tek istekte durur (count < size)
    Http::fake([
        'https://mpop-sit.hepsiburada.com/*' => Http::sequence()
            ->push($productPayload)
            ->push($productPayload),
    ]);

    $service = new ProductService($client);

    $stats = $service->syncProducts($credential->id);

    expect($stats['created'])->toBe(1);

    $product = Product::where('user_marketplace_credential_id', $credential->id)->first();
    expect($product)->not->toBeNull()
        ->and($product->remote_id)->toBe('HBV000ABC')
        ->and($product->sku)->toBe('SKU-1')
        ->and((float) $product->price)->toBe(149.90)
        ->and($product->stock)->toBe(12)
        ->and($product->status)->toBe('active');

    $listing = MarketplaceListing::where('user_marketplace_credential_id', $credential->id)->first();
    expect($listing)->not->toBeNull()
        ->and($listing->remote_product_id)->toBe('HBV000ABC')
        ->and($listing->master_product_id)->not->toBeNull();

    $master = MasterProduct::find($listing->master_product_id);
    expect($master->barcode)->toBe('8680000000001');

    // İkinci sync — idempotent
    $stats2 = $service->syncProducts($credential->id);

    expect($stats2['updated'])->toBe(1)
        ->and(Product::count())->toBe(1)
        ->and(MarketplaceListing::count())->toBe(1)
        ->and(MasterProduct::count())->toBe(1);
});

test('HB ürün barcode ile mevcut Trendyol master ürününe bağlanır', function () {
    [$user, $credential, $client] = hepsiburadaSetup();

    $existingMaster = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'barcode' => '8680000000002',
    ]);

    Http::fake([
        'https://mpop-sit.hepsiburada.com/*' => Http::sequence()
            ->push(['content' => [[
                'hbSku' => 'HBV000DEF',
                'merchantSku' => 'SKU-2',
                'barcode' => '8680000000002',
                'productName' => 'Cross Ürün',
                'price' => 99.0,
                'availableStock' => 3,
            ]]])
            ->push(['content' => []]),
    ]);

    (new ProductService($client))->syncProducts($credential->id);

    $listing = MarketplaceListing::where('user_marketplace_credential_id', $credential->id)->first();

    expect($listing->master_product_id)->toBe($existingMaster->id)
        ->and(MasterProduct::count())->toBe(1);
});

test('HB sipariş sync Order + OrderItem yazar, credential bağlar ve kâr job dispatch eder', function () {
    Queue::fake();

    [$user, $credential, $client] = hepsiburadaSetup();

    $orderPayload = [
        'content' => [
            [
                'orderNumber' => 'HB-1001',
                'orderDate' => '2026-06-20T10:30:00+03:00',
                'customerName' => 'ali veli',
                'totalPrice' => ['amount' => 250.0, 'currency' => 'TRY'],
                'status' => 'Open',
                'items' => [
                    [
                        'lineItemId' => 'L1',
                        'productName' => 'Ürün A',
                        'merchantSku' => 'SKU-1',
                        'quantity' => 2,
                        'price' => ['amount' => 125.0],
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        'https://mpop-sit.hepsiburada.com/*' => Http::sequence()
            ->push($orderPayload)
            ->push(['content' => []]),
    ]);

    $stats = (new OrderService($client))->syncOrders($credential->id);

    expect($stats['created'])->toBe(1);

    $order = Order::where('order_number', 'HB-1001')->first();
    expect($order)->not->toBeNull()
        ->and($order->user_id)->toBe($user->id)
        ->and($order->user_marketplace_credential_id)->toBe($credential->id)
        ->and($order->customer_first_name)->toBe('Ali')
        ->and($order->order_date->toDateString())->toBe('2026-06-20')
        ->and((float) $order->total_amount)->toBe(250.0);

    expect($order->items)->toHaveCount(1)
        ->and($order->items->first()->merchant_sku)->toBe('SKU-1')
        ->and((float) $order->items->first()->price)->toBe(125.0);

    Queue::assertPushed(EstimateOrderProfitJob::class, fn ($job) => $job->orderId === $order->id);
});

test('HB sipariş sync idempotenttir — aynı orderNumber iki koşuda tek sipariş', function () {
    Queue::fake();

    [, $credential, $client] = hepsiburadaSetup();

    $orderPayload = [
        'content' => [
            [
                'orderNumber' => 'HB-2002',
                'orderDate' => '2026-06-21T09:00:00+03:00',
                'customerName' => 'ayşe yılmaz',
                'totalPrice' => 100.0,
                'items' => [
                    ['productName' => 'Ürün B', 'merchantSku' => 'SKU-9', 'quantity' => 1, 'price' => 100.0],
                ],
            ],
        ],
    ];

    // Sayfa boyutundan az içerik → her sync tek istekte durur (count < size)
    Http::fake([
        'https://mpop-sit.hepsiburada.com/*' => Http::sequence()
            ->push($orderPayload)
            ->push($orderPayload),
    ]);

    $service = new OrderService($client);
    $service->syncOrders($credential->id);
    $service->syncOrders($credential->id);

    expect(Order::where('order_number', 'HB-2002')->count())->toBe(1)
        ->and(Order::where('order_number', 'HB-2002')->first()->items()->count())->toBe(1);
});

test('HB soru sync Question yazar', function () {
    [, $credential, $client] = hepsiburadaSetup();

    Http::fake([
        'https://mpop-sit.hepsiburada.com/*' => Http::sequence()
            ->push(['content' => [[
                'id' => 555,
                'content' => 'Bu ürün orijinal mi?',
                'status' => 'WAITING_FOR_ANSWER',
                'product' => ['name' => 'Ürün A'],
                'createdAt' => '2026-06-22T12:00:00+03:00',
            ]]])
            ->push(['content' => []]),
    ]);

    $stats = (new QuestionService($client))->syncQuestions($credential->id);

    expect($stats['created'])->toBe(1);

    $question = Question::where('user_marketplace_credential_id', $credential->id)->first();
    expect($question->remote_id)->toBe('555')
        ->and($question->question_text)->toBe('Bu ürün orijinal mi?');
});

test('HB iade sync Claim yazar', function () {
    [, $credential, $client] = hepsiburadaSetup();

    Http::fake([
        'https://mpop-sit.hepsiburada.com/*' => Http::sequence()
            ->push(['content' => [[
                'id' => 'CLM-1',
                'orderNumber' => 'HB-1001',
                'status' => 'WaitingApproval',
                'customerName' => 'Ali Veli',
                'items' => [['id' => 1]],
                'createdAt' => '2026-06-23T15:00:00+03:00',
            ]]])
            ->push(['content' => []]),
    ]);

    $stats = (new ClaimService($client))->syncClaims($credential->id);

    expect($stats['created'])->toBe(1);

    $claim = Claim::where('user_marketplace_credential_id', $credential->id)->first();
    expect($claim->remote_id)->toBe('CLM-1')
        ->and($claim->order_number)->toBe('HB-1001')
        ->and($claim->item_count)->toBe(1);
});
