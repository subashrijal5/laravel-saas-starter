<?php

namespace App\Console\Commands;

use App\Enums\BillingFeature;
use App\Models\Organization;
use App\Notifications\UsageApproachingLimitNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckUsageLimitsCommand extends Command
{
    protected $signature = 'saas:check-usage-limits';

    protected $description = 'Send notifications when usage approaches plan limits';

    public function handle(): int
    {
        /** @var list<int> $thresholds */
        $thresholds = config('saas.notifications.usage_thresholds', [80, 90]);

        $notifiedCount = 0;

        Organization::query()
            ->with('owner')
            ->whereHas('owner')
            ->chunk(100, function ($organizations) use ($thresholds, &$notifiedCount) {
                foreach ($organizations as $organization) {
                    foreach (BillingFeature::cases() as $feature) {
                        $notifiedCount += $this->checkFeatureUsage($organization, $feature, $thresholds);
                    }
                }
            });

        $this->info("Sent {$notifiedCount} usage limit notification(s).");

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $thresholds
     */
    protected function checkFeatureUsage(Organization $organization, BillingFeature $feature, array $thresholds): int
    {
        $limit = $organization->planLimit($feature);

        if ($limit === null || $limit === 0) {
            return 0;
        }

        $currentUsage = $this->resolveFeatureUsage($organization, $feature);

        if ($currentUsage === null) {
            return 0;
        }

        $percentage = (int) floor(($currentUsage / $limit) * 100);
        $notifiedCount = 0;

        foreach ($thresholds as $threshold) {
            if ($percentage < $threshold) {
                continue;
            }

            $alreadySent = $organization->owner->notifications()
                ->where('type', UsageApproachingLimitNotification::class)
                ->where('data->organization_id', $organization->id)
                ->where('data->feature', $feature->value)
                ->where('data->percentage', $threshold)
                ->where('created_at', '>=', now()->startOfMonth())
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $organization->owner->notify(new UsageApproachingLimitNotification(
                $organization,
                $feature->value,
                $currentUsage,
                $limit,
                $threshold,
            ));

            $notifiedCount++;

            Log::info('Notification: Usage approaching limit', [
                'organization_id' => $organization->id,
                'feature' => $feature->value,
                'current_usage' => $currentUsage,
                'limit' => $limit,
                'percentage' => $percentage,
            ]);
        }

        return $notifiedCount;
    }

    protected function resolveFeatureUsage(Organization $organization, BillingFeature $feature): ?int
    {
        return match ($feature) {
            BillingFeature::Items => $organization->members()->count(),
            default => null,
        };
    }
}
