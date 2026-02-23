<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DeleteOrganization
{
    public function __construct(
        private ClearOrganizationCache $clearCache,
    ) {}

    public function handle(Organization $organization): void
    {
        if ($organization->isPersonal()) {
            throw ValidationException::withMessages([
                'organization' => ['Cannot delete a personal organization.'],
            ]);
        }

        Log::info('Organization: Deleting', [
            'organization_id' => $organization->id,
            'name' => $organization->name,
        ]);

        try {
            $this->clearCache->forOrganization($organization);
        } catch (\Exception $e) {
            Log::warning('Organization: Cache clear failed during deletion', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }

        $members = $organization->members()->get();

        $organization->members()->detach();
        $organization->invitations()->delete();
        $organization->delete();

        foreach ($members as $member) {
            if ($member->current_organization_id === $organization->id) {
                $fallback = $member->personalOrganization() ?? $member->organizations()->first();

                if ($fallback) {
                    $member->switchOrganization($fallback);
                } else {
                    $member->forceFill(['current_organization_id' => null])->save();
                }
            }
        }

        Log::info('Organization: Deleted', [
            'organization_id' => $organization->id,
            'members_affected' => $members->count(),
        ]);
    }
}
