<?php

use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('credential is_active değişimi activity log düşer', function () {
    $user = User::factory()->create();
    $marketplace = Marketplace::factory()->create();
    $credential = UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
        'is_active' => true,
    ]);

    $credential->update(['is_active' => false]);

    $activity = Activity::query()
        ->where('log_name', 'marketplace_credential')
        ->where('subject_id', $credential->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and((bool) $activity->changes['attributes']['is_active'])->toBeFalse()
        ->and((bool) $activity->changes['old']['is_active'])->toBeTrue();
});

test('credential update api_secret loglanmaz (gizli alan)', function () {
    $user = User::factory()->create();
    $marketplace = Marketplace::factory()->create();
    $credential = UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
        'api_secret' => 'old-secret',
    ]);

    Activity::query()->delete();
    $credential->update(['api_secret' => 'new-secret']);

    $updateCount = Activity::query()
        ->where('subject_id', $credential->id)
        ->where('event', 'updated')
        ->count();
    expect($updateCount)->toBe(0);
});

test('user email değişimi user log_name ile yazılır', function () {
    $user = User::factory()->create(['email' => 'old@example.com']);

    $user->update(['email' => 'new@example.com']);

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->changes['attributes']['email'])->toBe('new@example.com');
});
