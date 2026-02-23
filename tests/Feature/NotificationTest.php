<?php

use App\Models\Organization;
use App\Models\User;
use App\Notifications\PlanExpiringNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

function createNotificationForUser(User $user, array $data = [], bool $read = false): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => Str::uuid(),
        'type' => PlanExpiringNotification::class,
        'data' => array_merge([
            'type' => 'plan_expiring',
            'title' => 'Pro expires in 7 days',
            'body' => 'Your Pro plan is expiring soon.',
            'action_url' => '/billing',
            'action_label' => 'Manage Billing',
            'organization_id' => 1,
        ], $data),
        'read_at' => $read ? now() : null,
    ]);
}

test('guest cannot access notifications', function () {
    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view notifications page', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    createNotificationForUser($user);
    createNotificationForUser($user, ['title' => 'Second notification']);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('notifications.data', 2)
        );
});

test('notifications are paginated', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    for ($i = 0; $i < 20; $i++) {
        createNotificationForUser($user, ['title' => "Notification {$i}"]);
    }

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('notifications.data', 15)
            ->where('notifications.last_page', 2)
        );
});

test('user can mark a notification as read', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $notification = createNotificationForUser($user);

    expect($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->patch(route('notifications.read', $notification->id))
        ->assertRedirect();

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    createNotificationForUser($user);
    createNotificationForUser($user, ['title' => 'Another one']);

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('user can delete a notification', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $notification = createNotificationForUser($user);

    $this->actingAs($user)
        ->delete(route('notifications.destroy', $notification->id))
        ->assertRedirect();

    $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
});

test('user cannot read another user notification', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $notification = createNotificationForUser($otherUser);

    $this->actingAs($user)
        ->patch(route('notifications.read', $notification->id))
        ->assertNotFound();
});

test('user cannot delete another user notification', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $notification = createNotificationForUser($otherUser);

    $this->actingAs($user)
        ->delete(route('notifications.destroy', $notification->id))
        ->assertNotFound();

    $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
});

test('unread notifications count is shared via inertia', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    createNotificationForUser($user);
    createNotificationForUser($user, ['title' => 'Second']);
    createNotificationForUser($user, ['title' => 'Read one'], read: true);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('auth.notifications.unread_count', 2)
            ->has('auth.notifications.recent', 2)
        );
});
