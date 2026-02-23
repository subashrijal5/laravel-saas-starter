<?php

use App\Models\Plan;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

test('sync command creates plans in database', function () {
    config([
        'saas.billing.plans' => [
            'free' => [
                'name' => 'Free',
                'description' => 'Free plan',
                'features' => ['Basic'],
                'limits' => ['items' => 10],
            ],
            'starter' => [
                'name' => 'Starter',
                'description' => 'Starter plan',
                'features' => ['Advanced'],
                'limits' => ['items' => 100],
            ],
        ],
        'saas.billing.meters' => [],
    ]);

    $this->artisan('saas:sync')
        ->assertSuccessful();

    expect(Plan::query()->count())->toBe(2);
    expect(Plan::query()->where('key', 'free')->first()->name)->toBe('Free');
    expect(Plan::query()->where('key', 'starter')->first()->name)->toBe('Starter');
});

test('sync command updates existing plans', function () {
    Plan::factory()->create(['key' => 'free', 'name' => 'Old Free', 'is_active' => true]);

    config([
        'saas.billing.plans' => [
            'free' => [
                'name' => 'New Free',
                'description' => 'Updated free plan',
                'features' => ['Updated'],
                'limits' => ['items' => 20],
            ],
        ],
        'saas.billing.meters' => [],
    ]);

    $this->artisan('saas:sync')
        ->assertSuccessful();

    $plan = Plan::query()->where('key', 'free')->first();
    expect($plan->name)->toBe('New Free');
    expect($plan->limits)->toBe(['items' => 20]);
});

test('sync command sets correct sort order', function () {
    config([
        'saas.billing.plans' => [
            'free' => [
                'name' => 'Free',
                'description' => 'Free',
                'limits' => ['items' => 10],
            ],
            'starter' => [
                'name' => 'Starter',
                'description' => 'Starter',
                'limits' => ['items' => 100],
            ],
            'team' => [
                'name' => 'Team',
                'description' => 'Team',
                'limits' => ['items' => null],
            ],
        ],
        'saas.billing.meters' => [],
    ]);

    $this->artisan('saas:sync')
        ->assertSuccessful();

    expect(Plan::query()->where('key', 'free')->first()->sort_order)->toBe(0);
    expect(Plan::query()->where('key', 'starter')->first()->sort_order)->toBe(1);
    expect(Plan::query()->where('key', 'team')->first()->sort_order)->toBe(2);
});

test('sync command fails when no billing config', function () {
    config(['saas.billing' => null]);

    $this->artisan('saas:sync')
        ->assertFailed();
});

test('plan model caches active plans', function () {
    Plan::factory()->free()->create(['is_active' => true]);
    Plan::factory()->pro()->create(['is_active' => true]);
    Plan::factory()->create(['key' => 'inactive', 'is_active' => false]);

    $cached = Plan::allCached();

    expect($cached)->toHaveCount(2);
    expect($cached->pluck('key')->toArray())->not->toContain('inactive');
});

test('plan model reports correct price ids', function () {
    $plan = Plan::factory()->pro()->create();

    expect($plan->priceId('monthly'))->toBe('price_pro_monthly');
    expect($plan->priceId('yearly'))->toBe('price_pro_yearly');
    expect($plan->priceId('weekly'))->toBeNull();
});

test('plan model identifies free plan correctly', function () {
    $freePlan = Plan::factory()->free()->create();
    $proPlan = Plan::factory()->pro()->create();

    expect($freePlan->isFreePlan())->toBeTrue();
    expect($proPlan->isFreePlan())->toBeFalse();
});

test('sync command logs start and completion', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    config([
        'saas.billing.plans' => [
            'free' => [
                'name' => 'Free',
                'description' => 'Free plan',
                'features' => ['Basic'],
                'limits' => ['items' => 10],
            ],
        ],
        'saas.billing.meters' => [],
    ]);

    $this->artisan('saas:sync')->assertSuccessful();

    expect($logs->contains(fn ($l) => $l->level === 'info' && str_contains($l->message, 'Starting sync')))->toBeTrue();
    expect($logs->contains(fn ($l) => $l->level === 'info' && str_contains($l->message, 'Sync completed')))->toBeTrue();
    expect($logs->contains(fn ($l) => $l->level === 'info' && str_contains($l->message, 'Plan synced')))->toBeTrue();
});
