<?php

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\IyzicoService;
use Illuminate\Support\Facades\Http;

test('initializeThreeDSPayment debug modda otomatik success döner', function () {
    $result = app(IyzicoService::class)->initializeThreeDSPayment(['price' => 499]);

    expect($result->ok)->toBeTrue()
        ->and($result->data['status'])->toBe('success');
});

test('initializeThreeDSPayment gerçek modda 3DS html içeriği döner', function () {
    config(['services.iyzico.debug' => false]);
    Http::fake([
        '*/payment/3dsecure/initialize' => Http::response([
            'status' => 'success',
            'threeDSHtmlContent' => base64_encode('<form id="bank-3ds"></form>'),
            'paymentId' => '12345',
        ]),
    ]);

    $result = app(IyzicoService::class)->initializeThreeDSPayment(['price' => 499]);

    expect($result->ok)->toBeTrue()
        ->and($result->data['threeDSHtmlContent'])->toContain('<form')
        ->and($result->data['paymentId'])->toBe('12345');
});

test('initializeThreeDSPayment API hatasında ServiceResult fail döner', function () {
    config(['services.iyzico.debug' => false]);
    Http::fake([
        '*/payment/3dsecure/initialize' => Http::response([
            'status' => 'failure',
            'errorCode' => '5001',
            'errorMessage' => 'Kart reddedildi',
        ]),
    ]);

    $result = app(IyzicoService::class)->initializeThreeDSPayment(['price' => 499]);

    expect($result->ok)->toBeFalse()
        ->and($result->errorCode)->toBe('iyzico_5001')
        ->and($result->errorMessage)->toBe('Kart reddedildi');
});

test('subscribe ödeme kaydı oluşturur ve aboneliği aktive eder (debug)', function () {
    $user = User::factory()->unsubscribed()->create();

    $this->actingAs($user)
        ->post(route('subscription.subscribe'), ['plan' => 'growth', 'billing_period' => 'monthly'])
        ->assertRedirect(route('marketplace.settings'));

    $this->assertDatabaseHas('subscription_payments', [
        'user_id' => $user->id,
        'status' => 'success',
        'billing_period' => 'monthly',
    ]);
    expect($user->fresh()->hasActiveSubscription())->toBeTrue();
});

test('payment callback bekleyen ödemeyi doğrular ve aktive eder (debug)', function () {
    $user = User::factory()->unsubscribed()->create();
    $plan = Plan::where('name', 'growth')->firstOrFail();

    $payment = SubscriptionPayment::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
        'conversation_id' => 'conv-abc-123',
    ]);

    $this->post(route('subscription.payment.callback'), [
        'conversationId' => 'conv-abc-123',
        'paymentId' => 'pay-999',
    ])->assertRedirect(route('marketplace.settings'));

    expect($payment->fresh()->status)->toBe('success')
        ->and($user->fresh()->hasActiveSubscription())->toBeTrue();
});

test('subscribe ödeme başlatma başarısızsa payment failed kaydeder', function () {
    config(['services.iyzico.debug' => false]);
    Http::fake([
        '*/payment/3dsecure/initialize' => Http::response([
            'status' => 'failure',
            'errorMessage' => 'Kart hatası',
        ]),
    ]);

    $user = User::factory()->unsubscribed()->create();

    $this->actingAs($user)
        ->post(route('subscription.subscribe'), ['plan' => 'growth'])
        ->assertRedirect();

    $this->assertDatabaseHas('subscription_payments', [
        'user_id' => $user->id,
        'status' => 'failed',
    ]);
    expect($user->fresh()->hasActiveSubscription())->toBeFalse();
});
