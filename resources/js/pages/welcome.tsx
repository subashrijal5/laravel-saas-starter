import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Shield, Users, Zap } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, pricing, register } from '@/routes';

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
        <>
            <Head title="Welcome">
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
                                <Link href={pricing()}>Pricing</Link>
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
                                    {canRegister && (
                                        <Button size="sm" asChild>
                                            <Link href={register()}>
                                                Get Started
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="mx-auto max-w-5xl px-6 py-24 text-center lg:py-32">
                        <h1 className="mx-auto max-w-2xl text-4xl font-bold tracking-tight text-hero-heading lg:text-5xl">
                            Everything you need to launch your SaaS
                        </h1>
                        <p className="mx-auto mt-4 max-w-lg text-lg text-hero-subtext">
                            A production-ready starter kit with
                            authentication, billing, teams, and more — so you
                            can focus on what makes your product unique.
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
                                        <Link href={canRegister ? register() : login()}>
                                            Get Started
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                    <Button
                                        size="lg"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link href={pricing()}>
                                            View Pricing
                                        </Link>
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
                                    <h3 className="font-semibold">
                                        {feature.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {feature.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border">
                    <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-6 text-sm text-muted-foreground">
                        <span>&copy; {new Date().getFullYear()} Starter Kit</span>
                        <nav className="flex gap-4">
                            <Link
                                href={pricing()}
                                className="transition-colors hover:text-foreground"
                            >
                                Pricing
                            </Link>
                        </nav>
                    </div>
                </footer>
            </div>
        </>
    );
}
