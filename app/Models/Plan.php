<?php

namespace App\Models;

use App\Enums\BillingFeature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'stripe_product_id',
        'stripe_price_ids',
        'stripe_metered_price_ids',
        'limits',
        'features',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stripe_price_ids' => 'array',
            'stripe_metered_price_ids' => 'array',
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(config('saas.cache.prefix', 'saas').':plans'));
        static::deleted(fn () => Cache::forget(config('saas.cache.prefix', 'saas').':plans'));
    }

    /**
     * @return Collection<int, static>
     */
    public static function allCached(): Collection
    {
        $prefix = config('saas.cache.prefix', 'saas');
        $ttl = config('saas.cache.ttl', 3600);

        return Cache::remember("{$prefix}:plans", $ttl, function () {
            return static::query()->where('is_active', true)->orderBy('sort_order')->get();
        });
    }

    public function priceId(string $interval): ?string
    {
        return $this->stripe_price_ids[$interval] ?? null;
    }

    public function meteredPriceId(string $key): ?string
    {
        return $this->stripe_metered_price_ids[$key] ?? null;
    }

    public function limit(BillingFeature|string $feature): ?int
    {
        $key = $feature instanceof BillingFeature ? $feature->value : $feature;
        $limits = $this->limits ?? [];

        if (! array_key_exists($key, $limits)) {
            return 0;
        }

        return $limits[$key];
    }

    public function isFreePlan(): bool
    {
        return empty($this->stripe_price_ids);
    }

    public function hasMeteredPricing(): bool
    {
        return ! empty($this->stripe_metered_price_ids);
    }
}
