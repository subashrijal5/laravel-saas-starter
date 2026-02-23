<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\UsageApproachingLimitNotification;
use Illuminate\Support\Facades\Notification;

test('command sends notification when usage crosses threshold', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    Plan::factory()->free()->create(['limits' => ['items' => 2]]);

    $extraMembers = User::factory()->count(1)->create();
    foreach ($extraMembers as $member) {
        $organization->members()->attach($member, ['role' => 'member']);
    }

    $this->artisan('saas:check-usage-limits')
        ->assertSuccessful();

    Notification::assertSentTo($owner, UsageApproachingLimitNotification::class, function ($notification) {
        return $notification->percentage === 80 || $notification->percentage === 90;
    });
});

test('command does not send notification when usage is below thresholds', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    Plan::factory()->free()->create(['limits' => ['items' => 100]]);

    $this->artisan('saas:check-usage-limits')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('command does not send duplicate notifications within same month', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    Plan::factory()->free()->create(['limits' => ['items' => 2]]);

    $extraMember = User::factory()->create();
    $organization->members()->attach($extraMember, ['role' => 'member']);

    $owner->notifications()->create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => UsageApproachingLimitNotification::class,
        'data' => [
            'type' => 'usage_approaching_limit',
            'title' => '80% of items limit reached',
            'body' => 'Test body',
            'organization_id' => $organization->id,
            'feature' => 'items',
            'percentage' => 80,
        ],
        'created_at' => now(),
    ]);

    $owner->notifications()->create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => UsageApproachingLimitNotification::class,
        'data' => [
            'type' => 'usage_approaching_limit',
            'title' => '90% of items limit reached',
            'body' => 'Test body',
            'organization_id' => $organization->id,
            'feature' => 'items',
            'percentage' => 90,
        ],
        'created_at' => now(),
    ]);

    $this->artisan('saas:check-usage-limits')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('command skips features with unlimited limits', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    Plan::factory()->enterprise()->create();

    $this->artisan('saas:check-usage-limits')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('notification contains correct data', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);

    $notification = new UsageApproachingLimitNotification(
        $organization,
        'items',
        8,
        10,
        80,
    );

    $data = $notification->toArray($owner);

    expect($data['type'])->toBe('usage_approaching_limit');
    expect($data['feature'])->toBe('items');
    expect($data['current_usage'])->toBe(8);
    expect($data['limit'])->toBe(10);
    expect($data['percentage'])->toBe(80);
    expect($data['organization_id'])->toBe($organization->id);
    expect($data['action_url'])->toBe(route('billing.index'));
});
