<?php

namespace App\Http\Controllers;

use App\Models\UploadedAsset;
use Illuminate\Http\JsonResponse;

/**
 * /site.webmanifest — lets a browser install the site to a home screen and
 * gives it a name, icon and colours instead of a URL and a screenshot.
 *
 * Generated rather than shipped as a static file: the app name and the icon
 * are both editable from /admin/appearance, and the icon lives in the
 * database because Railway wipes the filesystem on redeploy. A checked-in
 * file would go stale the first time either changed.
 */
class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $name = (string) config('app.name', 'TheCashFox');

        $icon = asset('favicon.png');
        try {
            if (UploadedAsset::has('favicon')) {
                $icon = route('brand-asset', 'favicon') . '?v=' . UploadedAsset::cacheBuster('favicon');
            }
        } catch (\Throwable $e) {
            report($e);
            // fall through to the bundled icon
        }

        return response()->json([
            'name'             => $name,
            // Home screens truncate at roughly 12 characters.
            'short_name'       => $name,
            'description'      => \App\Support\JsonLd::ORG_DESCRIPTION,
            'start_url'        => '/dashboard',
            'scope'            => '/',
            'display'          => 'standalone',
            'background_color' => '#0a0f1e',
            'theme_color'      => '#0a0f1e',
            'icons'            => [[
                'src'     => $icon,
                // The source is 512x512; "any" lets the browser scale it
                // rather than us lying about sizes we do not actually ship.
                'sizes'   => '512x512',
                'type'    => 'image/png',
                'purpose' => 'any',
            ]],
        ], 200, [
            'Content-Type'  => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
