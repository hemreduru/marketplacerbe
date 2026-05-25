<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingMail;
use App\Mail\TrialExpiredMail;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:process-expired';

    protected $description = 'Expire trials and past-due subscriptions that have passed their end date';

    public function handle(): int
    {
        $this->notifyTrialsEnding();
        $trialExpired = $this->expireTrials();
        $pastDue = $this->markPastDue();
        $fullyExpired = $this->expirePastDue();

        $this->info("Trials expired: {$trialExpired} | Marked past_due: {$pastDue} | Fully expired: {$fullyExpired}");

        Log::info('ProcessExpiredSubscriptions completed', [
            'trials_expired' => $trialExpired,
            'marked_past_due' => $pastDue,
            'fully_expired' => $fullyExpired,
        ]);

        return self::SUCCESS;
    }

    private function notifyTrialsEnding(): void
    {
        // Notify users whose trial ends in exactly 3 days
        Subscription::where('status', 'trialing')
            ->whereBetween('trial_ends_at', [now()->addDays(3)->startOfDay(), now()->addDays(3)->endOfDay()])
            ->with('user')
            ->get()
            ->each(function (Subscription $subscription) {
                Mail::to($subscription->user->email)
                    ->queue(new TrialEndingMail($subscription->user, 3));
            });
    }

    private function expireTrials(): int
    {
        $expiring = Subscription::where('status', 'trialing')
            ->where('trial_ends_at', '<=', now())
            ->with('user')
            ->get();

        $expiring->each(function (Subscription $subscription) {
            $subscription->update(['status' => 'expired']);
            Mail::to($subscription->user->email)
                ->queue(new TrialExpiredMail($subscription->user));
        });

        return $expiring->count();
    }

    private function markPastDue(): int
    {
        return Subscription::where('status', 'active')
            ->where('current_period_end', '<=', now())
            ->update(['status' => 'past_due']);
    }

    /** Fully expire subscriptions that have exceeded the 3-day grace period. */
    private function expirePastDue(): int
    {
        return Subscription::where('status', 'past_due')
            ->where('current_period_end', '<=', now()->subDays(3))
            ->update(['status' => 'expired']);
    }
}
