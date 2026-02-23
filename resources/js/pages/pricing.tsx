import { Head, Link, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { dashboard, home, login, pricing as pricingRoute, register } from '@/routes';
import type { PlanData } from '@/types';

const configPrices: Record<string, Record<string, number>> = {
    free: {},
    pro: { monthly: 2900, yearly: 29000 },
    enterprise: { monthly: 9900, yearly: 99000 },
};

export default function Pricing({ plans }: { plans: PlanData[] }) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Pricing">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="border-b border-border">
                    <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
                        <Link
                            href={home()}
                            className="flex items-center gap-2 font-semibold"
                        >
                            <div className="flex size-8 items-center justify-center rounded-md bg-brand text-brand-foreground">
                                <AppLogoIcon className="size-5 fill-current" />
                            </div>
                            <span>Starter Kit</span>
                        </Link>

                        <nav className="flex items-center gap-2">
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={pricingRoute()}>Pricing</Link>
                            </Button>

                            {auth.user ? (
                                <Button size="sm" asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        asChild
                                    >
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button size="sm" asChild>
                                        <Link href={register()}>
                                            Get Started
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="mx-auto max-w-5xl px-6 py-20 text-center">
                        <h1 className="text-3xl font-bold tracking-tight text-hero-heading lg:text-4xl">
                            Simple, transparent pricing
                        </h1>
                        <p className="mx-auto mt-3 max-w-md text-hero-subtext">
                            Choose the plan that fits your needs. Upgrade or
                            downgrade at any time.
                        </p>
                    </section>

                    <section className="mx-auto max-w-5xl px-6 pb-24">
                        <div className="grid gap-6 md:grid-cols-3">
                            {plans.map((plan, index) => {
                                const isHighlighted = index === 1;
                                const prices = configPrices[plan.key] ?? {};
                                const isFreePlan = !plan.stripe_price_ids;

                                return (
                                    <Card
                                        key={plan.key}
                                        className={cn(
                                            'flex flex-col',
                                            isHighlighted &&
                                                'border-plan-highlight shadow-md',
                                        )}
                                    >
                                        <CardHeader>
                                            <CardTitle>{plan.name}</CardTitle>
                                            <CardDescription>
                                                {plan.description}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="flex-1">
                                            <div className="mb-6">
                                                {isFreePlan ? (
                                                    <p className="text-4xl font-bold">
                                                        Free
                                                    </p>
                                                ) : (
                                                    <p className="text-4xl font-bold">
                                                        $
                                                        {prices.monthly
                                                            ? (
                                                                  prices.monthly /
                                                                  100
                                                              ).toFixed(0)
                                                            : '—'}
                                                        <span className="text-base font-normal text-muted-foreground">
                                                            /mo
                                                        </span>
                                                    </p>
                                                )}
                                            </div>
                                            <ul className="space-y-2.5">
                                                {plan.features?.map(
                                                    (feature) => (
                                                        <li
                                                            key={feature}
                                                            className="flex items-start gap-2 text-sm"
                                                        >
                                                            <Check className="mt-0.5 size-4 shrink-0 text-brand" />
                                                            {feature}
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </CardContent>
                                        <CardFooter>
                                            {auth.user ? (
                                                <Button
                                                    className={cn(
                                                        'w-full',
                                                        isHighlighted &&
                                                            'bg-plan-highlight text-plan-highlight-foreground hover:bg-plan-highlight/90',
                                                    )}
                                                    variant={
                                                        isHighlighted
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    asChild
                                                >
                                                    <Link href={dashboard()}>
                                                        Go to Dashboard
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <Button
                                                    className={cn(
                                                        'w-full',
                                                        isHighlighted &&
                                                            'bg-plan-highlight text-plan-highlight-foreground hover:bg-plan-highlight/90',
                                                    )}
                                                    variant={
                                                        isHighlighted
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    asChild
                                                >
                                                    <Link href={register()}>
                                                        {isFreePlan
                                                            ? 'Get Started Free'
                                                            : 'Start Free Trial'}
                                                    </Link>
                                                </Button>
                                            )}
                                        </CardFooter>
                                    </Card>
                                );
                            })}
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border">
                    <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-6 text-sm text-muted-foreground">
                        <span>
                            &copy; {new Date().getFullYear()} Starter Kit
                        </span>
                        <nav className="flex gap-4">
                            <Link
                                href={home()}
                                className="transition-colors hover:text-foreground"
                            >
                                Home
                            </Link>
                        </nav>
                    </div>
                </footer>
            </div>
        </>
    );
}
