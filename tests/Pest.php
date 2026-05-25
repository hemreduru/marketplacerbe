<?php

use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/**
 * Create a user with an active subscription on the given plan and a Trendyol marketplace credential.
 *
 * @return array{0: User, 1: UserMarketplaceCredential}
 */
function userWithTrendyol(string $planSlug = 'growth', array $credentialAttributes = []): array
{
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();

    $user = User::factory()->withPlan($planSlug)->create();

    $credential = UserMarketplaceCredential::factory()->create(array_merge([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
        'additional_credentials' => ['seller_id' => '342591'],
    ], $credentialAttributes));

    return [$user, $credential];
}
