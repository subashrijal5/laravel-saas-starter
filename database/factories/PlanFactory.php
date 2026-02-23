<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'stripe_price_ids' => null,
            'stripe_metered_price_ids' => null,
            'limits' => ['items' => 10],
            'features' => ['Basic feature'],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'free',
            'name' => 'Free',
            'stripe_price_ids' => null,
            'limits' => ['items' => 10, 'ai_tokens' => 1000],
        ]);
    }

    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'pro',
            'name' => 'Pro',
            'stripe_price_ids' => ['monthly' => 'price_pro_monthly', 'yearly' => 'price_pro_yearly'],
            'limits' => ['items' => 1000, 'ai_tokens' => 50000],
            'sort_order' => 1,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'enterprise',
            'name' => 'Enterprise',
            'stripe_price_ids' => ['monthly' => 'price_ent_monthly', 'yearly' => 'price_ent_yearly'],
            'limits' => ['items' => null, 'ai_tokens' => null],
            'sort_order' => 2,
        ]);
    }
}
