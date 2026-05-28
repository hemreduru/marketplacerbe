<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and set locale based on:
     * 1. Authenticated user's preferred language
     * 2. Accept-Language header
     * 3. App default locale
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = config('app.locale', 'tr');

        // Priority 1: Authenticated user's preferred language
        if (Auth::check()) {
            $user = Auth::user();

            // Load user settings with preferred language
            if ($user->settings && $user->settings->preferredLanguage) {
                $locale = $user->settings->preferredLanguage->code;
            }
        }

        // Priority 2: Accept-Language header from frontend
        if (! Auth::check() && $request->hasHeader('Accept-Language')) {
            $headerLocale = $request->header('Accept-Language');
            // Normalize locale (handle cases like 'tr-TR' -> 'tr')
            $headerLocale = substr($headerLocale, 0, 2);

            // Validate against supported locales
            if (in_array($headerLocale, ['tr', 'en'])) {
                $locale = $headerLocale;
            }
        }

        // Priority 3: Query parameter override (for testing): ?lang=tr
        if ($request->has('lang')) {
            $queryLocale = $request->query('lang');
            if (in_array($queryLocale, ['tr', 'en'])) {
                $locale = $queryLocale;
            }
        }

        // Set application locale
        app()->setLocale($locale);

        return $next($request);
    }
}
