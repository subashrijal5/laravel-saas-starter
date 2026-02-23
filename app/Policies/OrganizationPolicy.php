<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $organization->hasUser($user);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->hasOrganizationPermission('organization:update', $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return ! $organization->isPersonal()
            && $user->hasOrganizationPermission('organization:delete', $organization);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->hasOrganizationPermission('member:invite', $organization);
    }

    public function removeMembers(User $user, Organization $organization): bool
    {
        return $user->hasOrganizationPermission('member:remove', $organization);
    }

    public function updateMemberRoles(User $user, Organization $organization): bool
    {
        return $user->hasOrganizationPermission('member:update-role', $organization);
    }
}
