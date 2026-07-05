<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Show register form
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Brute-force koruması: email+IP başına 5 deneme / dk, sonra kilit.
        $throttleKey = Str::lower((string) $request->input('email')).'|'.(string) $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ])->withInput();
        }

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Auth::validate($credentials)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->withInput();
        }

        RateLimiter::clear($throttleKey);

        // 2FA aktifse: oturum henüz başlatılmaz; challenge sayfasına yönlendirilir.
        if ($user->hasEnabledTwoFactor()) {
            $request->session()->put('two_factor.user_id', $user->id);
            $request->session()->put('two_factor.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('dashboard');
    }

    /**
     * Handle register request
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
