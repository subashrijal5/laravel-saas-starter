import type { AppNotification, Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            recent_notifications?: AppNotification[];
            [key: string]: unknown;
        };
    }
}
