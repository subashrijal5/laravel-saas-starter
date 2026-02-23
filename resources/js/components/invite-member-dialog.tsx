import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AvailableRole, Organization } from '@/types';
import { store } from '@/actions/App/Http/Controllers/Organization/OrganizationInvitationController';

export default function InviteMemberDialog({
    organization,
    availableRoles,
}: {
    organization: Organization;
    availableRoles: AvailableRole[];
}) {
    const [open, setOpen] = useState(false);
    const [role, setRole] = useState('member');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Invite member</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Invite a new member</DialogTitle>
                    <DialogDescription>
                        Send an invitation to join {organization.name}.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...store.form({ organization: organization.id })}
                    options={{
                        preserveScroll: true,
                        onSuccess: () => setOpen(false),
                    }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="invite-email">
                                    Email address
                                </Label>
                                <Input
                                    id="invite-email"
                                    type="email"
                                    name="email"
                                    placeholder="colleague@example.com"
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="invite-role">Role</Label>
                                <input type="hidden" name="role" value={role} />
                                <Select
                                    value={role}
                                    onValueChange={setRole}
                                >
                                    <SelectTrigger id="invite-role">
                                        <SelectValue placeholder="Select role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableRoles
                                            .filter((r) => r.key !== 'owner')
                                            .map((r) => (
                                                <SelectItem
                                                    key={r.key}
                                                    value={r.key}
                                                >
                                                    {r.label}
                                                </SelectItem>
                                            ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role} />
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Send invitation
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
