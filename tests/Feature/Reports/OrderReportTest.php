<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrderForUser(User $user, int $marketplaceId, array $attrs = []): Order
{
    $order = Order::factory()->create(array_merge([
        'user_id' => $user->id,
        'marketplace_id' => $marketplaceId,
        'order_date' => now(),
        'status' => 'Created',
        'shipping_city' => 'Istanbul',
    ], $attrs));

    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2, 'price' => 100]);

    return $order;
}

test('analytics kullanıcısı sipariş raporunu görür', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $order = makeOrderForUser($user, $credential->marketplace_id);

    $this->actingAs($user)
        ->get(route('reports.order'))
        ->assertOk()
        ->assertSee($order->order_number);
});

test('starter kullanıcısı sipariş raporundan engellenir', function () {
    [$user, $credential] = userWithTrendyol('starter');

    $this->actingAs($user)
        ->get(route('reports.order'))
        ->assertRedirect(route('dashboard'));
});

test('toplu statü güncelleme seçili siparişleri günceller', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $o1 = makeOrderForUser($user, $credential->marketplace_id);
    $o2 = makeOrderForUser($user, $credential->marketplace_id);

    $this->actingAs($user)
        ->post(route('reports.order.bulk'), [
            'action' => 'status',
            'order_ids' => [$o1->id, $o2->id],
            'new_status' => 'Shipped',
        ])
        ->assertRedirect();

    expect($o1->fresh()->status)->toBe('Shipped')
        ->and($o2->fresh()->status)->toBe('Shipped');
});

test('toplu statü güncelleme başka kullanıcının siparişine dokunmaz', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $mine = makeOrderForUser($user, $credential->marketplace_id);

    $other = User::factory()->withPlan('pro')->create();
    $theirs = makeOrderForUser($other, $credential->marketplace_id);

    $this->actingAs($user)
        ->post(route('reports.order.bulk'), [
            'action' => 'status',
            'order_ids' => [$mine->id, $theirs->id],
            'new_status' => 'Shipped',
        ]);

    expect($mine->fresh()->status)->toBe('Shipped')
        ->and($theirs->fresh()->status)->toBe('Created');
});

test('CSV export indirilebilir', function () {
    [$user, $credential] = userWithTrendyol('pro');
    makeOrderForUser($user, $credential->marketplace_id);

    $response = $this->actingAs($user)->get(route('reports.order.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});
