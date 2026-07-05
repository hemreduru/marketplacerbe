<?php

use App\Models\FinancialDailySummary;
use App\Models\Order;

test('guests cannot view the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a user without a credential sees the connect prompt', function () {
    [$user] = userWithTrendyol();
    $user->marketplaceCredentials()->update(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.connect_now'));
});

test('a user with a credential sees KPI figures', function () {
    [$user, $credential] = userWithTrendyol();

    FinancialDailySummary::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'date' => now()->toDateString(),
        'gross_sales' => 1000,
        'net_profit' => 250,
    ]);

    Order::factory()->count(3)->create([
        'user_id' => $user->id,
        'marketplace_id' => $credential->marketplace_id,
        'status' => 'Created',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.revenue'))
        ->assertSee(__('dashboard.sales_net_trend'));
});

test('dashboard COGS dahil gerçek net kârı gösterir (true_net_profit)', function () {
    [$user, $credential] = userWithTrendyol();

    FinancialDailySummary::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'date' => now()->toDateString(),
        'gross_sales' => 1000,
        'net_profit' => 777,        // legacy (COGS düşülmemiş) — GÖSTERİLMEMELİ
        'true_net_profit' => 333,   // gerçek (COGS düşülmüş) — GÖSTERİLMELİ
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('333')
        ->assertDontSee('777');
});

test('dashboard true_net_profit yoksa legacy net_profit\'e düşer', function () {
    [$user, $credential] = userWithTrendyol();

    FinancialDailySummary::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'date' => now()->toDateString(),
        'gross_sales' => 1000,
        'net_profit' => 250,
        // true_net_profit set edilmedi → DB default 0 → fallback devrede
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('250');
});

test('dashboard TACoS kartı reklam/ciro oranını gösterir', function () {
    [$user, $credential] = userWithTrendyol();

    FinancialDailySummary::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'date' => now()->toDateString(),
        'gross_sales' => 1000,
        'ad_cost' => 125,          // TACoS = 125/1000 = %12.5
        'net_profit' => 300,
        'true_net_profit' => 300,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('dashboard.tacos'))
        ->assertSee('12.5');
});
