<?php

use App\Models\Organization;
use App\Models\User;

test('organization owner can delete a non-personal organization', function () {
    $user = User::factory()->create();
    $personalOrg = Organization::factory()->personal()->create(['owner_id' => $user->id]);
    $personalOrg->members()->attach($user, ['role' => 'owner']);

    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->delete(route('organizations.destroy', $organization))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
});

test('personal organization cannot be deleted', function () {
    $user = User::factory()->create();
    $personalOrg = Organization::factory()->personal()->create(['owner_id' => $user->id]);
    $personalOrg->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($personalOrg);

    $this->actingAs($user)
        ->delete(route('organizations.destroy', $personalOrg))
        ->assertForbidden();

    $this->assertDatabaseHas('organizations', ['id' => $personalOrg->id]);
});

test('member cannot delete organization', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);

    $this->actingAs($member)
        ->delete(route('organizations.destroy', $organization))
        ->assertForbidden();
});

test('members are switched to fallback org when organization is deleted', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $personalOrg = Organization::factory()->personal()->create(['owner_id' => $member->id]);
    $personalOrg->members()->attach($member, ['role' => 'owner']);

    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);
    $owner->switchOrganization($organization);

    $this->actingAs($owner)
        ->delete(route('organizations.destroy', $organization));

    expect($member->fresh()->current_organization_id)->toBe($personalOrg->id);
});
