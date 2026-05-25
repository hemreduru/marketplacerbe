<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanAllowsFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->hasActiveSubscription()) {
                return redirect()->route('subscription.select')->with('error', __('subscription.subscription_required'));
            }

            if (! $user->canUseFeature($feature)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => __("subscription.{$feature}_restricted"),
                    ], 403);
                }

                return redirect()->route('dashboard')->with('error', __("subscription.{$feature}_restricted"));
            }
        }

        return $next($request);
    }
}
