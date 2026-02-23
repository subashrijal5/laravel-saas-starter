<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'current_organization' => $user?->resolveCurrentOrganization(),
                'organizations' => $user?->organizations()
                    ->withPivot('role')
                    ->orderBy('name')
                    ->get() ?? [],
                'organization_permissions' => $user?->resolveOrganizationPermissions() ?? [],
                'billing' => $this->billingData($user?->currentOrganization),
                'notifications' => $this->notificationData($user),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array{unread_count: int, recent: list<array<string, mixed>>}|null
     */
    protected function notificationData(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => $user->unreadNotifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (DatabaseNotification $notification) => [
                    'id' => $notification->id,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at->toISOString(),
                    'read_at' => $notification->read_at?->toISOString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function billingData(?Organization $organization): ?array
    {
        if (! $organization) {
            return null;
        }

        return [
            'plan' => $organization->currentPlanKey(),
            'plan_name' => $organization->currentPlan()?->name,
            'limits' => $organization->currentPlan()?->limits ?? [],
            'is_subscribed' => $organization->subscribed(),
            'is_on_trial' => $organization->onTrial(),
            'trial_ends_at' => $organization->trialEndsAt()?->toISOString(),
        ];
    }
}
