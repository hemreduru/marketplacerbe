<?php

namespace App\Http\Middleware;

use App\Models\UserSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Get user settings from database
            $settings = UserSetting::where('user_id', $user->id)->first();

            if ($settings && $settings->preferredLanguage) {
                $locale = $settings->preferredLanguage->code ?? 'tr';
                App::setLocale($locale);
                session(['app_locale' => $locale]);
                session(['app_dark_mode' => $settings->dark_mode ?? false]);
            }
        }

        // If session has locale, use it
        if (session()->has('app_locale')) {
            App::setLocale(session('app_locale'));
        }

        return $next($request);
    }
}
