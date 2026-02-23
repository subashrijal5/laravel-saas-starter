<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SwitchCurrentOrganization
{
    public function handle(User $user, Organization $organization): void
    {
        if (! $organization->hasUser($user)) {
            throw ValidationException::withMessages([
                'organization' => ['You are not a member of this organization.'],
            ]);
        }

        Log::debug('Organization: Switching current organization', [
            'user_id' => $user->id,
            'from_organization_id' => $user->current_organization_id,
            'to_organization_id' => $organization->id,
        ]);

        $user->switchOrganization($organization);
    }
}
