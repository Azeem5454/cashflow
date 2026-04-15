<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-uploaded assets (brand logos, favicon, OG image) live here rather
 * than in public/brand/ so they survive redeploys on hosting platforms with
 * ephemeral filesystems (Railway, Heroku, serverless containers).
 *
 * The `data` column is PostgreSQL BYTEA (binary) — logos are typically
 * under 1 MB so this is safe. Served to the browser via BrandAssetController
 * with long-cache headers keyed by updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_assets', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('mime', 100);
            // `data` holds base64-encoded bytes. Using a text column avoids
            // Postgres bytea UTF-8 / driver-binding issues, works identically
            // across MySQL/Postgres/SQLite, and costs ~33% overhead — fine
            // for sub-megabyte brand assets.
            $table->longText('data');
            $table->unsignedInteger('size');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Backfill: if the legacy files already exist under public/brand/,
        // copy them into the DB so we don't lose them on this deploy.
        // Idempotent: only inserts keys that don't already exist.
        $publicBrand = public_path('brand');

        $backfillMap = [
            'logo-dark'  => $publicBrand . '/logo-dark.png',
            'logo-light' => $publicBrand . '/logo-light.png',
            'favicon'    => $publicBrand . '/favicon.png',
        ];

        foreach ($backfillMap as $key => $path) {
            if (! File::exists($path)) {
                continue;
            }

            $exists = DB::table('uploaded_assets')->where('key', $key)->exists();
            if ($exists) {
                continue;
            }

            $bytes = File::get($path);
            $mime  = File::mimeType($path) ?: 'image/png';

            DB::table('uploaded_assets')->insert([
                'key'        => $key,
                'mime'       => $mime,
                'data'       => base64_encode($bytes),
                'size'       => strlen($bytes),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_assets');
    }
};
