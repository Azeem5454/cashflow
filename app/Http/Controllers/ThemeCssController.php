<?php

namespace App\Http\Controllers;

use App\Helpers\Setting;
use Illuminate\Http\Response;

/**
 * Serves the admin-authored brand theme (colours + fonts) from the database.
 *
 * It used to be written to public/brand/theme.css, which Railway wipes on
 * every redeploy — so an admin would set the palette, see it work, and find
 * it silently reverted days later. The logos hit the same problem and moved
 * to uploaded_assets; this is the same fix for CSS.
 *
 * Cached hard and busted by ?v=, which the layouts take from
 * Setting::get('theme.version').
 */
class ThemeCssController extends Controller
{
    public function __invoke(): Response
    {
        $css = (string) Setting::get('theme.css', '');

        return response($css, 200, [
            'Content-Type'           => 'text/css; charset=UTF-8',
            'Cache-Control'          => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
