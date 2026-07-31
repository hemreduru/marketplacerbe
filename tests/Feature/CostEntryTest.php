<?php

use App\Models\MasterProduct;

test('cost entry sayfası yalnızca sıfır-maliyet ürünleri listeler', function () {
    [$user] = userWithTrendyol();
    MasterProduct::factory()->create(['user_id' => $user->id, 'cost_price' => 0, 'title' => 'Sifir Maliyet Urun', 'sku' => 'SKU-ZERO']);
    MasterProduct::factory()->create(['user_id' => $user->id, 'cost_price' => 30, 'title' => 'Dolu Maliyet Urun']);

    $this->actingAs($user)->get(route('cost-entry'))
        ->assertOk()
        ->assertSee('SKU-ZERO')
        ->assertDontSee('Dolu Maliyet Urun');
});

test('bulk cost update maliyeti yazar (K.3)', function () {
    [$user] = userWithTrendyol();
    $product = MasterProduct::factory()->create(['user_id' => $user->id, 'cost_price' => 0]);

    $this->actingAs($user)
        ->post(route('cost-entry.update'), ['costs' => [$product->id => '45.50']])
        ->assertRedirect(route('cost-entry'));

    expect((float) $product->fresh()->cost_price)->toBe(45.50);
});

test('kullanıcı başka kullanıcının ürün maliyetini güncelleyemez', function () {
    [$user] = userWithTrendyol();
    [$other] = userWithTrendyol();
    $product = MasterProduct::factory()->create(['user_id' => $other->id, 'cost_price' => 0]);

    $this->actingAs($user)
        ->post(route('cost-entry.update'), ['costs' => [$product->id => '99']]);

    expect((float) $product->fresh()->cost_price)->toBe(0.0);
});
