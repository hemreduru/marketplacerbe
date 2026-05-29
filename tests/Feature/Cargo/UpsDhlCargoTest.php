<?php

use App\Models\CargoCredential;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\Dhl\DhlService;
use App\Services\Cargo\Ups\UpsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('UPS ve DHL servisleri CargoProvider implemente eder', function () {
    $credential = CargoCredential::factory()->create();

    $ups = new UpsService($credential);
    $dhl = new DhlService($credential);

    expect($ups)->toBeInstanceOf(CargoProvider::class)
        ->and($dhl)->toBeInstanceOf(CargoProvider::class)
        ->and($ups->getServiceCode())->toBe('ups')
        ->and($dhl->getServiceCode())->toBe('dhl');
});

test('aktif olmayan UPS/DHL henüz implemente değil ServiceResult döner', function () {
    $credential = CargoCredential::factory()->create();
    $ups = new UpsService($credential);

    $result = $ups->track('1Z999');

    expect($result->ok)->toBeFalse()
        ->and($result->errorCode)->toBe('ups_not_implemented');
});

test('config UPS ve DHL için class eşlemesi içerir', function () {
    expect(config('cargo.providers.ups.class'))->toBe(UpsService::class)
        ->and(config('cargo.providers.dhl.class'))->toBe(DhlService::class);
});
