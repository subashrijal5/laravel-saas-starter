<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Notifications\PlanExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringPlansCommand extends Command
{
    protected $signature = 'saas:check-expiring-plans';

    protected $description = 'Send notifications for plans expiring within configured thresholds';

    public function handle(): int
    {
        /** @var list<int> $daysBeforeThresholds */
        $daysBeforeThresholds = config('saas.notifications.plan_expiry_days_before', [7, 3, 1]);

        $notifiedCount = 0;

        foreach ($daysBeforeThresholds as $daysBefore) {
            $targetDate = now()->addDays($daysBefore)->startOfDay();

            $organizations = Organization::query()
                ->whereHas('subscriptions', function ($query) use ($targetDate) {
                    $query->where(function ($q) use ($targetDate) {
                        $q->whereNotNull('trial_ends_at')
                            ->whereDate('trial_ends_at', $targetDate);
                    })->orWhere(function ($q) use ($targetDate) {
                        $q->whereNotNull('ends_at')
                            ->whereDate('ends_at', $targetDate);
                    });
                })
                ->with('owner')
                ->get();

            foreach ($organizations as $organization) {
                $owner = $organization->owner;

                if (! $owner) {
                    continue;
                }

                $alreadySent = $owner->notifications()
                    ->where('type', PlanExpiringNotification::class)
                    ->where('data->organization_id', $organization->id)
                    ->where('data->days_remaining', $daysBefore)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $owner->notify(new PlanExpiringNotification($organization, $daysBefore));
                $notifiedCount++;

                Log::info('Notification: Plan expiring', [
                    'organization_id' => $organization->id,
                    'owner_id' => $owner->id,
                    'days_remaining' => $daysBefore,
                ]);
            }
        }

        $this->info("Sent {$notifiedCount} plan expiry notification(s).");

        return self::SUCCESS;
    }
}
