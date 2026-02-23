import { usePage } from '@inertiajs/react';
import { Bell, BookOpen, Folder, LayoutGrid, Settings } from 'lucide-react';
import { useMemo } from 'react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import OrganizationSwitcher from '@/components/organization-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import { dashboard } from '@/routes';
import { index as notificationsIndex } from '@/routes/notifications';
import { edit } from '@/routes/profile';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const unreadCount = auth.notifications?.unread_count ?? 0;

    const mainNavItems: NavItem[] = useMemo(
        () => [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Notifications',
                href: notificationsIndex(),
                icon: Bell,
                badge: unreadCount,
            },
            {
                title: 'Settings',
                href: edit(),
                icon: Settings,
            },
        ],
        [unreadCount],
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <OrganizationSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
