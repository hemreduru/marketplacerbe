<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\IyzicoService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private IyzicoService $iyzicoService,
    ) {}

    public function select(): View
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('subscription.select', [
            'plans' => $plans,
            'user' => Auth::user(),
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => 'required|string|exists:plans,name',
            'billing_period' => 'sometimes|in:monthly,yearly',
        ]);

        $plan = Plan::where('name', $request->plan)->firstOrFail();
        $user = Auth::user();

        $paymentResult = $this->iyzicoService->createPayment([
            'price' => $request->billing_period === 'yearly' ? $plan->price_yearly : $plan->price_monthly,
            'currency' => 'TRY',
            'buyer' => ['name' => $user->name, 'email' => $user->email],
        ]);

        if (! $paymentResult['success']) {
            return back()->with('error', $paymentResult['message']);
        }

        $this->subscriptionService->subscribe(
            $user,
            $plan,
            $request->billing_period ?? 'monthly',
            $paymentResult['reference'],
        );

        return redirect()->route('marketplace.settings')
            ->with('success', __('subscription.subscribed_success', ['plan' => $plan->display_name]));
    }

    public function cancel(): RedirectResponse
    {
        $this->subscriptionService->cancel(Auth::user());

        return redirect()->route('subscription.select')
            ->with('success', __('subscription.canceled_success'));
    }
}
