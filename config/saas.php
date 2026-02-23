<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Organization Configuration
    |--------------------------------------------------------------------------
    */

    'organization' => [
        'label' => 'Organization',
        'personal_organization' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions
    |--------------------------------------------------------------------------
    |
    | Define the roles available in each organization and the permissions
    | granted to each role. Use '*' for all permissions or 'group:*' for
    | all permissions within a group (e.g. 'member:*').
    |
    */

    'roles' => [
        'owner' => [
            'label' => 'Owner',
            'description' => 'Full access to everything',
            'permissions' => ['*'],
        ],
        'admin' => [
            'label' => 'Admin',
            'description' => 'Can manage members and settings',
            'permissions' => [
                'organization:view',
                'organization:update',
                'member:*',
            ],
        ],
        'member' => [
            'label' => 'Member',
            'description' => 'Basic access',
            'permissions' => [
                'organization:view',
                'member:view',
            ],
        ],
    ],

    'permissions' => [
        'organization:view' => 'View organization details',
        'organization:update' => 'Update organization settings',
        'organization:delete' => 'Delete the organization',
        'member:view' => 'View members',
        'member:invite' => 'Invite members',
        'member:remove' => 'Remove members',
        'member:update-role' => 'Change member roles',
    ],

    'default_role' => 'member',

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    */

    'invitations' => [
        'enabled' => true,
        'expiry_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache the current organization and resolved permissions per user
    | to avoid repeated database lookups on every request.
    |
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'saas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    |
    | Define plans, pricing, and metered billing. Run `php artisan saas:sync`
    | to create or update Stripe products, prices, and meters from this config.
    |
    */

    'billing' => [
        'currency' => env('CASHIER_CURRENCY', 'usd'),
        'trial_days' => 14,

        'plans' => [
            'free' => [
                'name' => 'Free',
                'description' => 'Get started for free',
                'features' => ['10 items', '1,000 AI tokens/mo', 'Community support'],
                'limits' => ['items' => 10, 'ai_tokens' => 1000],
            ],
            'pro' => [
                'name' => 'Pro',
                'description' => 'For growing teams',
                'prices' => [
                    'monthly' => 2900,
                    'yearly' => 29000,
                ],
                'features' => ['1,000 items', '50,000 AI tokens/mo', 'Priority support'],
                'limits' => ['items' => 1000, 'ai_tokens' => 50000],
                'metered' => [
                    'ai_tokens_extra' => [
                        'meter' => 'ai_tokens',
                        'unit_amount' => 1,
                    ],
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'description' => 'For large organizations',
                'prices' => [
                    'monthly' => 9900,
                    'yearly' => 99000,
                ],
                'features' => ['Unlimited items', 'Unlimited AI tokens', 'Dedicated support'],
                'limits' => ['items' => null, 'ai_tokens' => null],
            ],
        ],

        'meters' => [
            'ai_tokens' => [
                'display_name' => 'AI Token Usage',
                'event_name' => 'ai_tokens_used',
            ],
        ],
    ],

];
