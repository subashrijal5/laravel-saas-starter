<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
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
                'notifications' => $this->notificationSummary($user),
            ],
            'recent_notifications' => $user
                ? Inertia::defer(fn () => $this->recentNotifications($user))
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Lightweight count cached for 60 seconds — safe for every request.
     *
     * @return array{unread_count: int}|null
     */
    protected function notificationSummary(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $count = Cache::remember(
            "notifications:unread:{$user->id}",
            60,
            fn () => $user->unreadNotifications()->count(),
        );

        return ['unread_count' => $count];
    }

    /**
     * Fetched via a deferred request after initial page render.
     *
     * @return list<array<string, mixed>>
     */
    protected function recentNotifications(User $user): array
    {
        return $user->unreadNotifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'data' => $notification->data,
                'created_at' => $notification->created_at->toISOString(),
                'read_at' => $notification->read_at?->toISOString(),
            ])
            ->all();
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

    public static function clearNotificationCache(int $userId): void
    {
        Cache::forget("notifications:unread:{$userId}");
    }
}
