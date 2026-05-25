<?php

use App\Models\Claim;
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
                    'orderLine' => ['id' => 1],
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
