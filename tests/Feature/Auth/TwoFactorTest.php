<?php

use App\Models\User;
use App\Services\Auth\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TwoFactorAuthService::class);
});

test('2FA aktif değilse login başarı ile dashboard\'a yönlendirir', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect('dashboard');

    $this->assertAuthenticatedAs($user);
});

test('2FA aktif kullanıcı login sonrası challenge sayfasına yönlendirilir', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret123'),
        'two_factor_secret' => $this->service->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $this->service->generateRecoveryCodes(),
    ]);

    $response = $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
    expect(session('two_factor.user_id'))->toBe($user->id);
});

test('challenge sayfası geçerli TOTP ile kullanıcıyı oturuma alır', function () {
    $secret = $this->service->generateSecret();
    $user = User::factory()->create([
        'password' => bcrypt('secret123'),
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $this->service->generateRecoveryCodes(),
    ]);

    $google2fa = app(Google2FA::class);
    $otp = $google2fa->getCurrentOtp($secret);

    $this->withSession(['two_factor.user_id' => $user->id])
        ->post(route('two-factor.verify'), ['code' => $otp])
        ->assertRedirect('dashboard');

    $this->assertAuthenticatedAs($user);
});

test('hatalı OTP challenge sayfasında reddedilir', function () {
    $user = User::factory()->create([
        'two_factor_secret' => $this->service->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $this->service->generateRecoveryCodes(),
    ]);

    $this->withSession(['two_factor.user_id' => $user->id])
        ->post(route('two-factor.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('recovery code tek kullanımlık ve consume edilir', function () {
    $codes = $this->service->generateRecoveryCodes();
    $user = User::factory()->create([
        'two_factor_secret' => $this->service->generateSecret(),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $codes,
    ]);

    $first = $codes[0];

    $this->withSession(['two_factor.user_id' => $user->id])
        ->post(route('two-factor.verify'), ['code' => $first])
        ->assertRedirect('dashboard');

    $user->refresh();
    expect($user->two_factor_recovery_codes)->not->toContain($first)
        ->and(count($user->two_factor_recovery_codes))->toBe(9);
});

test('challenge oturumu yoksa login\'e yönlendirir', function () {
    $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
});

test('TwoFactorAuthService::generateRecoveryCodes 10 benzersiz kod döner', function () {
    $codes = $this->service->generateRecoveryCodes();

    expect($codes)->toHaveCount(10)
        ->and(array_unique($codes))->toHaveCount(10);
});
