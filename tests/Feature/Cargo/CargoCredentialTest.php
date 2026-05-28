<?php

use App\Models\CargoCredential;
use App\Models\CargoProvider;
use App\Models\User;
use App\Support\Enums\CargoLabelFormat;
use Illuminate\Support\Facades\DB;

test('cargo provider model casts attributes correctly', function () {
    $provider = CargoProvider::factory()->yurtici()->create([
        'label_formats' => ['a4_pdf', 'zpl'],
        'config' => ['wsdl_url' => 'http://test.com?wsdl'],
    ]);

    expect($provider->has_webhook)->toBeFalse()
        ->and($provider->is_active)->toBeTrue()
        ->and($provider->label_formats)->toBeArray()
        ->and($provider->label_formats)->toContain('a4_pdf')
        ->and($provider->config)->toBeArray()
        ->and($provider->supportsLabelFormat(CargoLabelFormat::A4Pdf))->toBeTrue()
        ->and($provider->supportsLabelFormat(CargoLabelFormat::Png))->toBeFalse();
});

test('cargo credential encrypts username and password', function () {
    $user = User::factory()->create();
    $provider = CargoProvider::factory()->yurtici()->create();

    $credential = CargoCredential::factory()->forProvider($provider)->for($user)->create([
        'username' => 'my_user',
        'password' => 'my_secret',
        'is_active' => true,
    ]);

    expect($credential->username)->toBe('my_user')
        ->and($credential->password)->toBe('my_secret');

    $raw = DB::table('cargo_credentials')->where('id', $credential->id)->first();

    expect($raw->username)->not->toBe('my_user')
        ->and($raw->password)->not->toBe('my_secret');
});

test('cargo credential hidden from serialization', function () {
    $user = User::factory()->create();
    $provider = CargoProvider::factory()->yurtici()->create();
    $credential = CargoCredential::factory()->forProvider($provider)->for($user)->create();

    $arr = $credential->toArray();

    expect($arr)->not->toHaveKey('username')
        ->and($arr)->not->toHaveKey('password');
});

test('cargo credential active scope works', function () {
    $user = User::factory()->create();
    $provider = CargoProvider::factory()->yurtici()->create();

    CargoCredential::factory()->forProvider($provider)->for($user)->create(['is_active' => true]);

    $provider2 = CargoProvider::factory()->aras()->create();
    CargoCredential::factory()->forProvider($provider2)->for($user)->create(['is_active' => false]);

    expect(CargoCredential::active()->count())->toBe(1);
});

test('cargo credential is whitelisted check works', function () {
    $notWhitelisted = CargoCredential::factory()->create(['ip_whitelisted_at' => null]);
    $whitelisted = CargoCredential::factory()->active()->create();

    expect($notWhitelisted->isWhitelisted())->toBeFalse()
        ->and($whitelisted->isWhitelisted())->toBeTrue();
});
