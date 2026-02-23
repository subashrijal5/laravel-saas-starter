import { Head, Link, router } from '@inertiajs/react';
import {
    Bell,
    CheckCheck,
    CreditCard,
    MailOpen,
    Trash2,
    TrendingUp,
    UserPlus,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import {
    markAsRead,
    markAllAsRead,
    destroy,
} from '@/actions/App/Http/Controllers/NotificationController';
import { index as notificationsIndex } from '@/routes/notifications';
import type { AppNotification, BreadcrumbItem } from '@/types';

type PaginatedNotifications = {
    data: AppNotification[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    per_page: number;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: notificationsIndex().url },
];

function notificationIcon(type: string) {
    switch (type) {
        case 'invitation_received':
            return <UserPlus className="size-5 text-blue-500" />;
        case 'plan_expiring':
            return <CreditCard className="size-5 text-amber-500" />;
        case 'usage_approaching_limit':
            return <TrendingUp className="size-5 text-red-500" />;
        default:
            return <Bell className="size-5 text-muted-foreground" />;
    }
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMinutes < 1) {
        return 'Just now';
    }
    if (diffMinutes < 60) {
        return `${diffMinutes} minute${diffMinutes !== 1 ? 's' : ''} ago`;
    }
    if (diffHours < 24) {
        return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
    }
    if (diffDays < 7) {
        return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
    });
}

function NotificationRow({ notification }: { notification: AppNotification }) {
    const isUnread = notification.read_at === null;

    const handleClick = () => {
        if (isUnread) {
            router.patch(markAsRead.url(notification.id), {}, { preserveScroll: true });
        }
        if (notification.data.action_url) {
            router.visit(notification.data.action_url);
        }
    };

    const handleDelete = (e: React.MouseEvent) => {
        e.stopPropagation();
        router.delete(destroy.url(notification.id), { preserveScroll: true });
    };

    const handleMarkRead = (e: React.MouseEvent) => {
        e.stopPropagation();
        router.patch(markAsRead.url(notification.id), {}, { preserveScroll: true });
    };

    return (
        <div
            role="button"
            tabIndex={0}
            onClick={handleClick}
            onKeyDown={(e) => {
                if (e.key === 'Enter') {
                    handleClick();
                }
            }}
            className={`flex items-start gap-4 rounded-lg border p-4 transition-colors hover:bg-accent ${
                isUnread
                    ? 'border-blue-500/20 bg-blue-50/50 dark:border-blue-500/10 dark:bg-blue-950/20'
                    : 'border-sidebar-border/70 dark:border-sidebar-border'
            }`}
        >
            <div className="mt-0.5 shrink-0">
                {notificationIcon(notification.data.type)}
            </div>
            <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                        <p className={`text-sm leading-tight ${isUnread ? 'font-semibold' : 'font-medium'}`}>
                            {notification.data.title}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {notification.data.body}
                        </p>
                        <p className="mt-1.5 text-xs text-muted-foreground/70">
                            {formatDate(notification.created_at)}
                        </p>
                    </div>
                    <div className="flex shrink-0 items-center gap-1">
                        {isUnread && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                title="Mark as read"
                                onClick={handleMarkRead}
                            >
                                <CheckCheck className="size-4" />
                            </Button>
                        )}
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            title="Delete"
                            onClick={handleDelete}
                        >
                            <Trash2 className="size-4 text-destructive" />
                        </Button>
                    </div>
                </div>
                {notification.data.action_url && notification.data.action_label && (
                    <Link
                        href={notification.data.action_url}
                        className="mt-2 inline-block text-sm font-medium text-primary hover:underline"
                        onClick={(e) => e.stopPropagation()}
                    >
                        {notification.data.action_label}
                    </Link>
                )}
            </div>
            {isUnread && (
                <span className="mt-2 size-2 shrink-0 rounded-full bg-blue-500" />
            )}
        </div>
    );
}

export default function NotificationsIndex({
    notifications,
}: {
    notifications: PaginatedNotifications;
}) {
    const hasUnread = notifications.data.some((n) => n.read_at === null);

    const handleMarkAllRead = () => {
        router.post(markAllAsRead.url(), {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Notifications"
                        description="Stay updated on your organization activity."
                    />
                    {hasUnread && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleMarkAllRead}
                        >
                            <CheckCheck className="mr-1.5 size-4" />
                            Mark all as read
                        </Button>
                    )}
                </div>

                {notifications.data.length > 0 ? (
                    <div className="space-y-3">
                        {notifications.data.map((notification) => (
                            <NotificationRow
                                key={notification.id}
                                notification={notification}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-col items-center gap-3 rounded-lg border border-sidebar-border/70 py-16 dark:border-sidebar-border">
                        <MailOpen className="size-12 text-muted-foreground/40" />
                        <p className="text-lg font-medium text-muted-foreground">
                            No notifications yet
                        </p>
                        <p className="text-sm text-muted-foreground/70">
                            You&apos;ll see notifications here when something
                            important happens.
                        </p>
                    </div>
                )}

                {notifications.last_page > 1 && (
                    <div className="flex items-center justify-between pt-4">
                        {notifications.prev_page_url ? (
                            <Link
                                href={notifications.prev_page_url}
                                className="text-sm font-medium text-primary hover:underline"
                            >
                                Previous
                            </Link>
                        ) : (
                            <span />
                        )}
                        <span className="text-sm text-muted-foreground">
                            Page {notifications.current_page} of{' '}
                            {notifications.last_page}
                        </span>
                        {notifications.next_page_url ? (
                            <Link
                                href={notifications.next_page_url}
                                className="text-sm font-medium text-primary hover:underline"
                            >
                                Next
                            </Link>
                        ) : (
                            <span />
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
