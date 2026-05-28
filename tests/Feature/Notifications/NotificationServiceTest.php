<?php

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;

test('shouldSend returns true when no preference set (default enabled)', function () {
    $user = User::factory()->create();
    $service = new NotificationService;

    expect($service->shouldSend($user, 'daily_digest'))->toBeTrue();
    expect($service->shouldSend($user, 'critical_stock'))->toBeTrue();
});

test('shouldSend returns false when disabled', function () {
    $user = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'notification_type' => 'daily_digest',
        'channel' => 'mail',
        'enabled' => false,
    ]);

    $service = new NotificationService;

    expect($service->shouldSend($user, 'daily_digest'))->toBeFalse();
});

test('enable creates or updates preference', function () {
    $user = User::factory()->create();
    $service = new NotificationService;

    $service->enable($user, 'critical_stock');
    $pref = $service->preference($user, 'critical_stock');

    expect($pref)->not->toBeNull();
    expect($pref->enabled)->toBeTrue();
});

test('disable sets enabled to false', function () {
    $user = User::factory()->create();
    $service = new NotificationService;

    $service->disable($user, 'daily_digest');
    $pref = $service->preference($user, 'daily_digest');

    expect($pref->enabled)->toBeFalse();
});

test('types returns all notification types', function () {
    $service = new NotificationService;

    expect($service::types())->toContain('sync_failure', 'critical_stock', 'daily_digest');
});
