import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    CheckCheck,
    CreditCard,
    MailOpen,
    TrendingUp,
    UserPlus,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { index as notificationsIndex } from '@/routes/notifications';
import {
    markAsRead,
    markAllAsRead,
} from '@/actions/App/Http/Controllers/NotificationController';
import type { AppNotification } from '@/types';

function notificationIcon(type: string) {
    switch (type) {
        case 'invitation_received':
            return <UserPlus className="size-4 shrink-0 text-blue-500" />;
        case 'plan_expiring':
            return <CreditCard className="size-4 shrink-0 text-amber-500" />;
        case 'usage_approaching_limit':
            return <TrendingUp className="size-4 shrink-0 text-red-500" />;
        default:
            return <Bell className="size-4 shrink-0 text-muted-foreground" />;
    }
}

function timeAgo(dateString: string): string {
    const now = new Date();
    const date = new Date(dateString);
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);
    if (days < 7) {
        return `${days}d ago`;
    }

    return date.toLocaleDateString();
}

function NotificationItem({
    notification,
    onClose,
}: {
    notification: AppNotification;
    onClose: () => void;
}) {
    const handleClick = () => {
        router.patch(markAsRead.url(notification.id), {}, { preserveScroll: true });
        if (notification.data.action_url) {
            onClose();
            router.visit(notification.data.action_url);
        }
    };

    return (
        <button
            type="button"
            onClick={handleClick}
            className="flex w-full items-start gap-3 rounded-md p-3 text-left transition-colors hover:bg-accent"
        >
            <div className="mt-0.5">
                {notificationIcon(notification.data.type)}
            </div>
            <div className="min-w-0 flex-1">
                <p className="text-sm font-medium leading-tight">
                    {notification.data.title}
                </p>
                <p className="mt-0.5 text-xs text-muted-foreground line-clamp-2">
                    {notification.data.body}
                </p>
                <p className="mt-1 text-xs text-muted-foreground/70">
                    {timeAgo(notification.created_at)}
                </p>
            </div>
            <span className="mt-1 size-2 shrink-0 rounded-full bg-blue-500" />
        </button>
    );
}

export function NotificationBell() {
    const { auth } = usePage().props;
    const [open, setOpen] = useState(false);

    const notifications = auth.notifications;
    const unreadCount = notifications?.unread_count ?? 0;
    const recentNotifications = notifications?.recent ?? [];

    const handleMarkAllRead = () => {
        router.post(markAllAsRead.url(), {}, { preserveScroll: true });
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-9 w-9"
                >
                    <Bell className="size-5 opacity-80" />
                    {unreadCount > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between px-4 py-3">
                    <h4 className="text-sm font-semibold">Notifications</h4>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={handleMarkAllRead}
                            className="flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <CheckCheck className="size-3.5" />
                            Mark all read
                        </button>
                    )}
                </div>
                <Separator />
                <div className="max-h-80 overflow-y-auto">
                    {recentNotifications.length > 0 ? (
                        <div className="p-1">
                            {recentNotifications.map((notification) => (
                                <NotificationItem
                                    key={notification.id}
                                    notification={notification}
                                    onClose={() => setOpen(false)}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center gap-2 py-8 text-center">
                            <MailOpen className="size-8 text-muted-foreground/50" />
                            <p className="text-sm text-muted-foreground">
                                No new notifications
                            </p>
                        </div>
                    )}
                </div>
                <Separator />
                <div className="p-2">
                    <Link
                        href={notificationsIndex().url}
                        onClick={() => setOpen(false)}
                        className="block w-full rounded-md px-3 py-2 text-center text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    >
                        View all notifications
                    </Link>
                </div>
            </PopoverContent>
        </Popover>
    );
}
