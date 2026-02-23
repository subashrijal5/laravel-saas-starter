import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Shield, Users, Zap } from 'lucide-react';
import { Button } from '@/components/ui/button';
import MarketingLayout from '@/layouts/marketing-layout';
import { dashboard, login, pricing, register } from '@/routes';

const features = [
    {
        icon: Zap,
        title: 'Lightning Fast',
        description:
            'Built for speed with optimized infrastructure that scales with your business.',
    },
    {
        icon: Users,
        title: 'Team Collaboration',
        description:
            'Invite your team, manage roles, and work together seamlessly.',
    },
    {
        icon: Shield,
        title: 'Secure by Default',
        description:
            'Enterprise-grade security with two-factor auth and audit logging.',
    },
];

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage().props;

    return (
        <MarketingLayout>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>

            <section className="mx-auto max-w-5xl px-6 py-24 text-center lg:py-32">
                <h1 className="mx-auto max-w-2xl text-4xl font-bold tracking-tight text-hero-heading lg:text-5xl">
                    Everything you need to launch your SaaS
                </h1>
                <p className="mx-auto mt-4 max-w-lg text-lg text-hero-subtext">
                    A production-ready starter kit with authentication,
                    billing, teams, and more — so you can focus on what
                    makes your product unique.
                </p>
                <div className="mt-8 flex items-center justify-center gap-3">
                    {auth.user ? (
                        <Button size="lg" asChild>
                            <Link href={dashboard()}>
                                Go to Dashboard
                                <ArrowRight />
                            </Link>
                        </Button>
                    ) : (
                        <>
                            <Button
                                size="lg"
                                className="bg-brand text-brand-foreground hover:bg-brand/90"
                                asChild
                            >
                                <Link
                                    href={
                                        canRegister ? register() : login()
                                    }
                                >
                                    Get Started
                                    <ArrowRight />
                                </Link>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <Link href={pricing()}>View Pricing</Link>
                            </Button>
                        </>
                    )}
                </div>
            </section>

            <section className="border-t border-border bg-muted/50">
                <div className="mx-auto grid max-w-5xl gap-8 px-6 py-20 md:grid-cols-3">
                    {features.map((feature) => (
                        <div key={feature.title} className="space-y-2">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-brand-muted text-brand">
                                <feature.icon className="size-5" />
                            </div>
                            <h3 className="font-semibold">{feature.title}</h3>
                            <p className="text-sm text-muted-foreground">
                                {feature.description}
                            </p>
                        </div>
                    ))}
                </div>
            </section>
        </MarketingLayout>
    );
}
