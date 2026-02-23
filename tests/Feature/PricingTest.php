<?php

use App\Models\Plan;
use App\Models\User;

test('guests can view the pricing page', function () {
    Plan::factory()->count(3)->create();

    $response = $this->get(route('pricing'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('pricing')
        ->has('plans', 3)
    );
});

test('authenticated users can view the pricing page', function () {
    $user = User::factory()->create();
    Plan::factory()->count(2)->create();

    $this->actingAs($user);

    $response = $this->get(route('pricing'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('pricing')
        ->has('plans', 2)
    );
});
