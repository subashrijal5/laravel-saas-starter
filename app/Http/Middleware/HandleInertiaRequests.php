<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Illuminate\Http\Request;
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
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
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
