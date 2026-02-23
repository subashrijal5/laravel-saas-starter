<?php

use App\Models\Organization;
use App\Models\User;

test('authorized user can view organization members', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->get(route('organizations.members.index', $organization))
        ->assertOk();
});

test('non-member cannot view organization members', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $otherUser->id]);
    $organization->members()->attach($otherUser, ['role' => 'owner']);

    $this->actingAs($user)
        ->get(route('organizations.members.index', $organization))
        ->assertForbidden();
});

test('admin can update member role', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($admin, ['role' => 'admin']);
    $organization->members()->attach($member, ['role' => 'member']);
    $admin->switchOrganization($organization);

    $this->actingAs($admin)
        ->patch(route('organizations.members.update', [$organization, $member]), [
            'role' => 'admin',
        ])
        ->assertRedirect();

    expect($organization->userRole($member->fresh()))->toBe('admin');
});

test('cannot change owner role', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($admin, ['role' => 'admin']);
    $admin->switchOrganization($organization);

    $this->actingAs($admin)
        ->patch(route('organizations.members.update', [$organization, $owner]), [
            'role' => 'member',
        ])
        ->assertSessionHasErrors();
});

test('admin can remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $owner->switchOrganization($organization);

    $this->actingAs($owner)
        ->delete(route('organizations.members.destroy', [$organization, $member]))
        ->assertRedirect();

    expect($organization->hasUser($member))->toBeFalse();
});

test('owner cannot be removed', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($admin, ['role' => 'admin']);
    $admin->switchOrganization($organization);

    $this->actingAs($admin)
        ->delete(route('organizations.members.destroy', [$organization, $owner]))
        ->assertSessionHasErrors();
});

test('member cannot remove other members', function () {
    $owner = User::factory()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();

    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member1, ['role' => 'member']);
    $organization->members()->attach($member2, ['role' => 'member']);
    $member1->switchOrganization($organization);

    $this->actingAs($member1)
        ->delete(route('organizations.members.destroy', [$organization, $member2]))
        ->assertForbidden();
});
