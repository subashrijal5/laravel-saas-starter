<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class UpdateOrganization
{
    public function __construct(
        private ClearOrganizationCache $clearCache,
    ) {}

    /**
     * @param  array{name?: string, slug?: string}  $data
     */
    public function handle(Organization $organization, array $data): Organization
    {
        Log::debug('Organization: Updating', [
            'organization_id' => $organization->id,
            'changes' => array_keys($data),
        ]);

        $organization->update($data);

        try {
            $this->clearCache->forOrganization($organization);
        } catch (\Exception $e) {
            Log::warning('Organization: Cache clear failed after update', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $organization;
    }
}
