<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorAuthService $service) {}

    /**
     * Login sonrası, 2FA aktif kullanıcıyı OTP girmeye yönlendirir.
     */
    public function showChallenge(Request $request)
    {
        if (! $request->session()->has('two_factor.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * OTP veya recovery code doğrulama.
     */
    public function verifyChallenge(Request $request)
    {
        $userId = $request->session()->get('two_factor.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::find($userId);
        if (! $user || ! $user->hasEnabledTwoFactor()) {
            $request->session()->forget('two_factor.user_id');

            return redirect()->route('login');
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->input('code'));
        $isValid = $this->service->verify($user->two_factor_secret, $code)
            || $this->service->consumeRecoveryCode($user, $code);

        if (! $isValid) {
            return back()->withErrors(['code' => __('auth.two_factor.invalid_code')]);
        }

        $request->session()->forget('two_factor.user_id');
        Auth::login($user, (bool) $request->session()->pull('two_factor.remember', false));
        $request->session()->regenerate();

        return redirect()->intended('dashboard');
    }

    /**
     * Profil ayarlarında 2FA setup ekranı (QR + onay).
     */
    public function showSetup(Request $request)
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactor()) {
            return view('auth.two-factor-manage', [
                'recoveryCodes' => $user->two_factor_recovery_codes ?? [],
            ]);
        }

        $secret = $request->session()->get('two_factor_setup.secret');
        if (! $secret) {
            $secret = $this->service->generateSecret();
            $request->session()->put('two_factor_setup.secret', $secret);
        }

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'qrUri' => $this->service->provisioningUri($user, $secret),
        ]);
    }

    /**
     * QR akışını tamamlayıp confirm eder.
     */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $secret = $request->session()->get('two_factor_setup.secret');
        if (! $secret) {
            return redirect()->route('two-factor.setup');
        }

        if (! $this->service->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => __('auth.two_factor.invalid_code')]);
        }

        $user = $request->user();
        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $this->service->generateRecoveryCodes();
        $user->two_factor_confirmed_at = now();
        $user->save();

        $request->session()->forget('two_factor_setup.secret');

        return redirect()->route('two-factor.setup')
            ->with('status', __('auth.two_factor.enabled'));
    }

    /**
     * 2FA'yı devre dışı bırakır (parola tekrar doğrulanmalı).
     */
    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return redirect()->route('settings')
            ->with('status', __('auth.two_factor.disabled'));
    }
}
