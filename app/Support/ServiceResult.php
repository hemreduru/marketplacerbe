<?php

namespace App\Support;

/**
 * Pazaryeri / harici servis çağrılarının dönüş tipi.
 *
 * Exception sadece programming bug içindir. API hatası, network hatası,
 * validation hatası, rate limit vb. durumlar bu VO ile döner (ok=false).
 *
 * @template TData
 */
final class ServiceResult
{
    /**
     * @param  TData|null  $data
     * @param  array<string, mixed>|null  $raw  Marketplace'ten gelen ham response (debug için)
     */
    public function __construct(
        public readonly bool $ok,
        public readonly mixed $data = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly ?array $raw = null,
    ) {}

    /**
     * Başarılı sonuç.
     *
     * @template TOk
     *
     * @param  TOk  $data
     * @return self<TOk>
     */
    public static function ok(mixed $data = null): self
    {
        return new self(ok: true, data: $data);
    }

    /**
     * Hata sonucu.
     *
     * @param  array<string, mixed>|null  $raw
     * @return self<null>
     */
    public static function fail(string $code, string $message, ?array $raw = null): self
    {
        return new self(
            ok: false,
            errorCode: $code,
            errorMessage: $message,
            raw: $raw,
        );
    }
}
