<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * @return array<string>
     */
    public static function types(): array
    {
        return [
            'daily_digest',
            'weekly_digest',
            'monthly_digest',
            'critical_stock',
            'sync_failure',
            'new_question',
            'new_claim',
            'oversell_alarm',
            'reconciliation_mismatch',
        ];
    }

    public function shouldSend(User $user, string $type, string $channel = 'mail'): bool
    {
        $pref = $this->preference($user, $type, $channel);

        if (! $pref) {
            return $this->defaultEnabled($type);
        }

        return $pref->enabled;
    }

    public function preference(User $user, string $type, string $channel = 'mail'): ?NotificationPreference
    {
        return NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->where('channel', $channel)
            ->first();
    }

    /**
     * @return Collection<int, NotificationPreference>
     */
    public function preferences(User $user): Collection
    {
        return NotificationPreference::where('user_id', $user->id)->get();
    }

    public function enable(User $user, string $type, string $channel = 'mail'): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type, 'channel' => $channel],
            ['enabled' => true],
        );
    }

    public function disable(User $user, string $type, string $channel = 'mail'): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type, 'channel' => $channel],
            ['enabled' => false],
        );
    }

    private function defaultEnabled(string $type): bool
    {
        return match ($type) {
            'daily_digest', 'weekly_digest', 'monthly_digest' => true,
            'critical_stock', 'sync_failure', 'new_claim', 'oversell_alarm', 'reconciliation_mismatch' => true,
            default => false,
        };
    }
}
