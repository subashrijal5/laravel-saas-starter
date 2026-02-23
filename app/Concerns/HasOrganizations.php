<?php

namespace App\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait HasOrganizations
{
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'current_organization_id');
    }

    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    /**
     * Resolve the current organization from cache or database.
     */
    public function resolveCurrentOrganization(): ?Organization
    {
        if (! $this->current_organization_id) {
            return null;
        }

        if (! config('saas.cache.enabled')) {
            return $this->currentOrganization;
        }

        $key = $this->organizationCacheKey('current_organization');
        $ttl = config('saas.cache.ttl', 3600);

        try {
            return Cache::remember($key, $ttl, fn () => $this->currentOrganization);
        } catch (\Exception $e) {
            Log::warning('Organization: Cache read failed for current organization, falling back to DB', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return $this->currentOrganization;
        }
    }

    /**
     * Resolve the user's permissions for the current organization from cache or database.
     *
     * @return list<string>
     */
    public function resolveOrganizationPermissions(): array
    {
        if (! $this->current_organization_id) {
            return [];
        }

        if (! config('saas.cache.enabled')) {
            return $this->computeOrganizationPermissions();
        }

        $key = $this->organizationCacheKey('org_permissions');
        $ttl = config('saas.cache.ttl', 3600);

        try {
            return Cache::remember($key, $ttl, fn () => $this->computeOrganizationPermissions());
        } catch (\Exception $e) {
            Log::warning('Organization: Cache read failed for permissions, falling back to computation', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return $this->computeOrganizationPermissions();
        }
    }

    /**
     * Check if the user has a specific permission on the given (or current) organization.
     */
    public function hasOrganizationPermission(string $permission, ?Organization $organization = null): bool
    {
        if ($organization && $organization->id !== $this->current_organization_id) {
            $role = $organization->userRole($this);

            return $role ? static::roleHasPermission($role, $permission) : false;
        }

        $permissions = $this->resolveOrganizationPermissions();

        return in_array($permission, $permissions, true);
    }

    /**
     * Get the user's role on the given (or current) organization.
     */
    public function organizationRole(?Organization $organization = null): ?string
    {
        $org = $organization ?? $this->resolveCurrentOrganization();

        return $org?->userRole($this);
    }

    /**
     * Switch the user's current organization.
     */
    public function switchOrganization(Organization $organization): void
    {
        $this->forceFill(['current_organization_id' => $organization->id])->save();

        $this->clearOrganizationCache();

        $this->setRelation('currentOrganization', $organization);
    }

    /**
     * Clear all organization-related cache for this user.
     */
    public function clearOrganizationCache(): void
    {
        if (! config('saas.cache.enabled')) {
            return;
        }

        try {
            Cache::forget($this->organizationCacheKey('current_organization'));
            Cache::forget($this->organizationCacheKey('org_permissions'));
        } catch (\Exception $e) {
            Log::warning('Organization: Failed to clear organization cache', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check whether a role in config has the given permission.
     */
    public static function roleHasPermission(string $role, string $permission): bool
    {
        $rolePermissions = config("saas.roles.{$role}.permissions", []);

        if (in_array('*', $rolePermissions, true)) {
            return true;
        }

        foreach ($rolePermissions as $granted) {
            if ($granted === $permission) {
                return true;
            }

            if (Str::endsWith($granted, ':*')) {
                $prefix = Str::beforeLast($granted, ':*');

                if (Str::startsWith($permission, $prefix.':')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Compute the full list of granted permissions for the user's current organization.
     *
     * @return list<string>
     */
    private function computeOrganizationPermissions(): array
    {
        $organization = $this->currentOrganization;

        if (! $organization) {
            return [];
        }

        $role = $organization->userRole($this);

        if (! $role) {
            return [];
        }

        $rolePermissions = config("saas.roles.{$role}.permissions", []);

        if (in_array('*', $rolePermissions, true)) {
            return array_keys(config('saas.permissions', []));
        }

        $allPermissions = array_keys(config('saas.permissions', []));
        $granted = [];

        foreach ($rolePermissions as $pattern) {
            if (Str::endsWith($pattern, ':*')) {
                $prefix = Str::beforeLast($pattern, ':*');

                foreach ($allPermissions as $perm) {
                    if (Str::startsWith($perm, $prefix.':')) {
                        $granted[] = $perm;
                    }
                }
            } elseif (in_array($pattern, $allPermissions, true)) {
                $granted[] = $pattern;
            }
        }

        return array_values(array_unique($granted));
    }

    private function organizationCacheKey(string $suffix): string
    {
        $prefix = config('saas.cache.prefix', 'saas');

        return "{$prefix}:user:{$this->id}:{$suffix}";
    }

    /**
     * Get the personal organization for this user.
     */
    public function personalOrganization(): ?Organization
    {
        return $this->ownedOrganizations()->where('personal_organization', true)->first();
    }
}
