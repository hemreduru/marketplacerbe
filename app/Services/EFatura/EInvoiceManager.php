<?php

namespace App\Services\EFatura;

use App\Models\EInvoiceCredential;
use App\Models\User;
use App\Services\EFatura\Contracts\EInvoiceProvider;
use App\Services\EFatura\Exceptions\EInvoiceException;

class EInvoiceManager
{
    /**
     * @var array<string, class-string<EInvoiceProvider>>
     */
    protected array $providers = [];

    protected ?User $user = null;

    public function register(string $code, string $class): void
    {
        $this->providers[$code] = $class;
    }

    public function forUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @throws EInvoiceException
     */
    public function provider(string $code): EInvoiceProvider
    {
        $class = $this->providers[$code] ?? null;

        if (! $class) {
            throw new EInvoiceException(__('efatura.provider_not_found', ['code' => $code]));
        }

        if (! $this->user) {
            throw new EInvoiceException(__('efatura.user_context_required'));
        }

        $credential = EInvoiceCredential::active()
            ->where('user_id', $this->user->id)
            ->where('provider', $code)
            ->first();

        if (! $credential) {
            throw new EInvoiceException(__('efatura.credential_not_found', ['code' => $code]));
        }

        return new $class($credential);
    }

    /**
     * @return string[]
     */
    public function availableCodes(): array
    {
        return array_keys($this->providers);
    }
}
