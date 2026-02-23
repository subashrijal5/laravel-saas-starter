import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

export default function PermissionGate({
    permission,
    children,
    fallback = null,
}: {
    permission: string | string[];
    children: ReactNode;
    fallback?: ReactNode;
}) {
    const { auth } = usePage().props;
    const permissions = auth.organization_permissions;

    const required = Array.isArray(permission) ? permission : [permission];
    const hasPermission = required.every((p) => permissions.includes(p));

    if (!hasPermission) {
        return <>{fallback}</>;
    }

    return <>{children}</>;
}
