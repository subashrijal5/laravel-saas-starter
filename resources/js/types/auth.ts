export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    current_organization_id: number | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Organization = {
    id: number;
    name: string;
    slug: string;
    personal_organization: boolean;
    owner_id: number;
    created_at: string;
    updated_at: string;
    pivot?: {
        role: string;
    };
};

export type OrganizationMember = User & {
    pivot: {
        role: string;
        organization_id: number;
        user_id: number;
    };
};

export type OrganizationInvitation = {
    id: string;
    organization_id: number;
    email: string;
    role: string;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
};

export type AvailableRole = {
    key: string;
    label: string;
    description: string;
};

export type BillingData = {
    plan: string;
    plan_name: string | null;
    limits: Record<string, number | null>;
    is_subscribed: boolean;
    is_on_trial: boolean;
    trial_ends_at: string | null;
};

export type PlanData = {
    id: number;
    key: string;
    name: string;
    description: string | null;
    stripe_price_ids: Record<string, string> | null;
    limits: Record<string, number | null> | null;
    features: string[] | null;
    sort_order: number;
    is_active: boolean;
};

export type NotificationData = {
    type: string;
    title: string;
    body: string;
    action_url?: string;
    action_label?: string;
    organization_id?: number;
    [key: string]: unknown;
};

export type AppNotification = {
    id: string;
    data: NotificationData;
    created_at: string;
    read_at: string | null;
};

export type NotificationsData = {
    unread_count: number;
    recent: AppNotification[];
};

export type Auth = {
    user: User;
    current_organization: Organization | null;
    organizations: Organization[];
    organization_permissions: string[];
    billing: BillingData | null;
    notifications: NotificationsData | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
