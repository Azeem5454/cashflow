<?php

namespace App\Http\Controllers;

use App\Models\UploadedAsset;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Serves admin-uploaded brand assets (logos, favicon) from the database.
 *
 * Binary payload is cached in Redis keyed by "asset_payload.{key}" so a hot
 * logo doesn't hit Postgres on every page load. The client-side URL carries
 * a ?v={updated_at} cache-buster, so when admin re-uploads and the metadata
 * cache is cleared, the browser refetches and the new bytes are served.
 */
class BrandAssetController extends Controller
{
    /** Allow-list of keys this controller will serve — prevents path-like abuse. */
    private const ALLOWED = ['logo-dark', 'logo-light', 'favicon', 'og-image'];

    public function show(string $key)
    {
        if (! in_array($key, self::ALLOWED, true)) {
            abort(404);
        }

        $meta = UploadedAsset::meta($key);
        if (! $meta) {
            abort(404);
        }

        $bytes = Cache::remember("asset_payload.{$key}", 86400, function () use ($key) {
            return UploadedAsset::payload($key);
        });

        if ($bytes === null) {
            // Row was deleted between meta check and payload load.
            abort(404);
        }

        return response($bytes, 200, [
            'Content-Type'            => $meta['mime'],
            'Content-Length'          => (string) strlen($bytes),
            'Cache-Control'           => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options'  => 'nosniff',
        ]);
    }
}
