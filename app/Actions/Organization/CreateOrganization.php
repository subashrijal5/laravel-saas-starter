<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateOrganization
{
    /**
     * @param  array{name: string, slug?: string, personal_organization?: bool}  $data
     */
    public function handle(User $user, array $data): Organization
    {
        Log::debug('Organization: Creating organization', [
            'user_id' => $user->id,
            'name' => $data['name'],
            'personal' => $data['personal_organization'] ?? false,
        ]);

        $organization = Organization::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'personal_organization' => $data['personal_organization'] ?? false,
            'owner_id' => $user->id,
        ]);

        $organization->members()->attach($user, ['role' => 'owner']);

        $user->switchOrganization($organization);

        Log::info('Organization: Created', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'name' => $organization->name,
        ]);

        return $organization;
    }
}
