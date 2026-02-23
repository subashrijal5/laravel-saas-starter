<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CancelInvitation;
use App\Actions\Organization\InviteOrganizationMember;
use App\Actions\Organization\ResendInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationInvitationController extends Controller
{
    public function store(InviteMemberRequest $request, Organization $organization, InviteOrganizationMember $action): RedirectResponse
    {
        $action->handle($organization, $request->validated());

        return back();
    }

    public function update(Request $request, OrganizationInvitation $invitation, ResendInvitation $action): RedirectResponse
    {
        Gate::authorize('manageMembers', $invitation->organization);

        $action->handle($invitation);

        return back();
    }

    public function destroy(Request $request, OrganizationInvitation $invitation, CancelInvitation $action): RedirectResponse
    {
        Gate::authorize('manageMembers', $invitation->organization);

        $action->handle($invitation);

        return back();
    }
}
