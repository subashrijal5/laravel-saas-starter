<?php

use App\Models\Organization;
use App\Models\User;

test('organization owner can update organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->patch(route('organizations.update', $organization), [
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
        ])
        ->assertRedirect();

    expect($organization->fresh()->name)->toBe('Updated Name');
    expect($organization->fresh()->slug)->toBe('updated-slug');
});

test('admin can update organization', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($admin, ['role' => 'admin']);
    $admin->switchOrganization($organization);

    $this->actingAs($admin)
        ->patch(route('organizations.update', $organization), [
            'name' => 'Admin Updated',
        ])
        ->assertRedirect();

    expect($organization->fresh()->name)->toBe('Admin Updated');
});

test('member cannot update organization', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);

    $this->actingAs($member)
        ->patch(route('organizations.update', $organization), [
            'name' => 'Should Not Work',
        ])
        ->assertForbidden();
});

test('slug must be unique when updating', function () {
    $user = User::factory()->create();
    Organization::factory()->create(['slug' => 'existing-slug']);
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->patch(route('organizations.update', $organization), [
            'slug' => 'existing-slug',
        ])
        ->assertSessionHasErrors('slug');
});
