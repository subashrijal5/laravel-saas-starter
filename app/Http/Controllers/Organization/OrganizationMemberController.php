<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\RemoveOrganizationMember;
use App\Actions\Organization\UpdateMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateMemberRoleRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationMemberController extends Controller
{
    public function index(Request $request, Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        return Inertia::render('organizations/members', [
            'organization' => $organization,
            'members' => $organization->members()->orderBy('name')->get(),
            'invitations' => $organization->invitations()->orderBy('created_at', 'desc')->get(),
            'availableRoles' => collect(config('saas.roles'))
                ->map(fn (array $role, string $key) => [
                    'key' => $key,
                    'label' => $role['label'],
                    'description' => $role['description'] ?? '',
                ])
                ->values(),
            'permissions' => $request->user()->resolveOrganizationPermissions(),
        ]);
    }

    public function update(UpdateMemberRoleRequest $request, Organization $organization, User $user, UpdateMemberRole $action): RedirectResponse
    {
        $action->handle($organization, $user, $request->validated());

        return back();
    }

    public function destroy(Request $request, Organization $organization, User $user, RemoveOrganizationMember $action): RedirectResponse
    {
        Gate::authorize('removeMembers', $organization);

        $action->handle($organization, $user);

        return back();
    }
}
