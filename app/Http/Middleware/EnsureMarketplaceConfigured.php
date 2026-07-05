<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarketplaceConfigured
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Apply this lock only if they have an active plan (subscription comes first)
            if ($user->hasActiveSubscription()) {

                // Allow marketplace-settings, subscriptions, password profile updates, and logout
                if ($request->is('marketplace-settings*') ||
                    $request->is('subscription*') ||
                    $request->is('onboarding*') ||
                    $request->is('stop-impersonating') ||
                    $request->is('logout') ||
                    $request->is('settings*')) {
                    return $next($request);
                }

                // Check if user has at least one active marketplace credentials
                if (! $user->marketplaceCredentials()->exists()) {
                    return redirect()->route('marketplace.settings')->with('info', 'Cirotik panelinizi aktif hale getirebilmek için lütfen ilk pazaryeri API bağlantınızı kurun.');
                }
            }
        }

        return $next($request);
    }
}
