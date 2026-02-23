<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ClearOrganizationCache
{
    /**
     * Clear organization cache for a single user.
     */
    public function forUser(User $user): void
    {
        try {
            $user->clearOrganizationCache();
            Log::debug('Organization: Cleared cache for user', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::warning('Organization: Failed to clear cache for user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear organization cache for all members of an organization.
     */
    public function forOrganization(Organization $organization): void
    {
        try {
            $organization->members->each(fn (User $member) => $member->clearOrganizationCache());
            Log::debug('Organization: Cleared cache for organization members', [
                'organization_id' => $organization->id,
            ]);
        } catch (\Exception $e) {
            Log::warning('Organization: Failed to clear cache for organization members', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
