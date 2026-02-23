<?php

namespace App\Actions\Billing;

use App\Enums\BillingMeterKey;
use App\Models\BillingMeter;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class ReportUsage
{
    public function handle(Organization $organization, BillingMeterKey|string $meterKey, int $quantity = 1): void
    {
        $key = $meterKey instanceof BillingMeterKey ? $meterKey->value : $meterKey;

        Log::debug('Billing: Reporting usage', [
            'organization_id' => $organization->id,
            'meter' => $key,
            'quantity' => $quantity,
        ]);

        $meter = BillingMeter::allCached()->firstWhere('key', $key);

        if (! $meter) {
            Log::warning('Billing: Meter not found', ['meter_key' => $key]);

            return;
        }

        if (! $organization->subscribed()) {
            Log::debug('Billing: Skipping usage report, org not subscribed', [
                'organization_id' => $organization->id,
            ]);

            return;
        }

        try {
            $organization->reportMeterEvent($meter->event_name, $quantity);
        } catch (\Exception $e) {
            Log::error('Billing: Failed to report meter event to Stripe', [
                'organization_id' => $organization->id,
                'meter' => $key,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
