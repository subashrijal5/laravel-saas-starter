<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\LeaveOrganization;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveOrganizationController extends Controller
{
    public function __invoke(Request $request, Organization $organization, LeaveOrganization $action): RedirectResponse
    {
        $action->handle($request->user(), $organization);

        return to_route('dashboard');
    }
}
