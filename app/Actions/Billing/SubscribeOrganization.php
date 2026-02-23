<?php

namespace App\Actions\Billing;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Checkout;

class SubscribeOrganization
{
    /**
     * @param  array{plan: string, interval: string}  $data
     */
    public function handle(Organization $organization, array $data): Checkout
    {
        Log::debug('Billing: Starting checkout', [
            'organization_id' => $organization->id,
            'plan' => $data['plan'],
            'interval' => $data['interval'] ?? 'monthly',
        ]);

        $plan = Plan::query()->where('key', $data['plan'])->where('is_active', true)->first();

        if (! $plan) {
            Log::warning('Billing: Plan not found or inactive', ['plan' => $data['plan']]);

            throw ValidationException::withMessages([
                'plan' => ['The selected plan is not available.'],
            ]);
        }

        if ($plan->isFreePlan()) {
            throw ValidationException::withMessages([
                'plan' => ['The free plan does not require checkout.'],
            ]);
        }

        $interval = $data['interval'] ?? 'monthly';
        $priceId = $plan->priceId($interval);

        if (! $priceId) {
            Log::warning('Billing: Price ID not found for interval', [
                'plan' => $plan->key,
                'interval' => $interval,
            ]);

            throw ValidationException::withMessages([
                'interval' => ['The selected billing interval is not available for this plan.'],
            ]);
        }

        $priceIds = [$priceId];

        if ($plan->hasMeteredPricing()) {
            foreach ($plan->stripe_metered_price_ids as $meteredPriceId) {
                $priceIds[] = $meteredPriceId;
            }
        }

        $subscription = $organization->newSubscription('default', $priceIds);

        $trialDays = config('saas.billing.trial_days');
        $hasSubscribedBefore = $organization->subscriptions()->where('type', 'default')->exists();

        if ($trialDays && ! $hasSubscribedBefore) {
            $subscription->trialDays($trialDays);
        }

        try {
            $checkout = $subscription->allowPromotionCodes()->checkout([
                'success_url' => route('billing.index').'?checkout=success',
                'cancel_url' => route('billing.index').'?checkout=cancelled',
                'metadata' => [
                    'plan_key' => $plan->key,
                    'organization_id' => $organization->id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Billing: Stripe checkout failed', [
                'organization_id' => $organization->id,
                'plan' => $plan->key,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $organization->clearBillingCache();

        Log::info('Billing: Checkout session created', [
            'organization_id' => $organization->id,
            'plan' => $plan->key,
            'interval' => $interval,
            'trial' => $trialDays && ! $hasSubscribedBefore,
        ]);

        return $checkout;
    }
}
