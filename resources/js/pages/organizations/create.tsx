import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { store } from '@/actions/App/Http/Controllers/Organization/OrganizationController';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: '/organizations' },
    { title: 'Create', href: '/organizations/create' },
];

export default function CreateOrganization() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Organization" />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-6">
                <Heading
                    title="Create a new organization"
                    description="Organizations allow you to collaborate with others."
                />

                <Form {...store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Organization name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    placeholder="Acme Inc."
                                    required
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">
                                    Slug{' '}
                                    <span className="text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    placeholder="acme-inc"
                                />
                                <p className="text-sm text-muted-foreground">
                                    A URL-friendly identifier. Auto-generated if
                                    left blank.
                                </p>
                                <InputError message={errors.slug} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Create organization
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
