<?php

use App\Models\Organization;
use App\Models\User;

test('authenticated users can view the create organization page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('organizations.create'))
        ->assertOk();
});

test('authenticated users can create an organization', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => 'Acme Inc.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('organizations', [
        'name' => 'Acme Inc.',
        'owner_id' => $user->id,
        'personal_organization' => false,
    ]);

    expect($user->fresh()->current_organization_id)->not->toBeNull();
    expect($user->organizations()->count())->toBe(1);
});

test('organization is created with a custom slug when provided', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => 'Acme Inc.',
            'slug' => 'acme-inc',
        ]);

    $this->assertDatabaseHas('organizations', [
        'slug' => 'acme-inc',
    ]);
});

test('organization slug must be unique', function () {
    $user = User::factory()->create();
    Organization::factory()->create(['slug' => 'taken-slug']);

    $this->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => 'Test Org',
            'slug' => 'taken-slug',
        ])
        ->assertSessionHasErrors('slug');
});

test('organization name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name');
});

test('a personal organization is created on registration', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->organizations()->count())->toBe(1);

    $org = $user->organizations()->first();
    expect($org->personal_organization)->toBeTrue();
    expect($org->owner_id)->toBe($user->id);
    expect($user->current_organization_id)->toBe($org->id);
});

test('guests cannot create organizations', function () {
    $this->post(route('organizations.store'), [
        'name' => 'Acme Inc.',
    ])->assertRedirect(route('login'));
});
