<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcceptInvitationController extends Controller
{
    public function __invoke(Request $request, OrganizationInvitation $invitation, AcceptInvitation $action): RedirectResponse
    {
        $action->handle($request->user(), $invitation);

        return to_route('dashboard');
    }
}
