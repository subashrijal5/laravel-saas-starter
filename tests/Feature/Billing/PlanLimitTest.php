<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

test('within limit returns true when under limit', function () {
    $organization = Organization::factory()->create();
    Plan::factory()->free()->create();

    expect($organization->withinLimit('items', 5))->toBeTrue();
});

test('within limit returns false when at limit', function () {
    $organization = Organization::factory()->create();
    Plan::factory()->free()->create();

    expect($organization->withinLimit('items', 10))->toBeFalse();
});

test('within limit returns true when limit is null (unlimited)', function () {
    $organization = Organization::factory()->create();
    Plan::factory()->enterprise()->create();

    $organization->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => 'price_ent_monthly',
    ]);

    expect($organization->withinLimit('items', 999999))->toBeTrue();
    expect($organization->planLimit('items'))->toBeNull();
});

test('exceeds limit returns correct value', function () {
    $organization = Organization::factory()->create();
    Plan::factory()->free()->create();

    expect($organization->exceedsLimit('items', 5))->toBeFalse();
    expect($organization->exceedsLimit('items', 10))->toBeTrue();
    expect($organization->exceedsLimit('items', 15))->toBeTrue();
});

test('check plan limit middleware returns 402 when limit exceeded', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();

    $this->actingAs($user)
        ->postJson('/test-plan-limit', ['count' => 15])
        ->assertStatus(402);
});

test('check plan limit middleware allows when within limit', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();

    $this->actingAs($user)
        ->postJson('/test-plan-limit', ['count' => 5])
        ->assertSuccessful();
});

test('check plan limit middleware logs warning when exceeded', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $user = User::factory()->create();
    $organization = Organization::factory()->create(['owner_id' => $user->id]);
    $organization->members()->attach($user, ['role' => 'owner']);
    $user->switchOrganization($organization);

    Plan::factory()->free()->create();

    $this->actingAs($user)
        ->postJson('/test-plan-limit', ['count' => 15])
        ->assertStatus(402);

    expect($logs->contains(fn ($l) => $l->level === 'warning' && str_contains($l->message, 'Plan limit exceeded')))->toBeTrue();
});

beforeEach(function () {
    \Illuminate\Support\Facades\Route::post('/test-plan-limit', function (\Illuminate\Http\Request $request) {
        return response()->json(['ok' => true]);
    })->middleware(['auth', 'plan.limit:items,count']);
});
