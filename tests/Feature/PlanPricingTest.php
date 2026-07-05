<?php

use App\Models\Plan;
use Database\Seeders\PlanSeeder;

test('kalıcı ücretsiz tier migrate ile oluşur', function () {
    $free = Plan::where('name', 'free')->first();

    expect($free)->not->toBeNull();
    expect((float) $free->price_monthly)->toBe(0.0)
        ->and($free->products_limit)->toBe(1)   // tek-SKU
        ->and($free->orders_limit)->toBe(30)
        ->and($free->is_active)->toBeTrue();
});

test('PlanSeeder idempotent — tekrar çalıştırınca duplike yok', function () {
    $this->seed(PlanSeeder::class);
    $this->seed(PlanSeeder::class);

    expect(Plan::count())->toBe(4)                       // free, starter, growth, pro
        ->and(Plan::where('name', 'free')->count())->toBe(1)
        ->and(Plan::where('name', 'pro')->first()->features['repricing'])->toBeTrue();
});

test('fiyat kademeleri sipariş-hacmine göre artan sırada', function () {
    $this->seed(PlanSeeder::class);

    $tiers = Plan::orderBy('sort_order')->pluck('price_monthly', 'name');

    expect((float) $tiers['free'])->toBe(0.0)
        ->and((float) $tiers['starter'])->toBeLessThan((float) $tiers['growth'])
        ->and((float) $tiers['growth'])->toBeLessThan((float) $tiers['pro']);
});

test('admin plan fiyatını güncelleyebilir', function () {
    [$user] = userWithTrendyol();
    $user->update(['is_admin' => true]);
    $plan = Plan::where('name', 'starter')->firstOrFail();

    $this->actingAs($user)
        ->put(route('admin.plans.update', $plan), [
            'display_name' => 'Başlangıç',
            'price_monthly' => 599,
            'price_yearly' => 5990,
            'marketplaces_limit' => 1,
            'orders_limit' => 100,
            'products_limit' => 500,
            'trial_days' => 3,
            'sort_order' => 1,
            'features' => ['analytics' => false],
        ])
        ->assertRedirect(route('admin.plans.index'));

    expect((float) $plan->fresh()->price_monthly)->toBe(599.0);
});
