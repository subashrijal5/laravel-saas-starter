<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UpdateMemberRole
{
    public function __construct(
        private ClearOrganizationCache $clearCache,
    ) {}

    /**
     * @param  array{role: string}  $data
     */
    public function handle(Organization $organization, User $member, array $data): void
    {
        if ($organization->owner_id === $member->id) {
            throw ValidationException::withMessages([
                'role' => ['The organization owner role cannot be changed.'],
            ]);
        }

        if (! array_key_exists($data['role'], config('saas.roles', []))) {
            throw ValidationException::withMessages([
                'role' => ['The selected role is invalid.'],
            ]);
        }

        if ($data['role'] === 'owner') {
            throw ValidationException::withMessages([
                'role' => ['Cannot assign the owner role.'],
            ]);
        }

        $oldRole = $organization->members()->where('user_id', $member->id)->first()?->pivot?->role;

        $organization->members()->updateExistingPivot($member->id, [
            'role' => $data['role'],
        ]);

        $this->clearCache->forUser($member);

        Log::info('Organization: Member role updated', [
            'organization_id' => $organization->id,
            'member_id' => $member->id,
            'old_role' => $oldRole,
            'new_role' => $data['role'],
        ]);
    }
}
