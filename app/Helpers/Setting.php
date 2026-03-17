<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Setting
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $row = DB::table('settings')->where('key', $key)->first();

            return $row ? $row->value : $default;
        }) ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );

        Cache::forget("setting.{$key}");
    }

    public static function forget(string $key): void
    {
        DB::table('settings')->where('key', $key)->delete();
        Cache::forget("setting.{$key}");
    }

    public static function flush(): void
    {
        $keys = DB::table('settings')->pluck('key');

        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }
    }
}
