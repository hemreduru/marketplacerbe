<?php

use App\Services\Cargo\ValueObjects\CargoAddress;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\PackageInfo;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\Enums\CargoLabelFormat;
use App\Support\Enums\CargoPaymentType;

test('package info value object holds correct data', function () {
    $pkg = new PackageInfo(
        weightKg: 1.5,
        desi: 2.0,
        widthCm: 20.0,
        heightCm: 15.0,
        lengthCm: 10.0,
        content: 'Kitap',
        quantity: 2,
    );

    expect($pkg->weightKg)->toBe(1.5)
        ->and($pkg->desi)->toBe(2.0)
        ->and($pkg->quantity)->toBe(2)
        ->and($pkg->content)->toBe('Kitap');
});

test('cargo address value object holds correct data', function () {
    $addr = new CargoAddress(
        fullName: 'Ali Veli',
        phone: '05551234567',
        city: 'İstanbul',
        district: 'Kadıköy',
        address: 'Moda Cad. No:1',
        companyName: 'Test A.Ş.',
    );

    expect($addr->fullName)->toBe('Ali Veli')
        ->and($addr->city)->toBe('İstanbul')
        ->and($addr->companyName)->toBe('Test A.Ş.');
});

test('shipment request value object holds packages and addresses', function () {
    $sender = new CargoAddress('Ali', '0555', 'İstanbul', 'Kadıköy', 'Adres 1');
    $receiver = new CargoAddress('Veli', '0556', 'Ankara', 'Çankaya', 'Adres 2');
    $packages = [
        new PackageInfo(1.0, 2.0),
        new PackageInfo(0.5, 1.0),
    ];

    $req = new ShipmentRequest(
        sender: $sender,
        receiver: $receiver,
        packages: $packages,
        paymentType: CargoPaymentType::SenderPays,
        orderReference: 'ORDER-001',
    );

    expect($req->sender->fullName)->toBe('Ali')
        ->and($req->receiver->city)->toBe('Ankara')
        ->and($req->packages)->toHaveCount(2)
        ->and($req->paymentType)->toBe(CargoPaymentType::SenderPays)
        ->and($req->orderReference)->toBe('ORDER-001');
});

test('label format factory methods work', function () {
    $a4 = LabelFormat::a4(8);

    expect($a4->format)->toBe(CargoLabelFormat::A4Pdf)
        ->and($a4->positionsPerPage)->toBe(8)
        ->and($a4->isThermal())->toBeFalse();

    $zpl = LabelFormat::zpl();

    expect($zpl->format)->toBe(CargoLabelFormat::Zpl)
        ->and($zpl->isThermal())->toBeTrue();

    $png = LabelFormat::png();

    expect($png->format)->toBe(CargoLabelFormat::Png);
});

test('cargo label format default is a4 pdf with 6 positions', function () {
    $format = new LabelFormat;

    expect($format->format)->toBe(CargoLabelFormat::A4Pdf)
        ->and($format->positionsPerPage)->toBe(6);
});
