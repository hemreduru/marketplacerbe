<?php

use App\Models\OrderItem;
use App\Services\MarketplaceManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('order sync kalem komisyon oranı + indirimli tutarı yakalar (K.5)', function () {
    Queue::fake();
    [$user, $credential] = userWithTrendyol();

    // page 0 → siparişi döner, sonraki sayfalar boş (her 14-günlük chunk için)
    Http::fake(function ($request) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
        if ((int) ($q['page'] ?? 0) === 0) {
            return Http::response([
                'content' => [[
                    'orderNumber' => 'TY-K5',
                    'status' => 'Created',
                    'orderDate' => now()->getTimestampMs(),
                    'customerFirstName' => 'Ada',
                    'customerLastName' => 'Lovelace',
                    'lines' => [[
                        'productName' => 'Boya',
                        'merchantSku' => 'SKU-9',
                        'quantity' => 1,
                        'price' => 100.00,   // liste fiyatı
                        'amount' => 90.00,   // kampanya indirimli birim tutar
                        'commissionRate' => 18.5,
                    ]],
                ]],
                'totalElements' => 1,
            ]);
        }

        return Http::response(['content' => [], 'totalElements' => 1]);
    });

    app(MarketplaceManager::class)
        ->orderService($credential)
        ->syncOrders($credential->marketplace_id, $user->id, now()->year);

    $item = OrderItem::whereHas('order', fn ($q) => $q->where('order_number', 'TY-K5'))->first();

    expect($item)->not->toBeNull()
        ->and((float) $item->price)->toBe(90.00)            // indirimli tutar gelire yansıdı (eskiden 100)
        ->and((float) $item->commission_rate)->toBe(18.5);  // gerçek komisyon oranı (config %15 fallback değil)
});
