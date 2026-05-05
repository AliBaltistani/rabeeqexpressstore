<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use App\Models\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    const CACHE_DURATION = 86400; // 24 hours

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'setting:' . $key;

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        $group = explode('.', $key)[0] ?? 'general';

        self::updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'value' => $value]
        );

        Cache::forget('setting:' . $key);
    }

    public static function getGroup(string $group): array
    {
        $cacheKey = 'setting_group:' . $group;

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($group) {
            return self::where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    public static function flushCache(): void
    {
        Cache::flush();
    }

    protected static function booted()
    {
        static::saved(function () {
            self::flushCache();
        });

        static::deleted(function () {
            self::flushCache();
        });
    }
}
