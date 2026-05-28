<?php

namespace App\Services\Marketplaces;

use Illuminate\Support\Facades\Config;

/**
 * config/marketplaces/{code}.php manifest'lerinden capability sorgusu.
 *
 * Kullanım:
 *   MarketplaceCapability::supports('trendyol', 'buybox') -> true
 *   MarketplaceCapability::limit('hepsiburada', 'product_title_max') -> 100
 */
class MarketplaceCapability
{
    public static function supports(string $code, string $capability): bool
    {
        return (bool) Config::get("marketplaces.{$code}.capabilities.{$capability}", false);
    }

    public static function limit(string $code, string $key): mixed
    {
        return Config::get("marketplaces.{$code}.limits.{$key}");
    }

    public static function rateLimit(string $code, string $bucket = 'default'): ?int
    {
        return Config::get("marketplaces.{$code}.rate_limits.{$bucket}.per_minute");
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function manifest(string $code): ?array
    {
        $manifest = Config::get("marketplaces.{$code}");

        return is_array($manifest) ? $manifest : null;
    }

    /**
     * Tüm tanımlı pazaryeri code'larını döndür.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $manifests = Config::get('marketplaces', []);

        return is_array($manifests) ? array_keys($manifests) : [];
    }
}
