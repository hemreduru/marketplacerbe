<?php

use App\Models\Claim;
use App\Services\Finance\ReturnCostResolver;
use Illuminate\Support\Facades\Http;

test('claim sync stores claims pulled from the marketplace', function () {
    [$user, $credential] = userWithTrendyol('pro');

    Http::fake([
        '*/integration/order/sellers/*/claims*' => Http::sequence()
            ->push(['content' => [[
                'id' => 4242,
                'orderNumber' => '123456789',
                'customerFirstName' => 'Ada',
                'customerLastName' => 'Lovelace',
                'items' => [[
                    'orderLine' => ['id' => 1, 'price' => 150.00],
                    'claimItems' => [['claimItemStatus' => ['name' => 'Accepted']]],
                ]],
                'claimDate' => now()->getTimestampMs(),
            ]], 'totalPages' => 1]),
    ]);

    $this->actingAs($user)
        ->post(route('claims.sync'))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Claim::where('user_marketplace_credential_id', $credential->id)->count())->toBe(1);

    $claim = Claim::first();
    expect($claim->remote_id)->toBe('4242');
    expect($claim->order_number)->toBe('123456789');
    expect($claim->customer_name)->toBe('Ada Lovelace');
    expect($claim->status)->toBe('Accepted');
    expect($claim->item_count)->toBe(1);
    // K.2: iade tutarı + onay tarihi doldurulur (önceden ikisi de boştu)
    expect($claim->refund_amount)->toBe('150.0000');
    expect($claim->approved_at)->not->toBeNull();
});

test('ReturnCostResolver yalnızca onaylı iadeyi order_number bazında toplar (K.2)', function () {
    [, $credential] = userWithTrendyol('pro');

    Claim::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'order_number' => 'ORD-1',
        'refund_amount' => '80.0000',
        'approved_at' => now(),
        'claim_date' => now(),
    ]);
    // approved_at NULL → iade maliyetine katılmaz
    Claim::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'order_number' => 'ORD-2',
        'refund_amount' => '999.0000',
        'approved_at' => null,
        'claim_date' => now(),
    ]);

    $map = app(ReturnCostResolver::class)->approvedReturnCostMap(
        $credential->id,
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
    );

    expect($map->get('ORD-1'))->toBe('80.0000')
        ->and($map->has('ORD-2'))->toBeFalse();
});

test('claims data endpoint returns claims for the datatable', function () {
    [$user, $credential] = userWithTrendyol('pro');
    Claim::factory()->count(3)->create(['user_marketplace_credential_id' => $credential->id]);

    $this->actingAs($user)
        ->getJson(route('claims.data').'?draw=1&start=0&length=10')
        ->assertOk()
        ->assertJson(['recordsTotal' => 3]);
});

test('approving a claim is simulated when writes are disabled', function () {
    config(['marketplace.write_enabled' => false]);
    Http::preventStrayRequests();

    [$user, $credential] = userWithTrendyol('pro');
    $claim = Claim::factory()->create(['user_marketplace_credential_id' => $credential->id]);

    $this->actingAs($user)
        ->post(route('claims.approve'), [
            'claim_id' => $claim->remote_id,
            'claim_item_ids' => [1, 2],
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'message' => __('common.action_simulated')]);

    Http::assertNothingSent();
});
