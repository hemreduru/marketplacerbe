<?php

namespace App\Services;

use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Hepsiburada\ClaimService as HbClaimService;
use App\Services\Marketplaces\Hepsiburada\Client as HbClient;
use App\Services\Marketplaces\Hepsiburada\FinanceService as HbFinanceService;
use App\Services\Marketplaces\Hepsiburada\OrderService as HbOrderService;
use App\Services\Marketplaces\Hepsiburada\ProductService as HbProductService;
use App\Services\Marketplaces\Hepsiburada\QuestionService as HbQuestionService;
use App\Services\Marketplaces\N11\ClaimService as N11ClaimService;
use App\Services\Marketplaces\N11\Client as N11Client;
use App\Services\Marketplaces\N11\FinanceService as N11FinanceService;
use App\Services\Marketplaces\N11\OrderService as N11OrderService;
use App\Services\Marketplaces\N11\ProductService as N11ProductService;
use App\Services\Marketplaces\N11\QuestionService as N11QuestionService;
use App\Services\Marketplaces\Pazarama\ClaimService as PzClaimService;
use App\Services\Marketplaces\Pazarama\Client as PzClient;
use App\Services\Marketplaces\Pazarama\FinanceService as PzFinanceService;
use App\Services\Marketplaces\Pazarama\OrderService as PzOrderService;
use App\Services\Marketplaces\Pazarama\ProductService as PzProductService;
use App\Services\Marketplaces\Pazarama\QuestionService as PzQuestionService;
use App\Services\Marketplaces\Trendyol\ClaimService as TyClaimService;
use App\Services\Marketplaces\Trendyol\Client as TyClient;
use App\Services\Marketplaces\Trendyol\FinanceService as TyFinanceService;
use App\Services\Marketplaces\Trendyol\OrderService as TyOrderService;
use App\Services\Marketplaces\Trendyol\ProductService as TyProductService;
use App\Services\Marketplaces\Trendyol\QuestionService as TyQuestionService;
use InvalidArgumentException;

/**
 * Kullanicinin marketplace credential'ina gore servis obje'lerini build eder.
 * Tum servisler config tabanli HTTP cagrilari yapar.
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
            'product' => TyProductService::class,
            'order' => TyOrderService::class,
            'finance' => TyFinanceService::class,
            'question' => TyQuestionService::class,
            'claim' => TyClaimService::class,
        ],
        'hepsiburada' => [
            'product' => HbProductService::class,
            'order' => HbOrderService::class,
            'finance' => HbFinanceService::class,
            'question' => HbQuestionService::class,
            'claim' => HbClaimService::class,
        ],
        'n11' => [
            'product' => N11ProductService::class,
            'order' => N11OrderService::class,
            'finance' => N11FinanceService::class,
            'question' => N11QuestionService::class,
            'claim' => N11ClaimService::class,
        ],
        'pazarama' => [
            'product' => PzProductService::class,
            'order' => PzOrderService::class,
            'finance' => PzFinanceService::class,
            'question' => PzQuestionService::class,
            'claim' => PzClaimService::class,
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

    public function productService(UserMarketplaceCredential $credential): object
    {
        return $this->make($credential, 'product');
    }

    public function orderService(UserMarketplaceCredential $credential): object
    {
        return $this->make($credential, 'order');
    }

    public function financeService(UserMarketplaceCredential $credential): object
    {
        return $this->make($credential, 'finance');
    }

    public function questionService(UserMarketplaceCredential $credential): object
    {
        return $this->make($credential, 'question');
    }

    public function claimService(UserMarketplaceCredential $credential): object
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

        $client = match ($slug) {
            'trendyol' => new TyClient(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                (bool) config('marketplaces.trendyol.use_stage', false),
            ),
            'hepsiburada' => new HbClient(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                (bool) config('marketplaces.hepsiburada.use_stage', true),
            ),
            'n11' => new N11Client(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
            ),
            'pazarama' => new PzClient(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
            ),
            default => throw new InvalidArgumentException("No client for marketplace [{$slug}]."),
        };

        return new $class($client);
    }
}
