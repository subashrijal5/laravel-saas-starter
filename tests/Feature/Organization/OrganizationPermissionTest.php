<?php

use App\Models\Organization;
use App\Models\User;

test('owner has all permissions', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $permissions = $user->resolveOrganizationPermissions();

    expect($permissions)->toContain('organization:view');
    expect($permissions)->toContain('organization:update');
    expect($permissions)->toContain('organization:delete');
    expect($permissions)->toContain('member:view');
    expect($permissions)->toContain('member:invite');
    expect($permissions)->toContain('member:remove');
    expect($permissions)->toContain('member:update-role');
});

test('admin has correct permissions', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($admin, ['role' => 'admin']);
    $admin->switchOrganization($organization);

    $permissions = $admin->resolveOrganizationPermissions();

    expect($permissions)->toContain('organization:view');
    expect($permissions)->toContain('organization:update');
    expect($permissions)->toContain('member:view');
    expect($permissions)->toContain('member:invite');
    expect($permissions)->toContain('member:remove');
    expect($permissions)->toContain('member:update-role');
    expect($permissions)->not->toContain('organization:delete');
});

test('member has limited permissions', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);

    $permissions = $member->resolveOrganizationPermissions();

    expect($permissions)->toContain('organization:view');
    expect($permissions)->toContain('member:view');
    expect($permissions)->not->toContain('organization:update');
    expect($permissions)->not->toContain('organization:delete');
    expect($permissions)->not->toContain('member:invite');
    expect($permissions)->not->toContain('member:remove');
});

test('wildcard permission matching works', function () {
    $user = User::factory()->create();

    expect(User::roleHasPermission('owner', 'organization:view'))->toBeTrue();
    expect(User::roleHasPermission('owner', 'member:invite'))->toBeTrue();
    expect(User::roleHasPermission('admin', 'member:invite'))->toBeTrue();
    expect(User::roleHasPermission('admin', 'member:remove'))->toBeTrue();
    expect(User::roleHasPermission('admin', 'organization:delete'))->toBeFalse();
    expect(User::roleHasPermission('member', 'member:invite'))->toBeFalse();
});

test('hasOrganizationPermission checks current org permissions', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);

    expect($member->hasOrganizationPermission('organization:view'))->toBeTrue();
    expect($member->hasOrganizationPermission('member:invite'))->toBeFalse();
    expect($member->hasOrganizationPermission('organization:delete'))->toBeFalse();
});

test('permission check against specific organization works', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();

    $org1 = Organization::factory()->create(['owner_id' => $owner->id]);
    $org1->members()->attach($owner, ['role' => 'owner']);
    $org1->members()->attach($user, ['role' => 'admin']);

    $org2 = Organization::factory()->create(['owner_id' => $owner->id]);
    $org2->members()->attach($owner, ['role' => 'owner']);
    $org2->members()->attach($user, ['role' => 'member']);

    $user->switchOrganization($org1);

    expect($user->hasOrganizationPermission('member:invite', $org1))->toBeTrue();
    expect($user->hasOrganizationPermission('member:invite', $org2))->toBeFalse();
});

test('cache is cleared after switching organization', function () {
    $user = User::factory()->create();

    $org1 = Organization::factory()->create(['owner_id' => $user->id]);
    $org1->members()->attach($user, ['role' => 'owner']);

    $org2 = Organization::factory()->create(['owner_id' => $user->id]);
    $org2->members()->attach($user, ['role' => 'owner']);

    $user->switchOrganization($org1);
    $resolved1 = $user->resolveCurrentOrganization();
    expect($resolved1->id)->toBe($org1->id);

    $user->switchOrganization($org2);
    $resolved2 = $user->resolveCurrentOrganization();
    expect($resolved2->id)->toBe($org2->id);
});
