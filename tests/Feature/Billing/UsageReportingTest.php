<?php

use App\Actions\Billing\ReportUsage;
use App\Models\BillingMeter;
use App\Models\Organization;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

test('report usage does nothing when org not subscribed', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $organization = Organization::factory()->create();

    BillingMeter::factory()->create([
        'key' => 'ai_tokens',
        'event_name' => 'ai_tokens_used',
        'stripe_meter_id' => 'mtr_test',
    ]);

    $action = app(ReportUsage::class);

    expect(fn () => $action->handle($organization, 'ai_tokens', 5))
        ->not->toThrow(\Exception::class);

    expect($logs->contains(fn ($l) => $l->level === 'debug' && str_contains($l->message, 'Reporting usage')))->toBeTrue();
    expect($logs->contains(fn ($l) => $l->level === 'debug' && str_contains($l->message, 'not subscribed')))->toBeTrue();
});

test('report usage does nothing for unknown meter', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $organization = Organization::factory()->create();

    $action = app(ReportUsage::class);

    expect(fn () => $action->handle($organization, 'nonexistent_meter', 5))
        ->not->toThrow(\Exception::class);

    expect($logs->contains(fn ($l) => $l->level === 'warning' && str_contains($l->message, 'Meter not found')))->toBeTrue();
});

test('billing meter model caches correctly', function () {
    BillingMeter::factory()->create(['key' => 'ai_tokens']);
    BillingMeter::factory()->create(['key' => 'api_calls']);

    $meters = BillingMeter::allCached();

    expect($meters)->toHaveCount(2);
    expect($meters->pluck('key')->toArray())->toContain('ai_tokens', 'api_calls');
});
