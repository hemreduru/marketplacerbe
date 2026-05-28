<?php

use App\Models\CargoCredential;
use App\Models\CargoProvider;
use App\Models\User;
use App\Services\Cargo\CargoManager;
use App\Services\Cargo\Exceptions\CargoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Cargo\FakeArasProvider;
use Tests\Unit\Cargo\FakeYurticiProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->manager = new CargoManager;
});

test('cargo manager throws exception for unknown provider', function () {
    $user = User::factory()->create();

    expect(fn () => $this->manager->forUser($user)->provider('bilinmeyen'))
        ->toThrow(InvalidArgumentException::class);
});

test('cargo manager throws exception when user not set', function () {
    CargoProvider::factory()->yurtici()->create();
    $this->manager->register('yurtici', FakeYurticiProvider::class);

    expect(fn () => $this->manager->provider('yurtici'))
        ->toThrow(CargoException::class, __('cargo.user_not_set'));
});

test('cargo manager throws exception when credential not found', function () {
    $user = User::factory()->create();
    $this->manager->register('yurtici', FakeYurticiProvider::class);

    expect(fn () => $this->manager->forUser($user)->provider('yurtici'))
        ->toThrow(CargoException::class);
});

test('cargo manager throws exception when credential is inactive', function () {
    $user = User::factory()->create();
    $provider = CargoProvider::factory()->yurtici()->create();
    CargoCredential::factory()->forProvider($provider)->for($user)->create(['is_active' => false]);

    $this->manager->register('yurtici', FakeYurticiProvider::class);

    expect(fn () => $this->manager->forUser($user)->provider('yurtici'))
        ->toThrow(CargoException::class);
});

test('cargo manager throws exception when ip not whitelisted', function () {
    $user = User::factory()->create();
    $provider = CargoProvider::factory()->yurtici()->create();
    CargoCredential::factory()->forProvider($provider)->for($user)->create([
        'is_active' => true,
        'ip_whitelisted_at' => null,
    ]);

    $this->manager->register('yurtici', FakeYurticiProvider::class);

    expect(fn () => $this->manager->forUser($user)->provider('yurtici'))
        ->toThrow(CargoException::class);
});

test('cargo manager resolves correct provider when credential is valid', function () {
    $user = User::factory()->create();
    $provider = CargoProvider::factory()->yurtici()->create();
    CargoCredential::factory()->forProvider($provider)->for($user)->create([
        'is_active' => true,
        'ip_whitelisted_at' => now(),
    ]);

    $this->manager->register('yurtici', FakeYurticiProvider::class);

    $resolved = $this->manager->forUser($user)->provider('yurtici');

    expect($resolved)->toBeInstanceOf(FakeYurticiProvider::class)
        ->and($resolved->getServiceCode())->toBe('yurtici');
});

test('cargo manager available codes returns registered providers', function () {
    $this->manager->register('yurtici', FakeYurticiProvider::class);
    $this->manager->register('aras', FakeArasProvider::class);

    expect($this->manager->availableCodes())->toBe(['yurtici', 'aras']);
});
