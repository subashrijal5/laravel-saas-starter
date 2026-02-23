<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganization;
use App\Actions\Organization\DeleteOrganization;
use App\Actions\Organization\UpdateOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('organizations/index', [
            'organizations' => $request->user()
                ->organizations()
                ->withPivot('role')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('organizations/create');
    }

    public function store(StoreOrganizationRequest $request, CreateOrganization $action): RedirectResponse
    {
        $organization = $action->handle(
            $request->user(),
            $request->validated(),
        );

        return to_route('organizations.show', $organization);
    }

    public function show(Request $request, Organization $organization): Response
    {
        Gate::authorize('view', $organization);

        return Inertia::render('organizations/settings', [
            'organization' => $organization,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, UpdateOrganization $action): RedirectResponse
    {
        $action->handle($organization, $request->validated());

        return back();
    }

    public function destroy(Request $request, Organization $organization, DeleteOrganization $action): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        $action->handle($organization);

        return to_route('dashboard');
    }
}
