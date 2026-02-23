<?php

namespace App\Actions\Organization;

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    public function handle(User $user, OrganizationInvitation $invitation): void
    {
        Log::debug('Organization: Accepting invitation', [
            'user_id' => $user->id,
            'invitation_id' => $invitation->id,
            'organization_id' => $invitation->organization_id,
        ]);

        if ($invitation->isExpired()) {
            $invitation->delete();

            Log::warning('Organization: Attempted to accept expired invitation', [
                'invitation_id' => $invitation->id,
                'user_id' => $user->id,
            ]);

            throw ValidationException::withMessages([
                'invitation' => ['This invitation has expired.'],
            ]);
        }

        if ($invitation->email !== $user->email) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation does not belong to you.'],
            ]);
        }

        $organization = $invitation->organization;

        if ($organization->hasUser($user)) {
            $invitation->delete();

            throw ValidationException::withMessages([
                'invitation' => ['You are already a member of this organization.'],
            ]);
        }

        $organization->members()->attach($user, ['role' => $invitation->role]);

        $user->switchOrganization($organization);

        $invitation->delete();

        Log::info('Organization: Invitation accepted', [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => $invitation->role,
        ]);
    }
}
