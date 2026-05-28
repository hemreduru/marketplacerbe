<?php

use App\Models\MasterProduct;
use App\Models\PriceEvent;
use App\Services\Inventory\MasterProductPriceProjector;
use App\Support\Enums\PriceEventType;
use App\Support\Enums\StockEventSource;

beforeEach(function () {
    $this->projector = new MasterProductPriceProjector;
});

it('ilk fiyat değişimini kabul eder ve master.current_price güncellenir', function () {
    $master = MasterProduct::factory()->create(['current_price' => 100.0000]);

    $result = $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => PriceEventType::ManualChange->value,
        'source' => StockEventSource::User->value,
        'source_reference' => 'manual-1',
        'new_price' => 129.99,
        'previous_price' => 100.00,
        'occurred_at' => now(),
    ]);

    expect($result->ok)->toBeTrue();
    expect((string) $master->fresh()->current_price)->toBe('129.9900');
});

it('15dk içinde aynı SKU için 2. fiyat update reddedilir', function () {
    $master = MasterProduct::factory()->create(['current_price' => 100.0000]);

    $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => PriceEventType::ManualChange->value,
        'source' => StockEventSource::User->value,
        'source_reference' => 'first',
        'new_price' => 110.00,
        'occurred_at' => now(),
    ]);

    $second = $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => PriceEventType::ManualChange->value,
        'source' => StockEventSource::User->value,
        'source_reference' => 'second',
        'new_price' => 120.00,
        'occurred_at' => now()->addSeconds(60),
    ]);

    expect($second->ok)->toBeFalse();
    expect($second->errorCode)->toBe('rate_limited_window');
    expect(PriceEvent::count())->toBe(1);
    expect((string) $master->fresh()->current_price)->toBe('110.0000');
});

it('15dk geçtikten sonra 2. fiyat update kabul edilir', function () {
    $master = MasterProduct::factory()->create(['current_price' => 100.0000]);

    $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => PriceEventType::ManualChange->value,
        'source' => StockEventSource::User->value,
        'source_reference' => 'first',
        'new_price' => 110.00,
        'occurred_at' => now()->subMinutes(20),
    ]);

    $second = $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => PriceEventType::ManualChange->value,
        'source' => StockEventSource::User->value,
        'source_reference' => 'second',
        'new_price' => 130.00,
        'occurred_at' => now(),
    ]);

    expect($second->ok)->toBeTrue();
    expect((string) $master->fresh()->current_price)->toBe('130.0000');
});
