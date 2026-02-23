<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RemoveOrganizationMember
{
    public function __construct(
        private ClearOrganizationCache $clearCache,
    ) {}

    public function handle(Organization $organization, User $member): void
    {
        if ($organization->owner_id === $member->id) {
            throw ValidationException::withMessages([
                'member' => ['The organization owner cannot be removed.'],
            ]);
        }

        Log::info('Organization: Removing member', [
            'organization_id' => $organization->id,
            'member_id' => $member->id,
        ]);

        $organization->members()->detach($member);

        try {
            $this->clearCache->forUser($member);
        } catch (\Exception $e) {
            Log::warning('Organization: Cache clear failed after member removal', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($member->current_organization_id === $organization->id) {
            $fallback = $member->personalOrganization() ?? $member->organizations()->first();

            if ($fallback) {
                $member->switchOrganization($fallback);
            } else {
                $member->forceFill(['current_organization_id' => null])->save();
            }
        }
    }
}
