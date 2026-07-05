<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
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

            // Allow access to subscription selection, mock subscribe endpoint, and logout
            if ($request->is('subscription*') || $request->is('onboarding*') || $request->is('stop-impersonating') || $request->is('logout')) {
                return $next($request);
            }

            if (! $user->hasActiveSubscription()) {
                return redirect()->route('subscription.select')->with('error', __('subscription.subscription_required'));
            }
        }

        return $next($request);
    }
}
