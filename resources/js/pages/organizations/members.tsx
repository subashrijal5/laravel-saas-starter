import { Head, router, usePage } from '@inertiajs/react';
import { RefreshCw, Trash2, X } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import Heading from '@/components/heading';
import InviteMemberDialog from '@/components/invite-member-dialog';
import MemberRoleSelect from '@/components/member-role-select';
import PermissionGate from '@/components/permission-gate';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type {
    AvailableRole,
    BreadcrumbItem,
    Organization,
    OrganizationInvitation,
    OrganizationMember,
} from '@/types';

const RESEND_COOLDOWN_MS = 60_000;

export default function OrganizationMembers({
    organization,
    members,
    invitations,
    availableRoles,
    permissions,
}: {
    organization: Organization;
    members: OrganizationMember[];
    invitations: OrganizationInvitation[];
    availableRoles: AvailableRole[];
    permissions: string[];
}) {
    const { auth } = usePage().props;
    const [cooldowns, setCooldowns] = useState<Record<string, number>>({});
    const timersRef = useRef<Record<string, ReturnType<typeof setTimeout>>>({});

    useEffect(() => {
        const timers = timersRef.current;
        return () => {
            Object.values(timers).forEach(clearTimeout);
        };
    }, []);

    const startCooldown = useCallback((invitationId: string) => {
        setCooldowns((prev) => ({ ...prev, [invitationId]: Date.now() + RESEND_COOLDOWN_MS }));

        timersRef.current[invitationId] = setTimeout(() => {
            setCooldowns((prev) => {
                const next = { ...prev };
                delete next[invitationId];
                return next;
            });
            delete timersRef.current[invitationId];
        }, RESEND_COOLDOWN_MS);
    }, []);

    const isOnCooldown = useCallback(
        (invitationId: string) => {
            const expiresAt = cooldowns[invitationId];
            return expiresAt !== undefined && Date.now() < expiresAt;
        },
        [cooldowns],
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Settings',
            href: `/organizations/${organization.id}/members`,
        },
    ];

    function updateRole(memberId: number, role: string) {
        router.patch(
            `/organizations/${organization.id}/members/${memberId}`,
            { role },
            { preserveScroll: true },
        );
    }

    function removeMember(memberId: number) {
        if (confirm('Are you sure you want to remove this member?')) {
            router.delete(
                `/organizations/${organization.id}/members/${memberId}`,
                { preserveScroll: true },
            );
        }
    }

    function cancelInvitation(invitationId: string) {
        router.delete(`/organizations/invitations/${invitationId}`, {
            preserveScroll: true,
        });
    }

    function resendInvitation(invitationId: string) {
        router.patch(
            `/organizations/invitations/${invitationId}`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => startCooldown(invitationId),
            },
        );
    }

    function invitationStatus(invitation: OrganizationInvitation) {
        if (
            invitation.expires_at &&
            new Date(invitation.expires_at) < new Date()
        ) {
            return 'expired';
        }
        return 'pending';
    }

    function initials(name: string): string {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${organization.name} — Members`} />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Members"
                            description={`Manage members of ${organization.name}.`}
                        />
                        <PermissionGate permission="member:invite">
                            <InviteMemberDialog
                                organization={organization}
                                availableRoles={availableRoles}
                            />
                        </PermissionGate>
                    </div>

                <div className="divide-y rounded-lg border">
                    {members.map((member) => (
                        <div
                            key={member.id}
                            className="flex items-center justify-between p-4"
                        >
                            <div className="flex items-center gap-3">
                                <Avatar className="size-9">
                                    <AvatarFallback className="text-xs">
                                        {initials(member.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm font-medium">
                                            {member.name}
                                        </span>
                                        {member.id === auth.user.id && (
                                            <Badge variant="secondary">
                                                You
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {member.email}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                {member.pivot.role === 'owner' ? (
                                    <Badge>Owner</Badge>
                                ) : (
                                    <>
                                        <PermissionGate permission="member:update-role">
                                            <MemberRoleSelect
                                                roles={availableRoles}
                                                value={member.pivot.role}
                                                onValueChange={(role) =>
                                                    updateRole(member.id, role)
                                                }
                                                disabled={
                                                    member.id === auth.user.id
                                                }
                                            />
                                        </PermissionGate>

                                        {!permissions.includes(
                                            'member:update-role',
                                        ) && (
                                            <Badge variant="outline">
                                                {member.pivot.role}
                                            </Badge>
                                        )}

                                        <PermissionGate permission="member:remove">
                                            {member.id !== auth.user.id && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        removeMember(member.id)
                                                    }
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            )}
                                        </PermissionGate>
                                    </>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                {invitations.length > 0 && (
                    <div className="space-y-4">
                        <Heading
                            variant="small"
                            title="Invitations"
                            description="People who have been invited to this organization."
                        />

                        <div className="divide-y rounded-lg border">
                            {invitations.map((invitation) => {
                                const status = invitationStatus(invitation);

                                return (
                                    <div
                                        key={invitation.id}
                                        className="flex items-center justify-between p-4"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <p className="text-sm font-medium">
                                                        {invitation.email}
                                                    </p>
                                                    <Badge
                                                        variant={
                                                            status ===
                                                            'expired'
                                                                ? 'destructive'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {status === 'expired'
                                                            ? 'Expired'
                                                            : 'Pending'}
                                                    </Badge>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    Invited as{' '}
                                                    <span className="font-medium">
                                                        {invitation.role}
                                                    </span>
                                                    {invitation.expires_at && (
                                                        <>
                                                            {' '}
                                                            &middot;{' '}
                                                            {status ===
                                                            'expired'
                                                                ? 'Expired'
                                                                : 'Expires'}{' '}
                                                            {new Date(
                                                                invitation.expires_at,
                                                            ).toLocaleDateString()}
                                                        </>
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                        <PermissionGate permission="member:invite">
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={isOnCooldown(
                                                        invitation.id,
                                                    )}
                                                    onClick={() =>
                                                        resendInvitation(
                                                            invitation.id,
                                                        )
                                                    }
                                                >
                                                    <RefreshCw className="mr-1 size-4" />
                                                    Resend
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Cancel invitation"
                                                    onClick={() =>
                                                        cancelInvitation(
                                                            invitation.id,
                                                        )
                                                    }
                                                >
                                                    <X className="size-4" />
                                                </Button>
                                            </div>
                                        </PermissionGate>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
