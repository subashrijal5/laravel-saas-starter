<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LeaveOrganization
{
    public function __construct(
        private ClearOrganizationCache $clearCache,
    ) {}

    public function handle(User $user, Organization $organization): void
    {
        if ($organization->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'organization' => ['The organization owner cannot leave. Transfer ownership or delete the organization instead.'],
            ]);
        }

        if ($organization->isPersonal()) {
            throw ValidationException::withMessages([
                'organization' => ['You cannot leave your personal organization.'],
            ]);
        }

        Log::info('Organization: Member leaving', [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $organization->members()->detach($user);

        $this->clearCache->forUser($user);

        if ($user->current_organization_id === $organization->id) {
            $fallback = $user->personalOrganization() ?? $user->organizations()->first();

            if ($fallback) {
                $user->switchOrganization($fallback);
            } else {
                $user->forceFill(['current_organization_id' => null])->save();
            }
        }
    }
}
