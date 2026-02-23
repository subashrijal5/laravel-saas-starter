<?php

namespace App\Listeners;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

class StripeWebhookListener
{
    /**
     * @var list<string>
     */
    protected array $subscriptionEvents = [
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
    ];

    public function handle(WebhookReceived $event): void
    {
        $eventType = $event->payload['type'] ?? 'unknown';

        if (! in_array($eventType, $this->subscriptionEvents)) {
            return;
        }

        $stripeCustomerId = $event->payload['data']['object']['customer'] ?? null;

        Log::info('Webhook: Stripe subscription event received', [
            'event_type' => $eventType,
            'customer_id' => $stripeCustomerId,
        ]);

        if (! $stripeCustomerId) {
            Log::warning('Webhook: Missing customer ID in payload', [
                'event_type' => $eventType,
            ]);

            return;
        }

        $organization = Cashier::findBillable($stripeCustomerId);

        if (! $organization instanceof Organization) {
            Log::warning('Webhook: Organization not found for Stripe customer', [
                'customer_id' => $stripeCustomerId,
                'event_type' => $eventType,
            ]);

            return;
        }

        try {
            $organization->clearBillingCache();
            Log::debug('Webhook: Billing cache cleared', [
                'organization_id' => $organization->id,
                'event_type' => $eventType,
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook: Failed to clear billing cache', [
                'organization_id' => $organization->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
