<?php

use App\Models\Claim;
use App\Models\MasterProduct;

test('claim detay sayfası zenginleştirilmiş iade alanlarını gösterir', function () {
    [$user, $credential] = userWithTrendyol();
    $claim = Claim::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'order_number' => 'CLM-123',
        'return_reason' => 'DefectiveProduct',
        'refund_amount' => 149.90,
        'restock' => true,
    ]);

    $this->actingAs($user)->get(route('claims.show', $claim->id))
        ->assertOk()
        ->assertSee('CLM-123')
        ->assertSee(__('claims.return_reason'))
        ->assertSee('DefectiveProduct')
        ->assertSee('149.90 TL');
});

test('kullanıcı başka kullanıcının claim detayını göremez', function () {
    [$user] = userWithTrendyol();
    $foreignClaim = Claim::factory()->create(); // farklı credential

    $this->actingAs($user)->get(route('claims.show', $foreignClaim->id))->assertNotFound();
});

test('master-product sayfası çevirileri literal anahtar göstermez', function () {
    [$user] = userWithTrendyol();
    $product = MasterProduct::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('master-products.show', $product->id))
        ->assertOk()
        ->assertSee(__('products.marketplace_listings'))
        ->assertDontSee('products.marketplace_listings'); // ham anahtar GÖRÜNMEMELİ
});
