<?php

use App\Actions\Billing\ReportUsage;
use App\Enums\BillingFeature;
use App\Enums\BillingMeterKey;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

if (! function_exists('current_plan')) {
    function current_plan(): ?Plan
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->currentOrganization?->currentPlan();
    }
}

if (! function_exists('plan_limit')) {
    function plan_limit(BillingFeature|string $feature): ?int
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->currentOrganization?->planLimit($feature);
    }
}

if (! function_exists('within_limit')) {
    function within_limit(BillingFeature|string $feature, int $count): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->currentOrganization?->withinLimit($feature, $count) ?? false;
    }
}

if (! function_exists('report_usage')) {
    function report_usage(BillingMeterKey|string $meter, int $qty = 1): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user?->currentOrganization;

        if (! $organization) {
            Log::debug('Billing: report_usage called without active organization');

            return;
        }

        app(ReportUsage::class)->handle($organization, $meter, $qty);
    }
}

if (! function_exists('clear_billing_cache')) {
    function clear_billing_cache(?Organization $org = null): void
    {
        try {
            if ($org) {
                $org->clearBillingCache();

                return;
            }

            /** @var User|null $user */
            $user = Auth::user();
            $user?->currentOrganization?->clearBillingCache();
        } catch (\Exception $e) {
            Log::warning('Billing: Failed to clear billing cache via helper', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
