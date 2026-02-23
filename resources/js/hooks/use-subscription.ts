import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { BillingData } from '@/types';

export function useSubscription() {
    const { auth } = usePage().props;
    const billing = auth.billing as BillingData | null;

    return useMemo(
        () => ({
            plan: billing?.plan ?? 'free',
            planName: billing?.plan_name ?? 'Free',
            isSubscribed: billing?.is_subscribed ?? false,
            isOnTrial: billing?.is_on_trial ?? false,
            isFree: !billing?.is_subscribed && !billing?.is_on_trial,
            trialEndsAt: billing?.trial_ends_at
                ? new Date(billing.trial_ends_at)
                : null,
            limits: billing?.limits ?? {},
        }),
        [billing],
    );
}
