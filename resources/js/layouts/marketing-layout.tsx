import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, pricing, register } from '@/routes';

export default function MarketingLayout({
    children,
}: {
    children: ReactNode;
}) {
    const { auth } = usePage().props;

    return (
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
                                <Button variant="ghost" size="sm" asChild>
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button size="sm" asChild>
                                    <Link href={register()}>Get Started</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t border-border">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-6 text-sm text-muted-foreground">
                    <span>
                        &copy; {new Date().getFullYear()} Starter Kit
                    </span>
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
    );
}
