import { Head, router } from '@inertiajs/react';
import { Check, ExternalLink } from 'lucide-react';
import Heading from '@/components/heading';
import PlanBadge from '@/components/plan-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useSubscription } from '@/hooks/use-subscription';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index as billingIndex, checkout, portal } from '@/routes/billing';
import type { BreadcrumbItem, PlanData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: billingIndex.url() },
];

export default function BillingIndex({
    plans,
    currentPlan,
    isSubscribed,
    isOnTrial,
    trialEndsAt,
}: {
    plans: PlanData[];
    currentPlan: PlanData | null;
    isSubscribed: boolean;
    isOnTrial: boolean;
    trialEndsAt: string | null;
}) {
    const subscription = useSubscription();

    function handleCheckout(planKey: string, interval: string) {
        router.post(checkout.url(), { plan: planKey, interval });
    }

    function handlePortal() {
        router.get(portal.url());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Billing"
                            description="Manage your subscription and billing."
                        />
                        <PlanBadge />
                    </div>

                    {isOnTrial && trialEndsAt && (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                            <p className="text-sm text-blue-800 dark:text-blue-200">
                                Your trial ends on{' '}
                                <strong>
                                    {new Date(
                                        trialEndsAt,
                                    ).toLocaleDateString()}
                                </strong>
                                . Choose a plan below to continue after your
                                trial.
                            </p>
                        </div>
                    )}

                    {isSubscribed && (
                        <div className="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <p className="text-sm font-medium">
                                    Current plan:{' '}
                                    <strong>{currentPlan?.name}</strong>
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Manage your subscription, payment methods,
                                    and invoices through the billing portal.
                                </p>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handlePortal}
                            >
                                <ExternalLink className="mr-1 size-4" />
                                Manage billing
                            </Button>
                        </div>
                    )}

                    <div className="grid gap-4 md:grid-cols-3">
                        {plans.map((plan) => {
                            const isCurrent =
                                currentPlan?.key === plan.key;
                            const isFreePlan = !plan.stripe_price_ids;

                            const configPrices =
                                getConfigPrices(plan.key);

                            return (
                                <Card
                                    key={plan.key}
                                    className={
                                        isCurrent
                                            ? 'border-primary'
                                            : ''
                                    }
                                >
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <CardTitle>
                                                {plan.name}
                                            </CardTitle>
                                            {isCurrent && (
                                                <Badge>Current</Badge>
                                            )}
                                        </div>
                                        <CardDescription>
                                            {plan.description}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="mb-4">
                                            {isFreePlan ? (
                                                <p className="text-3xl font-bold">
                                                    Free
                                                </p>
                                            ) : (
                                                <p className="text-3xl font-bold">
                                                    $
                                                    {configPrices.monthly
                                                        ? (
                                                              configPrices.monthly /
                                                              100
                                                          ).toFixed(0)
                                                        : '?'}
                                                    <span className="text-sm font-normal text-muted-foreground">
                                                        /mo
                                                    </span>
                                                </p>
                                            )}
                                        </div>
                                        <ul className="space-y-2">
                                            {plan.features?.map(
                                                (feature) => (
                                                    <li
                                                        key={feature}
                                                        className="flex items-center gap-2 text-sm"
                                                    >
                                                        <Check className="size-4 text-green-500" />
                                                        {feature}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </CardContent>
                                    <CardFooter>
                                        {isCurrent ? (
                                            <Button
                                                className="w-full"
                                                variant="outline"
                                                disabled
                                            >
                                                Current plan
                                            </Button>
                                        ) : isFreePlan ? (
                                            <Button
                                                className="w-full"
                                                variant="outline"
                                                disabled={
                                                    subscription.isFree
                                                }
                                            >
                                                {subscription.isFree
                                                    ? 'Current plan'
                                                    : 'Downgrade'}
                                            </Button>
                                        ) : (
                                            <div className="flex w-full gap-2">
                                                <Button
                                                    className="flex-1"
                                                    onClick={() =>
                                                        handleCheckout(
                                                            plan.key,
                                                            'monthly',
                                                        )
                                                    }
                                                >
                                                    Monthly
                                                </Button>
                                                <Button
                                                    className="flex-1"
                                                    variant="outline"
                                                    onClick={() =>
                                                        handleCheckout(
                                                            plan.key,
                                                            'yearly',
                                                        )
                                                    }
                                                >
                                                    Yearly
                                                </Button>
                                            </div>
                                        )}
                                    </CardFooter>
                                </Card>
                            );
                        })}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

function getConfigPrices(planKey: string): Record<string, number> {
    const defaults: Record<string, Record<string, number>> = {
        free: {},
        pro: { monthly: 2900, yearly: 29000 },
        enterprise: { monthly: 9900, yearly: 99000 },
    };

    return defaults[planKey] ?? {};
}
