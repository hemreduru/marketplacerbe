<?php

namespace App\Services;

use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Services\Contracts\ClaimServiceContract;
use App\Services\Contracts\FinanceServiceContract;
use App\Services\Contracts\OrderServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\QuestionServiceContract;
use App\Services\Trendyol\TrendyolClaimService;
use App\Services\Trendyol\TrendyolFinanceService;
use App\Services\Trendyol\TrendyolOrderService;
use App\Services\Trendyol\TrendyolProductService;
use App\Services\Trendyol\TrendyolQuestionService;
use InvalidArgumentException;

/**
 * Resolves a user's marketplace credentials and builds the concrete service
 * implementations for that marketplace. Adding a new marketplace is a matter of
 * implementing the service contracts and registering the classes in $services.
 */
class MarketplaceManager
{
    /**
     * Map of marketplace slug => [service type => concrete class].
     *
     * @var array<string, array<string, class-string>>
     */
    protected array $services = [
        'trendyol' => [
            'product' => TrendyolProductService::class,
            'order' => TrendyolOrderService::class,
            'finance' => TrendyolFinanceService::class,
            'question' => TrendyolQuestionService::class,
            'claim' => TrendyolClaimService::class,
        ],
    ];

    /**
     * Find the active credential a user holds for the given marketplace.
     */
    public function credentialFor(User $user, string $slug = 'trendyol'): ?UserMarketplaceCredential
    {
        $marketplace = Marketplace::where('slug', $slug)->first();

        if (! $marketplace) {
            return null;
        }

        return UserMarketplaceCredential::where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->first();
    }

    public function productService(UserMarketplaceCredential $credential): ProductServiceContract
    {
        return $this->make($credential, 'product');
    }

    public function orderService(UserMarketplaceCredential $credential): OrderServiceContract
    {
        return $this->make($credential, 'order');
    }

    public function financeService(UserMarketplaceCredential $credential): FinanceServiceContract
    {
        return $this->make($credential, 'finance');
    }

    public function questionService(UserMarketplaceCredential $credential): QuestionServiceContract
    {
        return $this->make($credential, 'question');
    }

    public function claimService(UserMarketplaceCredential $credential): ClaimServiceContract
    {
        return $this->make($credential, 'claim');
    }

    /**
     * Build a concrete service for the credential's marketplace.
     */
    protected function make(UserMarketplaceCredential $credential, string $type): object
    {
        $slug = $credential->marketplace->slug;
        $class = $this->services[$slug][$type] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("No {$type} service registered for marketplace [{$slug}].");
        }

        $useStage = (bool) config("marketplace.marketplaces.{$slug}.use_stage", false);

        return new $class(
            $credential->api_key,
            $credential->api_secret,
            $credential->additional_credentials['seller_id'] ?? '',
            $useStage,
        );
    }
}
