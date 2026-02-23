<?php

namespace App\Concerns;

use App\Enums\BillingFeature;
use App\Enums\BillingPlan;
use App\Models\Plan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait HasSubscription
{
    public function currentPlan(): ?Plan
    {
        $prefix = config('saas.cache.prefix', 'saas');

        try {
            return Cache::remember(
                "{$prefix}:org:{$this->id}:plan",
                600,
                fn () => $this->resolveCurrentPlanFromSubscription()
            );
        } catch (\Exception $e) {
            Log::warning('Billing: Cache read failed for current plan, falling back to DB', [
                'organization_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return $this->resolveCurrentPlanFromSubscription();
        }
    }

    public function currentPlanKey(): string
    {
        return $this->currentPlan()?->key ?? BillingPlan::Free->value;
    }

    public function planLimit(BillingFeature|string $feature): ?int
    {
        $key = $feature instanceof BillingFeature ? $feature->value : $feature;
        $plan = $this->currentPlan();

        if (! $plan) {
            $freePlan = Plan::allCached()->firstWhere('key', BillingPlan::Free->value);

            return $freePlan?->limit($key) ?? 0;
        }

        return $plan->limit($key);
    }

    public function withinLimit(BillingFeature|string $feature, int $currentCount): bool
    {
        $limit = $this->planLimit($feature);

        if ($limit === null) {
            return true;
        }

        return $currentCount < $limit;
    }

    public function exceedsLimit(BillingFeature|string $feature, int $currentCount): bool
    {
        return ! $this->withinLimit($feature, $currentCount);
    }

    public function onPlan(BillingPlan|string $planKey): bool
    {
        $key = $planKey instanceof BillingPlan ? $planKey->value : $planKey;

        return $this->currentPlanKey() === $key;
    }

    public function onFreePlan(): bool
    {
        return $this->onPlan(BillingPlan::Free);
    }

    public function onPaidPlan(): bool
    {
        $plan = $this->currentPlan();

        return $plan !== null && ! $plan->isFreePlan();
    }

    public function onTrialOrSubscribed(): bool
    {
        return $this->subscribed() || $this->onTrial();
    }

    public function clearBillingCache(): void
    {
        $prefix = config('saas.cache.prefix', 'saas');

        try {
            Cache::forget("{$prefix}:org:{$this->id}:plan");
        } catch (\Exception $e) {
            Log::warning('Billing: Failed to clear billing cache', [
                'organization_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function resolveCurrentPlanFromSubscription(): ?Plan
    {
        $subscription = $this->subscription();

        if (! $subscription || ! $subscription->valid()) {
            return Plan::allCached()->firstWhere('key', BillingPlan::Free->value);
        }

        $stripePriceId = $subscription->stripe_price;

        return Plan::allCached()->first(function (Plan $plan) use ($stripePriceId) {
            if (! $plan->stripe_price_ids) {
                return false;
            }

            return in_array($stripePriceId, $plan->stripe_price_ids);
        });
    }
}
