<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-uploaded brand assets (logos, favicon). Stored in the database so
 * they survive redeploys on Railway / other ephemeral-filesystem hosts.
 *
 * Served via App\Http\Controllers\BrandAssetController at /brand-asset/{key}.
 */
class UploadedAsset extends Model
{
    protected $primaryKey = 'key';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    protected $fillable = ['key', 'mime', 'data', 'size', 'updated_at'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    private const METADATA_CACHE_TTL = 86400; // 24 hours — cache invalidated on put()

    /**
     * Is an asset with this key present? Cached so Blade templates checking
     * "does the logo exist?" on every page don't cost a DB query per request.
     */
    public static function has(string $key): bool
    {
        return (bool) static::meta($key);
    }

    /**
     * Cache-buster (unix timestamp of last update) for URL query strings,
     * so browsers re-fetch after an admin re-uploads. null if absent.
     */
    public static function cacheBuster(string $key): ?int
    {
        $m = static::meta($key);
        return $m['v'] ?? null;
    }

    /**
     * Fetch cached {mime, size, v} metadata without loading the binary payload.
     * Keeps the hot-path lightweight even when the cache misses.
     */
    public static function meta(string $key): ?array
    {
        return Cache::remember("asset_meta.{$key}", self::METADATA_CACHE_TTL, function () use ($key) {
            $row = static::query()
                ->where('key', $key)
                ->select(['key', 'mime', 'size', 'updated_at'])
                ->first();

            if (! $row) {
                return null;
            }

            return [
                'mime' => $row->mime,
                'size' => $row->size,
                'v'    => $row->updated_at?->timestamp ?? time(),
            ];
        });
    }

    /**
     * Store or replace an asset. Bytes are base64-encoded in the `data` column
     * (see migration for rationale). Clears metadata + payload caches.
     */
    public static function put(string $key, string $bytes, string $mime): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'mime'       => $mime,
                'data'       => base64_encode($bytes),
                'size'       => strlen($bytes),
                'updated_at' => now(),
            ]
        );

        Cache::forget("asset_meta.{$key}");
        Cache::forget("asset_payload.{$key}");
    }

    /**
     * Fetch the raw binary payload for an asset. Returns null if absent.
     * The column stores base64; we decode here so callers get bytes.
     */
    public static function payload(string $key): ?string
    {
        $encoded = static::query()->where('key', $key)->value('data');
        if ($encoded === null) {
            return null;
        }
        $decoded = base64_decode($encoded, true);
        return $decoded === false ? null : $decoded;
    }

    /**
     * Remove an asset. Returns true if something was deleted.
     */
    public static function forgetKey(string $key): bool
    {
        $deleted = (bool) static::query()->where('key', $key)->delete();

        Cache::forget("asset_meta.{$key}");
        Cache::forget("asset_payload.{$key}");

        return $deleted;
    }
}
