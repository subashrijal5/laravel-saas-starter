<?php

use App\Listeners\StripeWebhookListener;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookReceived;

test('webhook listener logs warning when customer not found', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $event = new WebhookReceived([
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'customer' => 'cus_nonexistent',
            ],
        ],
    ]);

    app(StripeWebhookListener::class)->handle($event);

    expect($logs->contains(fn ($l) => $l->level === 'info' && str_contains($l->message, 'subscription event received')))->toBeTrue();
    expect($logs->contains(fn ($l) => $l->level === 'warning' && str_contains($l->message, 'Organization not found')))->toBeTrue();
});

test('webhook listener logs warning when customer id missing', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $event = new WebhookReceived([
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [],
        ],
    ]);

    app(StripeWebhookListener::class)->handle($event);

    expect($logs->contains(fn ($l) => $l->level === 'warning' && str_contains($l->message, 'Missing customer ID')))->toBeTrue();
});

test('webhook listener ignores non-subscription events', function () {
    $logs = collect();
    Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logs) {
        $logs->push($event);
    });

    $event = new WebhookReceived([
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'customer' => 'cus_test',
            ],
        ],
    ]);

    app(StripeWebhookListener::class)->handle($event);

    expect($logs->contains(fn ($l) => $l->level === 'info'))->toBeFalse();
});
