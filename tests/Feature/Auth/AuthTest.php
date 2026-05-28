<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('guest login sayfasını görebilir', function () {
    $this->get(route('login'))->assertOk();
});

test('guest register sayfasını görebilir', function () {
    $this->get(route('register'))->assertOk();
});

test('giriş yapmış kullanıcı login sayfasına gidince yönlendirilir', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(); // dashboard veya subscription.select (abonelik durumuna göre)
});

test('hatalı parola login\'i reddeder ve session\'da hata bırakır', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-pass')]);

    $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'wrong-pass',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('register başarılı olunca kullanıcı oluşturur ve oturum açar', function () {
    $payload = [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ];

    $this->post(route('register.post'), $payload)
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->username)->toBe('testuser');

    $this->assertAuthenticatedAs($user);
});

test('register tekrar eden email\'i reddeder', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->post(route('register.post'), [
        'name' => 'Other',
        'username' => 'other',
        'email' => 'dup@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertSessionHasErrors('email');
});

test('logout oturumu kapatır ve login\'e yönlendirir', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('parola sıfırlama bağlantısı talep edilebilir', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('parola sıfırlama bilinmeyen email için ses çıkarmadan reddeder', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'unknown@example.com'])
        ->assertSessionHasErrors('email');
});
