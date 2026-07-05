<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeTrialMail;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\IyzicoService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private IyzicoService $iyzicoService,
    ) {}

    public function select(): View
    {
        $user = Auth::user();
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('subscription.select', [
            'plans' => $plans,
            'user' => $user,
            'hasUsedTrial' => $user->hasUsedTrial(),
        ]);
    }

    public function startTrial(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasUsedTrial()) {
            return redirect()->route('subscription.select')
                ->with('error', __('subscription.trial_already_used'));
        }

        $plan = Plan::where('name', 'growth')->firstOrFail();
        $this->subscriptionService->startTrial($user, $plan);
        Mail::to($user->email)->queue(new WelcomeTrialMail($user, $plan->trial_days));

        return redirect()->route('dashboard')
            ->with('success', __('subscription.trial_started', ['days' => $plan->trial_days]));
    }

    /**
     * Abonelik başlatır: ödeme kaydı + iyzico 3DS. 3DS gerekmezse (debug/otomatik
     * onay) abonelik hemen aktive edilir; gerçek 3DS'te banka formu render edilir.
     */
    public function subscribe(Request $request): RedirectResponse|Response
    {
        $request->validate([
            'plan' => 'required|string|exists:plans,name',
            'billing_period' => 'sometimes|in:monthly,yearly',
        ]);

        $plan = Plan::where('name', $request->plan)->firstOrFail();

        /** @var User $user */
        $user = Auth::user();
        $billingPeriod = $request->billing_period ?? 'monthly';
        $amount = $billingPeriod === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $payment = SubscriptionPayment::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_period' => $billingPeriod,
            'amount' => $amount,
            'currency' => 'TRY',
            'status' => 'pending',
            'conversation_id' => (string) Str::uuid(),
        ]);

        $result = $this->iyzicoService->initializeThreeDSPayment([
            'price' => $amount,
            'paidPrice' => $amount,
            'currency' => 'TRY',
            'conversationId' => $payment->conversation_id,
            'callbackUrl' => route('subscription.payment.callback'),
            'buyer' => ['name' => $user->name, 'email' => $user->email],
        ]);

        if (! $result->ok) {
            $payment->update(['status' => 'failed', 'error_message' => $result->errorMessage]);

            return back()->with('error', $result->errorMessage ?? __('subscription.payment_error'));
        }

        $data = (array) $result->data;
        $threeDSHtml = (string) ($data['threeDSHtmlContent'] ?? '');

        // 3DS gerekmiyorsa (debug/otomatik onay) hemen aktive et.
        if ($threeDSHtml === '') {
            return $this->activateFromPayment($payment, (string) ($data['paymentId'] ?? ''));
        }

        // Gerçek 3DS: banka doğrulama formunu render et (kullanıcı bankaya yönlenir).
        return response()->view('subscription.three-ds', ['html' => $threeDSHtml]);
    }

    /**
     * iyzico 3DS callback'i — abonelik ödemesini doğrular ve aktive eder.
     * Public endpoint (iyzico session'suz POST eder); kullanıcı conversation_id
     * ile ödeme kaydından çözülür.
     */
    public function paymentCallback(Request $request): RedirectResponse
    {
        $conversationId = (string) $request->input('conversationId');

        $payment = SubscriptionPayment::where('conversation_id', $conversationId)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            return redirect()->route('subscription.select')
                ->with('error', __('subscription.payment_error'));
        }

        $result = $this->iyzicoService->completeThreeDSPayment([
            'conversationId' => $conversationId,
            'paymentId' => $request->input('paymentId'),
        ]);

        $data = (array) $result->data;

        if (! $result->ok || ($data['status'] ?? '') !== 'success') {
            $payment->update([
                'status' => 'failed',
                'error_message' => $result->errorMessage ?? 'callback verification failed',
            ]);

            return redirect()->route('subscription.select')
                ->with('error', __('subscription.payment_failed'));
        }

        return $this->activateFromPayment($payment, (string) ($data['paymentId'] ?? ''));
    }

    public function cancel(): RedirectResponse
    {
        $this->subscriptionService->cancel(Auth::user());

        return redirect()->route('subscription.select')
            ->with('success', __('subscription.canceled_success'));
    }

    /**
     * Başarılı ödemeyi aboneliğe çevirir + ödeme kaydını success işaretler.
     */
    private function activateFromPayment(SubscriptionPayment $payment, string $paymentId): RedirectResponse
    {
        $this->subscriptionService->subscribe(
            $payment->user,
            $payment->plan,
            $payment->billing_period,
            $paymentId,
        );

        $payment->update([
            'status' => 'success',
            'payment_id' => $paymentId,
            'paid_at' => now(),
        ]);

        return redirect()->route('marketplace.settings')
            ->with('success', __('subscription.subscribed_success', ['plan' => $payment->plan->display_name]));
    }
}
