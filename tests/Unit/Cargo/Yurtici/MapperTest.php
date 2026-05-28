<?php

use App\Services\Cargo\Yurtici\Mapper\ShipmentMapper;
use App\Services\Cargo\Yurtici\Mapper\TrackingMapper;
use App\Support\Enums\ShipmentStatus;

test('shipment mapper extracts tracking number from various response formats', function () {
    $mapper = new ShipmentMapper;

    expect($mapper->extractTrackingNumber(['trackingNumber' => 'YT1234567']))->toBe('YT1234567')
        ->and($mapper->extractTrackingNumber(['shipmentResult' => ['trackingNumber' => 'YT7654321']]))->toBe('YT7654321')
        ->and($mapper->extractTrackingNumber(['other' => 'data']))->toBeNull();
});

test('shipment mapper extracts label data from various response formats', function () {
    $mapper = new ShipmentMapper;

    expect($mapper->extractLabelData(['labelData' => 'base64encodeddata']))->toBe('base64encodeddata')
        ->and($mapper->extractLabelData(['labelResult' => ['labelData' => 'zpl_data']]))->toBe('zpl_data')
        ->and($mapper->extractLabelData(['empty' => 'response']))->toBeNull();
});

test('tracking mapper maps statuses correctly', function (string $raw, string $expected) {
    $mapper = new TrackingMapper;

    expect($mapper->mapStatus($raw))->toBe($expected);
})->with([
    ['kayit', ShipmentStatus::Created->value],
    ['şubede', ShipmentStatus::PickedUp->value],
    ['yolda', ShipmentStatus::InTransit->value],
    ['dağıtımda', ShipmentStatus::OutForDelivery->value],
    ['teslim edildi', ShipmentStatus::Delivered->value],
    ['basarisiz', ShipmentStatus::Failed->value],
    ['iade', ShipmentStatus::Returned->value],
    ['iptal', ShipmentStatus::Cancelled->value],
    ['bilinmeyen_durum', ShipmentStatus::InTransit->value],
]);

test('tracking mapper returns status list from response', function () {
    $mapper = new TrackingMapper;
    $response = [
        'shipmentStatusList' => [
            ['trackingNumber' => 'YT001', 'status' => 'yolda', 'location' => 'İstanbul', 'timestamp' => '2026-01-01T12:00:00'],
            ['trackingNumber' => 'YT002', 'status' => 'teslim edildi', 'location' => null, 'timestamp' => '2026-01-01T14:00:00'],
        ],
    ];

    $result = $mapper->toStatusList($response);

    expect($result)->toHaveCount(2)
        ->and($result[0]['tracking_number'])->toBe('YT001')
        ->and($result[0]['status'])->toBe(ShipmentStatus::InTransit->value)
        ->and($result[1]['status'])->toBe(ShipmentStatus::Delivered->value);
});

test('tracking mapper returns empty array for invalid response', function () {
    $mapper = new TrackingMapper;

    expect($mapper->toStatusList([]))->toBe([])
        ->and($mapper->toStatusList(['not_a_list' => 'value']))->toBe([]);
});
