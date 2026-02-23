import { Transition } from '@headlessui/react';
import { Form, Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PermissionGate from '@/components/permission-gate';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, Organization } from '@/types';
import {
    update,
    destroy,
} from '@/actions/App/Http/Controllers/Organization/OrganizationController';

export default function OrganizationSettings({
    organization,
    permissions,
}: {
    organization: Organization;
    permissions: string[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Settings',
            href: `/organizations/${organization.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${organization.name} — Settings`} />

            <SettingsLayout>

                <PermissionGate permission="organization:update">
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Organization"
                            description="Update your organization name and slug."
                        />

                        <Form
                            {...update.form({
                                organization: organization.id,
                            })}
                            options={{ preserveScroll: true }}
                            className="space-y-6"
                        >
                            {({ processing, recentlySuccessful, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="org-name">
                                            Organization name
                                        </Label>
                                        <Input
                                            id="org-name"
                                            name="name"
                                            defaultValue={organization.name}
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="org-slug">Slug</Label>
                                        <Input
                                            id="org-slug"
                                            name="slug"
                                            defaultValue={organization.slug}
                                            required
                                        />
                                        <InputError message={errors.slug} />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <Button disabled={processing}>
                                            Save
                                        </Button>
                                        <Transition
                                            show={recentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="text-sm text-neutral-600">
                                                Saved
                                            </p>
                                        </Transition>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </PermissionGate>

                <PermissionGate permission="organization:delete">
                    {!organization.personal_organization && (
                        <div className="space-y-6 border-t pt-6">
                            <Heading
                                variant="small"
                                title="Delete organization"
                                description="Permanently delete this organization and all of its data."
                            />
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    if (
                                        confirm(
                                            'Are you sure you want to delete this organization? This action cannot be undone.',
                                        )
                                    ) {
                                        router.delete(
                                            destroy.url({
                                                organization: organization.id,
                                            }),
                                        );
                                    }
                                }}
                            >
                                Delete organization
                            </Button>
                        </div>
                    )}
                </PermissionGate>
            </SettingsLayout>
        </AppLayout>
    );
}
