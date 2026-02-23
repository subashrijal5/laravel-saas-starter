<?php

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Notification;

test('admin can invite a member', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    $this->actingAs($owner)
        ->post(route('organizations.invitations.store', $organization), [
            'email' => 'new@example.com',
            'role' => 'member',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('organization_invitations', [
        'organization_id' => $organization->id,
        'email' => 'new@example.com',
        'role' => 'member',
    ]);

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);
});

test('existing user receives notification when invited', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'existing@example.com']);
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    $this->actingAs($owner)
        ->post(route('organizations.invitations.store', $organization), [
            'email' => 'existing@example.com',
            'role' => 'member',
        ]);

    Notification::assertSentTo($invitee, OrganizationInvitationNotification::class);
});

test('cannot invite an existing member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $owner->switchOrganization($organization);

    $this->actingAs($owner)
        ->post(route('organizations.invitations.store', $organization), [
            'email' => $member->email,
            'role' => 'member',
        ])
        ->assertSessionHasErrors('email');
});

test('cannot invite with duplicate email', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'already@example.com',
    ]);

    $this->actingAs($owner)
        ->post(route('organizations.invitations.store', $organization), [
            'email' => 'already@example.com',
            'role' => 'member',
        ])
        ->assertSessionHasErrors('email');
});

test('user can accept an invitation', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'invitee@example.com',
        'role' => 'member',
    ]);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', $invitation))
        ->assertRedirect(route('dashboard'));

    expect($organization->hasUser($invitee))->toBeTrue();
    expect($organization->userRole($invitee))->toBe('member');
    expect($invitee->fresh()->current_organization_id)->toBe($organization->id);
    $this->assertDatabaseMissing('organization_invitations', ['id' => $invitation->id]);
});

test('user cannot accept invitation for different email', function () {
    $owner = User::factory()->create();
    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'correct@example.com',
        'role' => 'member',
    ]);

    $this->actingAs($wrongUser)
        ->get(route('invitations.accept', $invitation))
        ->assertSessionHasErrors();
});

test('admin can cancel an invitation', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($owner)
        ->delete(route('organizations.invitations.destroy', $invitation))
        ->assertRedirect();

    $this->assertDatabaseMissing('organization_invitations', ['id' => $invitation->id]);
});

test('expired invitation cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'expired@example.com']);
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);

    $invitation = OrganizationInvitation::factory()->expired()->create([
        'organization_id' => $organization->id,
        'email' => 'expired@example.com',
        'role' => 'member',
    ]);

    $this->actingAs($invitee)
        ->get(route('invitations.accept', $invitation))
        ->assertSessionHasErrors('invitation');

    expect($organization->hasUser($invitee))->toBeFalse();
    $this->assertDatabaseMissing('organization_invitations', ['id' => $invitation->id]);
});

test('invitation has expires_at set from config', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    $this->actingAs($owner)
        ->post(route('organizations.invitations.store', $organization), [
            'email' => 'timed@example.com',
            'role' => 'member',
        ]);

    $invitation = OrganizationInvitation::query()->where('email', 'timed@example.com')->first();
    expect($invitation->expires_at)->not->toBeNull();
    expect(now()->diffInDays($invitation->expires_at, false))->toBeBetween(6, 7);
});

test('admin can resend an invitation', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'resend@example.com',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($owner)
        ->patch(route('organizations.invitations.resend', $invitation))
        ->assertRedirect();

    $invitation->refresh();
    expect($invitation->expires_at->isFuture())->toBeTrue();

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);
});

test('resend invitation sends to existing user', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'existing-resend@example.com']);
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'existing-resend@example.com',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($owner)
        ->patch(route('organizations.invitations.resend', $invitation))
        ->assertRedirect();

    Notification::assertSentTo($invitee, OrganizationInvitationNotification::class);
});

test('member cannot resend invitation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs($member)
        ->patch(route('organizations.invitations.resend', $invitation))
        ->assertForbidden();
});

test('member cannot invite others', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $organization->members()->attach($member, ['role' => 'member']);
    $member->switchOrganization($organization);

    $this->actingAs($member)
        ->post(route('organizations.invitations.store', $organization), [
            'email' => 'new@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();
});
