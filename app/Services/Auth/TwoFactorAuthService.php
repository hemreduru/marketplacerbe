<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP tabanlı iki adımlı doğrulama akışı.
 *
 * - Secret üretimi (Base32, 16 karakter)
 * - QR provisioning URI (otpauth://...)
 * - OTP doğrulama (30 sn drift toleransı)
 * - Recovery code yönetimi (10 adet, tek kullanımlık)
 */
class TwoFactorAuthService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Yeni bir Base32 secret üretir. Kullanıcı confirm edene kadar kaydedilir
     * ama `two_factor_confirmed_at` set edilmez.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Authenticator uygulamasının QR olarak yorumlayacağı URI.
     */
    public function provisioningUri(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'Cirotik'),
            $user->email,
            $secret,
        );
    }

    /**
     * Verilen OTP doğru mu? Drift toleransı 30 saniye (1 window).
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, window: 1);
    }

    /**
     * 10 adet tek kullanımlık recovery code (10 karakter, "-" ile bölünmüş).
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 10))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /**
     * Verilen recovery code kullanıcının listesinde varsa onu tüketir
     * (listeden çıkarır) ve true döner. Yoksa false.
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = Str::lower(trim($code));

        if (! in_array($normalized, $codes, true)) {
            return false;
        }

        $user->two_factor_recovery_codes = array_values(
            array_filter($codes, fn (string $c) => $c !== $normalized),
        );
        $user->save();

        return true;
    }
}
