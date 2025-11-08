<?php

namespace App\Services;

use App\Models\Marketplace;
use App\Models\UserMarketplaceCredential;

class MarketplaceServiceFactory
{
    /**
     * Create a marketplace service instance.
     *
     * @param UserMarketplaceCredential $credential
     * @return MarketplaceServiceInterface
     * @throws \Exception
     */
    public static function make(UserMarketplaceCredential $credential): MarketplaceServiceInterface
    {
        $credential->load('marketplace');
        $marketplace = $credential->marketplace;

        return match (strtoupper($marketplace->code)) {
            'TRENDYOL' => new TrendyolService($credential),
            // 'HEPSIBURADA' => new HepsiburadaService($credential),
            // 'N11' => new N11Service($credential),
            // 'AMAZON' => new AmazonService($credential),
            default => throw new \Exception("Marketplace service not implemented: {$marketplace->code}")
        };
    }

    /**
     * Create a marketplace service instance by marketplace and credential IDs.
     *
     * @param int $userId
     * @param int $marketplaceId
     * @return MarketplaceServiceInterface
     * @throws \Exception
     */
    public static function makeByIds(int $userId, int $marketplaceId): MarketplaceServiceInterface
    {
        $credential = UserMarketplaceCredential::where('user_id', $userId)
            ->where('marketplace_id', $marketplaceId)
            ->where('is_active', true)
            ->firstOrFail();

        return self::make($credential);
    }

    /**
     * Create a marketplace service instance by marketplace code.
     *
     * @param int $userId
     * @param string $marketplaceCode
     * @return MarketplaceServiceInterface
     * @throws \Exception
     */
    public static function makeByCode(int $userId, string $marketplaceCode): MarketplaceServiceInterface
    {
        $marketplace = Marketplace::where('code', $marketplaceCode)
            ->where('is_active', true)
            ->firstOrFail();

        $credential = UserMarketplaceCredential::where('user_id', $userId)
            ->where('marketplace_id', $marketplace->id)
            ->where('is_active', true)
            ->firstOrFail();

        return self::make($credential);
    }

    /**
     * Get all available marketplace service classes.
     *
     * @return array
     */
    public static function getAvailableServices(): array
    {
        return [
            'TRENDYOL' => TrendyolService::class,
            // 'HEPSIBURADA' => HepsiburadaService::class,
            // 'N11' => N11Service::class,
            // 'AMAZON' => AmazonService::class,
        ];
    }

    /**
     * Check if a marketplace service is implemented.
     *
     * @param string $marketplaceCode
     * @return bool
     */
    public static function isImplemented(string $marketplaceCode): bool
    {
        return array_key_exists($marketplaceCode, self::getAvailableServices());
    }
}
