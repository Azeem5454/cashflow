<?php

namespace App\Support;

/**
 * Normalises an uploaded business logo before it is stored.
 *
 * Re-encoding through GD drops every metadata chunk the original carried
 * (EXIF, GPS, colour profiles, embedded thumbnails) and caps the longest side
 * at 512px, so a phone photo doesn't become a 5 MB row in `uploaded_assets`.
 *
 * GD is guaranteed on this deployment (composer.json requires ext-gd), but the
 * original bytes are returned untouched if it is somehow unavailable or the
 * image cannot be decoded — the caller has already validated MIME and size.
 */
class BusinessLogo
{
    public const MAX_EDGE = 512;

    public static function normalise(string $bytes): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $bytes;
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return $bytes;
        }

        try {
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w < 1 || $h < 1) {
                return $bytes;
            }

            $scale = min(1.0, self::MAX_EDGE / max($w, $h));
            $tw    = max(1, (int) round($w * $scale));
            $th    = max(1, (int) round($h * $scale));

            $dst = imagecreatetruecolor($tw, $th);
            // Keep transparency for PNGs; JPEGs are opaque anyway.
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

            ob_start();
            imagepng($dst, null, 6);
            $out = (string) ob_get_clean();

            imagedestroy($dst);

            return $out !== '' ? $out : $bytes;
        } catch (\Throwable) {
            return $bytes;
        } finally {
            imagedestroy($src);
        }
    }
}
