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
        Cache::forget(self::BRANDING_CACHE);
    }

    /** Cache key for the branding bundle read on every request. */
    public const BRANDING_CACHE = 'settings.branding';

    /**
     * The handful of settings that override config on every request, fetched
     * as ONE cached array.
     *
     * Read on every single request, so it must not cost a query per key — and
     * it must not call Schema::hasTable() either, which is an
     * information_schema lookup on each hit. A missing table just means an
     * empty bundle.
     *
     * @return array<string, string>  config key => value
     */
    public static function branding(): array
    {
        return Cache::rememberForever(self::BRANDING_CACHE, function () {
            $map = [
                'app.name'          => 'app.name',
                'app.tagline'       => 'app.tagline',
                'app.support_email' => 'app.support_email',
                'mail.from_name'    => 'mail.from.name',
                'mail.from_address' => 'mail.from.address',
            ];

            $rows = DB::table('settings')
                ->whereIn('key', array_keys($map))
                ->pluck('value', 'key');

            $out = [];
            foreach ($map as $settingKey => $configKey) {
                $value = $rows[$settingKey] ?? null;
                if ($value !== null && $value !== '') {
                    $out[$configKey] = $value;
                }
            }

            return $out;
        });
    }

    public static function forget(string $key): void
    {
        DB::table('settings')->where('key', $key)->delete();
        Cache::forget("setting.{$key}");
        Cache::forget(self::BRANDING_CACHE);
    }

    public static function flush(): void
    {
        $keys = DB::table('settings')->pluck('key');

        foreach ($keys as $key) {
            Cache::forget("setting.{$key}");
        }

        Cache::forget(self::BRANDING_CACHE);
    }
}
