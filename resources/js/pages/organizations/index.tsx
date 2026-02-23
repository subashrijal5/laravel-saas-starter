import { Head, Link, router } from '@inertiajs/react';
import { Building2, Settings, Users } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Organization } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: '/organizations' },
];

export default function OrganizationsIndex({
    organizations,
}: {
    organizations: Organization[];
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organizations" />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Organizations"
                        description="Manage the organizations you belong to."
                    />
                    <Button asChild>
                        <Link href="/organizations/create">
                            Create organization
                        </Link>
                    </Button>
                </div>

                <div className="divide-y rounded-lg border">
                    {organizations.map((org) => (
                        <div
                            key={org.id}
                            className="flex items-center justify-between p-4"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-lg border bg-muted">
                                    <Building2 className="size-5 text-muted-foreground" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            {org.name}
                                        </span>
                                        {org.personal_organization && (
                                            <Badge variant="secondary">
                                                Personal
                                            </Badge>
                                        )}
                                        {org.pivot?.role && (
                                            <Badge variant="outline">
                                                {org.pivot.role}
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {org.slug}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        router.get(
                                            `/organizations/${org.id}/members`,
                                        )
                                    }
                                >
                                    <Users className="mr-1 size-4" />
                                    Members
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        router.get(`/organizations/${org.id}`)
                                    }
                                >
                                    <Settings className="mr-1 size-4" />
                                    Settings
                                </Button>
                            </div>
                        </div>
                    ))}

                    {organizations.length === 0 && (
                        <div className="p-8 text-center text-muted-foreground">
                            You don&apos;t belong to any organizations yet.
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
