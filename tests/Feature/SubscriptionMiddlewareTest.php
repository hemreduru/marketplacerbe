<?php

use App\Models\Marketplace;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserMarketplaceCredential;

test('guests are redirected to login from dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('unsubscribed users are redirected to subscription page from dashboard', function () {
    $user = User::factory()->unsubscribed()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.select'));
});

test('unsubscribed users can view subscription select page', function () {
    $user = User::factory()->unsubscribed()->create();

    $this->actingAs($user)
        ->get(route('subscription.select'))
        ->assertOk()
        ->assertSee('Cirotik');
});

test('unsubscribed users can subscribe to a plan', function () {
    $user = User::factory()->unsubscribed()->create();

    $this->actingAs($user)
        ->post(route('subscription.subscribe'), ['plan' => 'growth'])
        ->assertRedirect(route('marketplace.settings'));

    expect($user->hasActiveSubscription())->toBeTrue();

    $subscription = $user->subscriptions()->latest()->first();
    expect($subscription->status)->toBe('active');
    expect($subscription->plan->name)->toBe('growth');
});

test('subscribed users with no marketplace connection are redirected to marketplace settings page', function () {
    $user = User::factory()->withPlan('growth')->create();

    $user->marketplaceCredentials()->delete();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('marketplace.settings'));
});

test('subscribed users with marketplace connection can access the dashboard', function () {
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();

    $user = User::factory()->withPlan('pro')->create();

    UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('trialing users can access the dashboard', function () {
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();

    $user = User::factory()->create();

    $plan = Plan::where('name', 'growth')->first();
    Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'trialing',
        'trial_ends_at' => now()->addDays(3),
        'current_period_start' => now(),
        'current_period_end' => now()->addDays(3),
    ]);

    UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
