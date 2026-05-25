<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionService
{
    /**
     * Start a trial subscription on the given plan for a newly registered user.
     */
    public function startTrial(User $user, Plan $plan): Subscription
    {
        $trialEndsAt = now()->addDays($plan->trial_days);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'billing_period' => 'monthly',
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => now(),
            'current_period_end' => $trialEndsAt,
        ]);
    }

    /**
     * Activate a paid subscription, canceling any existing active subscription first.
     */
    public function subscribe(
        User $user,
        Plan $plan,
        string $billingPeriod = 'monthly',
        ?string $paymentReference = null
    ): Subscription {
        $this->cancelActiveSubscription($user);

        $periodEnd = $billingPeriod === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_period' => $billingPeriod,
            'current_period_start' => now(),
            'current_period_end' => $periodEnd,
            'payment_reference' => $paymentReference,
        ]);
    }

    /**
     * Cancel the user's active subscription immediately.
     */
    public function cancel(User $user): void
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }

    /**
     * Upgrade or downgrade to a different plan by creating a new active subscription.
     */
    public function upgrade(User $user, Plan $plan, string $billingPeriod = 'monthly', ?string $paymentReference = null): Subscription
    {
        return $this->subscribe($user, $plan, $billingPeriod, $paymentReference);
    }

    private function cancelActiveSubscription(User $user): void
    {
        $subscription = $user->activeSubscription();

        if ($subscription && ! $subscription->canceled()) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);
        }
    }
}
