<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    public static function getValue(string $key, mixed $default = null, string $group = 'general'): mixed
    {
        $cacheKey = "setting.{$group}.{$key}";

        return Cache::remember($cacheKey, 60, function () use ($key, $default, $group) {
            $row = static::query()->where('group', $group)->where('key', $key)->first();

            return $row?->value ?? $default;
        });
    }

    public static function setValue(string $key, mixed $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) || is_bool($value) ? json_encode($value) : (string) $value],
        );
        Cache::forget("setting.{$group}.{$key}");
    }
}
