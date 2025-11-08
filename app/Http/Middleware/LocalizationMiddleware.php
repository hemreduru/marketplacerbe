<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from Accept-Language header or query parameter
        $locale = $request->header('Accept-Language', config('app.locale'));

        // Support query parameter override: ?lang=tr
        if ($request->has('lang')) {
            $locale = $request->query('lang');
        }

        // Normalize locale (handle cases like 'tr-TR' -> 'tr')
        $locale = substr($locale, 0, 2);

        // Validate locale (only tr and en supported)
        $supportedLocales = ['tr', 'en'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.fallback_locale', 'en');
        }

        // Set application locale
        app()->setLocale($locale);

        return $next($request);
    }
}
