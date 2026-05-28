<?php

namespace App\Services\Cargo;

use App\Models\CargoCredential;
use App\Models\CargoProvider;
use App\Models\User;
use App\Services\Cargo\Contracts\CargoProvider as CargoProviderContract;
use App\Services\Cargo\Exceptions\CargoException;
use InvalidArgumentException;

/**
 * Kargo işlemleri için ana giriş noktası.
 *
 * Kullanım:
 *   $manager->forUser($user)->provider('yurtici')->createShipment($request);
 */
class CargoManager
{
    /**
     * Kargo sağlayıcı kodundan -> concrete class eşleştirmesi.
     *
     * @var array<string, class-string<CargoProviderContract>>
     */
    protected array $providers = [];

    /**
     * Mevcut kullanıcı (fluent context).
     */
    protected ?User $user = null;

    /**
     * Kargo sağlayıcı kaydı.
     */
    public function register(string $code, string $class): void
    {
        $this->providers[$code] = $class;
    }

    /**
     * Kullanıcı bağlamını ayarla.
     */
    public function forUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Belirtilen koda sahip sağlayıcıyı build et.
     *
     * @throws CargoException
     */
    public function provider(string $code): CargoProviderContract
    {
        $class = $this->providers[$code] ?? null;

        if (! $class) {
            throw new InvalidArgumentException(__('cargo.provider_not_found', ['code' => $code]));
        }

        if (! $this->user) {
            throw new CargoException(__('cargo.user_not_set'));
        }

        $credential = $this->resolveCredential($code);

        if (! $credential) {
            throw new CargoException(__('cargo.credential_not_found', ['code' => $code]));
        }

        if (! $credential->is_active) {
            throw new CargoException(__('cargo.credential_inactive', ['code' => $code]));
        }

        if (! $credential->isWhitelisted()) {
            throw new CargoException(__('cargo.ip_not_whitelisted', ['code' => $code]));
        }

        return new $class($credential);
    }

    /**
     * Kayıtlı sağlayıcı kodlarının listesi.
     *
     * @return string[]
     */
    public function availableCodes(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Kullanıcının belirtilen sağlayıcı için credential'ını bul.
     */
    protected function resolveCredential(string $code): ?CargoCredential
    {
        $provider = CargoProvider::active()->byCode($code)->first();

        if (! $provider) {
            return null;
        }

        return CargoCredential::where('user_id', $this->user->id)
            ->where('cargo_provider_id', $provider->id)
            ->first();
    }
}
