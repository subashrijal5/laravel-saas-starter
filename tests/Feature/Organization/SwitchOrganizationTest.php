<?php

use App\Models\Organization;
use App\Models\User;

test('user can switch to an organization they belong to', function () {
    $user = User::factory()->create();

    $org1 = Organization::factory()->create(['owner_id' => $user->id]);
    $org1->members()->attach($user, ['role' => 'owner']);

    $org2 = Organization::factory()->create(['owner_id' => $user->id]);
    $org2->members()->attach($user, ['role' => 'owner']);

    $user->switchOrganization($org1);

    $this->actingAs($user)
        ->post(route('organizations.switch', $org2))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->current_organization_id)->toBe($org2->id);
});

test('user cannot switch to an organization they do not belong to', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $organization = Organization::factory()->create(['owner_id' => $otherUser->id]);
    $organization->members()->attach($otherUser, ['role' => 'owner']);

    $this->actingAs($user)
        ->post(route('organizations.switch', $organization))
        ->assertSessionHasErrors();
});

test('user can leave an organization', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $personalOrg = Organization::factory()->personal()->create(['owner_id' => $member->id]);
    $personalOrg->members()->attach($member, ['role' => 'owner']);

    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);

    $this->actingAs($member)
        ->post(route('organizations.leave', $organization))
        ->assertRedirect(route('dashboard'));

    expect($organization->hasUser($member))->toBeFalse();
    expect($member->fresh()->current_organization_id)->toBe($personalOrg->id);
});

test('owner cannot leave their organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->post(route('organizations.leave', $organization))
        ->assertSessionHasErrors();
});
