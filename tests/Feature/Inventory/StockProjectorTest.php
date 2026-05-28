<?php

use App\Models\MasterProduct;
use App\Models\StockEvent;
use App\Services\Inventory\MasterProductStockProjector;
use App\Support\Enums\StockEventSource;
use App\Support\Enums\StockEventType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->projector = new MasterProductStockProjector;
});

it('idempotency: aynı (source, source_reference, event_type) ikinci kez insert edilemez', function () {
    $master = MasterProduct::factory()->create(['current_stock' => 10]);

    StockEvent::create([
        'event_uuid' => (string) Str::uuid(),
        'master_product_id' => $master->id,
        'event_type' => StockEventType::Sale->value,
        'source' => StockEventSource::Trendyol->value,
        'source_reference' => 'order-7777',
        'quantity_delta' => -1,
        'occurred_at' => now(),
    ]);

    expect(fn () => StockEvent::create([
        'event_uuid' => (string) Str::uuid(),
        'master_product_id' => $master->id,
        'event_type' => StockEventType::Sale->value,
        'source' => StockEventSource::Trendyol->value,
        'source_reference' => 'order-7777',
        'quantity_delta' => -1,
        'occurred_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('aynı sipariş webhook 2 kez gelirse stok bir kez azalır', function () {
    $master = MasterProduct::factory()->create(['current_stock' => 10, 'version' => 0]);

    $payload = [
        'master_product_id' => $master->id,
        'event_type' => StockEventType::Sale->value,
        'source' => StockEventSource::Trendyol->value,
        'source_reference' => 'order-1234',
        'quantity_delta' => -3,
        'occurred_at' => now(),
    ];

    $this->projector->record($payload);
    $result = $this->projector->record($payload);

    expect($result->ok)->toBeFalse();
    expect($result->errorCode)->toBe('duplicate_event');
    expect($master->fresh()->current_stock)->toBe(7);
    expect(StockEvent::count())->toBe(1);
});

it('100 farklı event ardı ardına işlendiğinde projection doğru', function () {
    $master = MasterProduct::factory()->create(['current_stock' => 1000, 'version' => 0]);

    for ($i = 1; $i <= 100; $i++) {
        $this->projector->record([
            'master_product_id' => $master->id,
            'event_type' => StockEventType::Sale->value,
            'source' => StockEventSource::Trendyol->value,
            'source_reference' => "order-{$i}",
            'quantity_delta' => -1,
            'occurred_at' => now(),
        ]);
    }

    expect($master->fresh()->current_stock)->toBe(900);
    expect($master->fresh()->version)->toBe(100);
});

it('manuel adjust + sync_in concurrent → her ikisi de event olarak işlenir', function () {
    $master = MasterProduct::factory()->create(['current_stock' => 50, 'version' => 0]);

    $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => StockEventType::ManualAdjust->value,
        'source' => StockEventSource::User->value,
        'source_reference' => 'user-adjust-1',
        'quantity_delta' => +10,
        'occurred_at' => now(),
    ]);

    $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => StockEventType::SyncIn->value,
        'source' => StockEventSource::Trendyol->value,
        'source_reference' => 'sync-2026-05-28',
        'quantity_delta' => +5,
        'occurred_at' => now(),
    ]);

    expect($master->fresh()->current_stock)->toBe(65);
    expect(StockEvent::count())->toBe(2);
});

it('iade webhook +qty ekler', function () {
    $master = MasterProduct::factory()->create(['current_stock' => 0, 'version' => 0]);

    $this->projector->record([
        'master_product_id' => $master->id,
        'event_type' => StockEventType::Return->value,
        'source' => StockEventSource::Trendyol->value,
        'source_reference' => 'claim-99',
        'quantity_delta' => +1,
        'occurred_at' => now(),
    ]);

    expect($master->fresh()->current_stock)->toBe(1);
});
