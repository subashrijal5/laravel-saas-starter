<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BillingMeter extends Model
{
    /** @use HasFactory<\Database\Factories\BillingMeterFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'display_name',
        'event_name',
        'stripe_meter_id',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(config('saas.cache.prefix', 'saas').':meters'));
        static::deleted(fn () => Cache::forget(config('saas.cache.prefix', 'saas').':meters'));
    }

    /**
     * @return Collection<int, static>
     */
    public static function allCached(): Collection
    {
        $prefix = config('saas.cache.prefix', 'saas');
        $ttl = config('saas.cache.ttl', 3600);

        return Cache::remember("{$prefix}:meters", $ttl, function () {
            return static::all();
        });
    }
}
