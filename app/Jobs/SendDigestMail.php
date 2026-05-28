<?php

namespace App\Jobs;

use App\Mail\DailyDigest;
use App\Mail\MonthlyDigest;
use App\Mail\WeeklyDigest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDigestMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type,
    ) {}

    public function handle(): void
    {
        $notifications = new NotificationService;
        $users = User::whereHas('subscriptions', fn ($q) => $q->where('stripe_status', 'active'))->get();

        foreach ($users as $user) {
            if (! $notifications->shouldSend($user, $this->type)) {
                continue;
            }

            $mailable = match ($this->type) {
                'weekly_digest' => new WeeklyDigest(
                    user: $user,
                    netProfit: '0.00',
                    revenue: '0.00',
                    orderCount: 0,
                    margin: '0.00',
                    returnRate: '0.00',
                ),
                'monthly_digest' => new MonthlyDigest(
                    user: $user,
                    netProfit: '0.00',
                    revenue: '0.00',
                    orderCount: 0,
                    margin: '0.00',
                    returnRate: '0.00',
                ),
                default => new DailyDigest(
                    user: $user,
                    netProfit: '0.00',
                    revenue: '0.00',
                    orderCount: 0,
                    margin: '0.00',
                    returnRate: '0.00',
                ),
            };

            Mail::to($user)->queue($mailable);
        }
    }
}
