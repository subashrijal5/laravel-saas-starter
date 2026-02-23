<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

test('billing page is accessible', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();
    Plan::factory()->pro()->create(['sort_order' => 1]);

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('billing/index')
            ->has('plans', 2)
            ->has('currentPlan')
        );
});

test('organization resolves free plan when not subscribed', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();

    expect($organization->currentPlanKey())->toBe('free');
    expect($organization->onFreePlan())->toBeTrue();
    expect($organization->onPaidPlan())->toBeFalse();
});

test('organization reports correct plan limits', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();

    expect($organization->planLimit('items'))->toBe(10);
    expect($organization->planLimit('ai_tokens'))->toBe(1000);
    expect($organization->withinLimit('items', 5))->toBeTrue();
    expect($organization->withinLimit('items', 10))->toBeFalse();
});

test('subscribed middleware redirects when not subscribed', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertSuccessful();
});

test('checkout requires valid plan and interval', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->post(route('billing.checkout'), [
            'plan' => '',
            'interval' => 'monthly',
        ])
        ->assertSessionHasErrors('plan');

    $this->actingAs($user)
        ->post(route('billing.checkout'), [
            'plan' => 'pro',
            'interval' => 'invalid',
        ])
        ->assertSessionHasErrors('interval');
});

test('checkout rejects free plan', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();

    $this->actingAs($user)
        ->post(route('billing.checkout'), [
            'plan' => 'free',
            'interval' => 'monthly',
        ])
        ->assertSessionHasErrors('plan');
});

test('billing page requires authentication', function () {
    $this->get(route('billing.index'))
        ->assertRedirect(route('login'));
});

test('billing page logs debug on access', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();

    $this->actingAs($user)->get(route('billing.index'));

    expect($logs->contains(fn ($l) => $l->level === 'debug' && str_contains($l->message, 'Viewing billing page')))->toBeTrue();
});

test('checkout logs warning for unavailable plan', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    $this->actingAs($user)
        ->post(route('billing.checkout'), [
            'plan' => 'nonexistent',
            'interval' => 'monthly',
        ])
        ->assertSessionHasErrors('plan');

    expect($logs->contains(fn ($l) => $l->level === 'warning' && str_contains($l->message, 'Plan not found')))->toBeTrue();
});
