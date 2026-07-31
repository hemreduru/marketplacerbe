<?php

use App\Models\MasterProduct;

test('kâr simülatörü verilen fiyatla net kâr hesaplar (K.9)', function () {
    [$user] = userWithTrendyol();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id, 'cost_price' => 30, 'vat_rate' => 20, 'sku' => 'SKU-SIM', 'title' => 'Sim Urun',
    ]);

    $this->actingAs($user)
        ->get(route('reports.simulator', ['master_product_id' => $master->id, 'price' => 200]))
        ->assertOk()
        ->assertSee('Sim Urun')
        ->assertSee('166.67'); // net gelir = 200 / 1.20 (deterministik)
});

test('simülatör boş formda sonuç göstermez (K.9)', function () {
    [$user] = userWithTrendyol();

    $this->actingAs($user)
        ->get(route('reports.simulator'))
        ->assertOk()
        ->assertSee(__('reports.simulator_empty'));
});
