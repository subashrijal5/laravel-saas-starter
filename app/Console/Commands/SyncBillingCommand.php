<?php

namespace App\Console\Commands;

use App\Models\BillingMeter;
use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

class SyncBillingCommand extends Command
{
    protected $signature = 'saas:sync';

    protected $description = 'Sync billing plans and meters from config to Stripe and the database';

    public function handle(): int
    {
        $billingConfig = config('saas.billing');

        if (empty($billingConfig)) {
            $this->error('No billing configuration found in config/saas.php');

            return self::FAILURE;
        }

        Log::info('SyncBilling: Starting sync');
        $this->info('Syncing billing configuration with Stripe...');
        $this->newLine();

        $this->syncMeters($billingConfig['meters'] ?? []);
        $this->syncPlans($billingConfig['plans'] ?? [], $billingConfig['currency'] ?? 'usd');
        $this->detectRemovedPlans($billingConfig['plans'] ?? []);
        $this->flushCaches();

        $this->newLine();
        $this->displaySummary();

        Log::info('SyncBilling: Sync completed');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{display_name: string, event_name: string}>  $meters
     */
    protected function syncMeters(array $meters): void
    {
        if (empty($meters)) {
            return;
        }

        $this->components->info('Syncing meters...');

        foreach ($meters as $key => $meterConfig) {
            $meter = BillingMeter::query()->where('key', $key)->first();

            if ($meter) {
                $meter->update([
                    'display_name' => $meterConfig['display_name'],
                    'event_name' => $meterConfig['event_name'],
                ]);
                Log::info('SyncBilling: Meter updated', ['key' => $key]);
                $this->components->twoColumnDetail($key, '<fg=yellow>updated</>');
            } else {
                $stripeMeter = $this->createStripeMeter($meterConfig);

                try {
                    BillingMeter::query()->create([
                        'key' => $key,
                        'display_name' => $meterConfig['display_name'],
                        'event_name' => $meterConfig['event_name'],
                        'stripe_meter_id' => $stripeMeter?->id,
                    ]);
                    Log::info('SyncBilling: Meter created', ['key' => $key]);
                } catch (\Exception $e) {
                    Log::error('SyncBilling: Failed to create meter in DB', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                    $this->components->warn("Failed to create meter '{$key}': {$e->getMessage()}");

                    continue;
                }

                $this->components->twoColumnDetail($key, '<fg=green>created</>');
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $plans
     */
    protected function syncPlans(array $plans, string $currency): void
    {
        if (empty($plans)) {
            return;
        }

        $this->components->info('Syncing plans...');

        $sortOrder = 0;

        foreach ($plans as $key => $planConfig) {
            $existingPlan = Plan::query()->where('key', $key)->first();

            $stripeProductId = $existingPlan?->stripe_product_id;
            $stripePriceIds = $existingPlan?->stripe_price_ids ?? [];
            $stripeMeteredPriceIds = $existingPlan?->stripe_metered_price_ids ?? [];

            $hasPrices = ! empty($planConfig['prices']);

            if ($hasPrices) {
                $stripeProductId = $this->syncStripeProduct($stripeProductId, $planConfig);
                $stripePriceIds = $this->syncStripePrices(
                    $stripeProductId,
                    $stripePriceIds,
                    $planConfig['prices'],
                    $currency,
                );
            }

            if (! empty($planConfig['metered'])) {
                $stripeProductId ??= $this->syncStripeProduct($stripeProductId, $planConfig);
                $stripeMeteredPriceIds = $this->syncStripeMeteredPrices(
                    $stripeProductId,
                    $stripeMeteredPriceIds,
                    $planConfig['metered'],
                    $currency,
                );
            }

            try {
                Plan::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'name' => $planConfig['name'],
                        'description' => $planConfig['description'] ?? null,
                        'stripe_product_id' => $stripeProductId,
                        'stripe_price_ids' => $hasPrices ? $stripePriceIds : null,
                        'stripe_metered_price_ids' => ! empty($stripeMeteredPriceIds) ? $stripeMeteredPriceIds : null,
                        'limits' => $planConfig['limits'] ?? null,
                        'features' => $planConfig['features'] ?? null,
                        'sort_order' => $sortOrder++,
                        'is_active' => true,
                    ],
                );
            } catch (\Exception $e) {
                Log::error('SyncBilling: Failed to upsert plan in DB', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
                $this->components->warn("Failed to save plan '{$key}': {$e->getMessage()}");

                continue;
            }

            $status = $existingPlan ? '<fg=yellow>updated</>' : '<fg=green>created</>';
            Log::info('SyncBilling: Plan synced', ['key' => $key, 'status' => $existingPlan ? 'updated' : 'created']);
            $this->components->twoColumnDetail($key, $status);
        }
    }

    protected function syncStripeProduct(?string $existingProductId, array $planConfig): ?string
    {
        $stripe = Cashier::stripe();

        try {
            if ($existingProductId) {
                $stripe->products->update($existingProductId, [
                    'name' => $planConfig['name'],
                    'description' => $planConfig['description'] ?? '',
                ]);

                return $existingProductId;
            }

            $product = $stripe->products->create([
                'name' => $planConfig['name'],
                'description' => $planConfig['description'] ?? '',
            ]);

            return $product->id;
        } catch (\Exception $e) {
            Log::error('SyncBilling: Stripe product sync failed', [
                'product_id' => $existingProductId,
                'error' => $e->getMessage(),
            ]);
            $this->components->warn("Stripe API error: {$e->getMessage()}");

            return $existingProductId;
        }
    }

    /**
     * @param  array<string, string>  $existingPriceIds
     * @param  array<string, int>  $prices
     * @return array<string, string>
     */
    protected function syncStripePrices(?string $productId, array $existingPriceIds, array $prices, string $currency): array
    {
        if (! $productId) {
            return $existingPriceIds;
        }

        $stripe = Cashier::stripe();
        $intervalMap = ['monthly' => 'month', 'yearly' => 'year'];
        $result = $existingPriceIds;

        foreach ($prices as $interval => $amount) {
            $stripeInterval = $intervalMap[$interval] ?? $interval;
            $existingPriceId = $existingPriceIds[$interval] ?? null;

            try {
                if ($existingPriceId) {
                    $existingPrice = $stripe->prices->retrieve($existingPriceId);
                    if ((int) $existingPrice->unit_amount === (int) $amount) {
                        continue;
                    }
                    $stripe->prices->update($existingPriceId, ['active' => false]);
                }

                $price = $stripe->prices->create([
                    'product' => $productId,
                    'unit_amount' => $amount,
                    'currency' => $currency,
                    'recurring' => ['interval' => $stripeInterval],
                ]);

                $result[$interval] = $price->id;
            } catch (\Exception $e) {
                Log::error('SyncBilling: Stripe price sync failed', [
                    'product_id' => $productId,
                    'interval' => $interval,
                    'error' => $e->getMessage(),
                ]);
                $this->components->warn("Stripe price sync error ({$interval}): {$e->getMessage()}");
            }
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $existingMeteredPriceIds
     * @param  array<string, array{meter: string, unit_amount: int}>  $meteredConfig
     * @return array<string, string>
     */
    protected function syncStripeMeteredPrices(?string $productId, array $existingMeteredPriceIds, array $meteredConfig, string $currency): array
    {
        if (! $productId) {
            return $existingMeteredPriceIds;
        }

        $stripe = Cashier::stripe();
        $result = $existingMeteredPriceIds;

        foreach ($meteredConfig as $key => $config) {
            $meter = BillingMeter::query()->where('key', $config['meter'])->first();

            if (! $meter?->stripe_meter_id) {
                Log::warning('SyncBilling: Meter not found for metered price', [
                    'meter_key' => $config['meter'],
                    'price_key' => $key,
                ]);
                $this->components->warn("Meter '{$config['meter']}' not found or has no Stripe ID. Skipping metered price '{$key}'.");

                continue;
            }

            $existingPriceId = $existingMeteredPriceIds[$key] ?? null;

            try {
                if ($existingPriceId) {
                    $existingPrice = $stripe->prices->retrieve($existingPriceId);
                    if ((int) $existingPrice->unit_amount === (int) $config['unit_amount']) {
                        continue;
                    }
                    $stripe->prices->update($existingPriceId, ['active' => false]);
                }

                $price = $stripe->prices->create([
                    'product' => $productId,
                    'unit_amount' => $config['unit_amount'],
                    'currency' => $currency,
                    'recurring' => [
                        'interval' => 'month',
                        'meter' => $meter->stripe_meter_id,
                        'usage_type' => 'metered',
                    ],
                ]);

                $result[$key] = $price->id;
            } catch (\Exception $e) {
                Log::error('SyncBilling: Stripe metered price sync failed', [
                    'price_key' => $key,
                    'error' => $e->getMessage(),
                ]);
                $this->components->warn("Stripe metered price sync error ({$key}): {$e->getMessage()}");
            }
        }

        return $result;
    }

    /**
     * @param  array{display_name: string, event_name: string}  $meterConfig
     */
    protected function createStripeMeter(array $meterConfig): ?object
    {
        try {
            return Cashier::stripe()->billing->meters->create([
                'display_name' => $meterConfig['display_name'],
                'default_aggregation' => ['formula' => 'sum'],
                'event_name' => $meterConfig['event_name'],
            ]);
        } catch (\Exception $e) {
            Log::error('SyncBilling: Stripe meter creation failed', [
                'display_name' => $meterConfig['display_name'],
                'error' => $e->getMessage(),
            ]);
            $this->components->warn("Stripe meter creation error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $configPlans
     */
    protected function detectRemovedPlans(array $configPlans): void
    {
        $dbPlans = Plan::query()->where('is_active', true)->pluck('key');
        $removedKeys = $dbPlans->diff(array_keys($configPlans));

        if ($removedKeys->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->components->warn('The following plans exist in the database but not in config:');

        foreach ($removedKeys as $key) {
            $this->components->twoColumnDetail($key, '<fg=red>removed from config</>');
        }

        if ($this->confirm('Do you want to deactivate these plans?')) {
            foreach ($removedKeys as $key) {
                $plan = Plan::query()->where('key', $key)->first();

                if ($plan?->stripe_product_id) {
                    try {
                        Cashier::stripe()->products->update($plan->stripe_product_id, ['active' => false]);
                    } catch (\Exception $e) {
                        Log::error('SyncBilling: Failed to deactivate Stripe product', [
                            'plan_key' => $key,
                            'error' => $e->getMessage(),
                        ]);
                        $this->components->warn("Could not deactivate Stripe product for '{$key}': {$e->getMessage()}");
                    }
                }

                $plan?->update(['is_active' => false]);
                Log::info('SyncBilling: Plan deactivated', ['key' => $key]);
            }

            $this->components->info('Removed plans have been deactivated.');
        }
    }

    protected function flushCaches(): void
    {
        $prefix = config('saas.cache.prefix', 'saas');
        Cache::forget("{$prefix}:plans");
        Cache::forget("{$prefix}:meters");
    }

    protected function displaySummary(): void
    {
        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->get();

        $this->components->info('Current billing plans:');
        $this->newLine();

        $rows = $plans->map(fn (Plan $plan) => [
            $plan->key,
            $plan->name,
            $plan->stripe_product_id ?? '-',
            $plan->stripe_price_ids ? implode(', ', $plan->stripe_price_ids) : '-',
            $plan->isFreePlan() ? 'Free' : 'Paid',
        ]);

        $this->table(
            ['Key', 'Name', 'Stripe Product', 'Price IDs', 'Type'],
            $rows,
        );
    }
}
