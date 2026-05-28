<?php

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;

it('creates a master product with three listings via factory chain', function () {
    $master = MasterProduct::factory()
        ->has(MarketplaceListing::factory()->count(3), 'listings')
        ->create();

    expect($master->listings)->toHaveCount(3);
    expect($master->listings->first()->master->id)->toBe($master->id);
});

it('persists decimal precision for cost_price (4 decimal places)', function () {
    $master = MasterProduct::factory()->create([
        'cost_price' => 12.3456,
        'current_price' => 99.9999,
    ]);

    expect((string) $master->cost_price)->toBe('12.3456');
    expect((string) $master->current_price)->toBe('99.9999');
});

it('marketplace_listing belongsTo credential', function () {
    $listing = MarketplaceListing::factory()->create();

    expect($listing->credential)->not->toBeNull();
});
