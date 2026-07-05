<?php

use App\Models\Product;
use App\Models\User;

test('onboarding sayfası misafiri login\'e yönlendirir', function () {
    $this->get(route('onboarding'))->assertRedirect(route('login'));
});

test('yeni kullanıcıda hesap adımı tamam, diğerleri beklemede', function () {
    $user = User::factory()->unsubscribed()->create();

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk()
        ->assertSee(__('onboarding.step_subscription_title'))
        ->assertSee(__('onboarding.step_subscription_cta'));

    expect($user->fresh()->onboarding_completed_steps)->toBe(['account']);
});

test('onboarding abonesiz kullanıcıda EnsureSubscribed tarafından engellenmez', function () {
    $user = User::factory()->unsubscribed()->create();

    // Abonesiz kullanıcı normalde subscription.select'e düşer; onboarding muaf.
    $this->actingAs($user)->get(route('onboarding'))->assertOk();
});

test('tüm adımlar tamamlanınca onboarding complete gösterir', function () {
    [$user, $credential] = userWithTrendyol();
    Product::factory()->create(['user_marketplace_credential_id' => $credential->id]);

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk()
        ->assertSee(__('onboarding.complete_title'));

    expect($user->fresh()->isOnboardingComplete())->toBeTrue()
        ->and($user->fresh()->onboarding_completed_steps)->toContain('first_sync');
});
