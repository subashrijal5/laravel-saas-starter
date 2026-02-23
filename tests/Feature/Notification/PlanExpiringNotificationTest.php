<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\PlanExpiringNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function createOrganizationWithTrialEndingIn(int $days): array
{
    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    Plan::query()->firstOrCreate(['key' => 'pro'], [
        'name' => 'Pro',
        'description' => 'For growing teams',
        'stripe_price_ids' => ['monthly' => 'price_pro_monthly', 'yearly' => 'price_pro_yearly'],
        'limits' => ['items' => 1000, 'ai_tokens' => 50000],
        'features' => ['Basic feature'],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    DB::table('subscriptions')->insert([
        'organization_id' => $organization->id,
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_pro_monthly',
        'trial_ends_at' => now()->addDays($days)->startOfDay(),
        'ends_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$owner, $organization];
}

test('command sends notification when trial is expiring within configured days', function () {
    Notification::fake();

    [$owner] = createOrganizationWithTrialEndingIn(7);

    $this->artisan('saas:check-expiring-plans')
        ->assertSuccessful();

    Notification::assertSentTo($owner, PlanExpiringNotification::class, function ($notification) {
        return $notification->daysRemaining === 7;
    });
});

test('command sends notification for multiple thresholds', function () {
    Notification::fake();

    [$owner7] = createOrganizationWithTrialEndingIn(7);
    [$owner3] = createOrganizationWithTrialEndingIn(3);
    [$owner1] = createOrganizationWithTrialEndingIn(1);

    $this->artisan('saas:check-expiring-plans')
        ->assertSuccessful();

    Notification::assertSentTo($owner7, PlanExpiringNotification::class);
    Notification::assertSentTo($owner3, PlanExpiringNotification::class);
    Notification::assertSentTo($owner1, PlanExpiringNotification::class);
});

test('command does not send notification when plan is not expiring soon', function () {
    Notification::fake();

    createOrganizationWithTrialEndingIn(30);

    $this->artisan('saas:check-expiring-plans')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('command does not send duplicate notifications', function () {
    Notification::fake();

    [$owner, $organization] = createOrganizationWithTrialEndingIn(7);

    $owner->notifications()->create([
        'id' => \Illuminate\Support\Str::uuid(),
        'type' => PlanExpiringNotification::class,
        'data' => [
            'type' => 'plan_expiring',
            'title' => 'Pro expires in 7 days',
            'body' => 'Your Pro plan is expiring soon.',
            'organization_id' => $organization->id,
            'days_remaining' => 7,
        ],
    ]);

    $this->artisan('saas:check-expiring-plans')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('command sends notification for subscription ending', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);
    $organization->members()->attach($owner, ['role' => 'owner']);
    $owner->switchOrganization($organization);

    Plan::factory()->pro()->create();

    DB::table('subscriptions')->insert([
        'organization_id' => $organization->id,
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro_monthly',
        'trial_ends_at' => null,
        'ends_at' => now()->addDays(3)->startOfDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('saas:check-expiring-plans')
        ->assertSuccessful();

    Notification::assertSentTo($owner, PlanExpiringNotification::class, function ($notification) {
        return $notification->daysRemaining === 3;
    });
});

test('notification contains correct data', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $owner->id]);

    Plan::factory()->free()->create();

    $notification = new PlanExpiringNotification($organization, 7);
    $data = $notification->toArray($owner);

    expect($data['type'])->toBe('plan_expiring');
    expect($data['days_remaining'])->toBe(7);
    expect($data['organization_id'])->toBe($organization->id);
    expect($data['action_url'])->toBe(route('billing.index'));
});
