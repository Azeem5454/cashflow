<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\UploadedAsset;

/**
 * Renders a branded 1200×630 featured/OG image for a blog post using GD.
 *
 * Design principles:
 *   - Centered composition: title + category pill live in a 700px-wide
 *     "safe zone" so even a 1:1 card crop still shows them.
 *   - Single light source: one large soft radial glow creates depth,
 *     no competing elements.
 *   - Restrained palette: deep navy + category accent only.
 *   - Subtle film grain instead of a dot grid for texture.
 *
 * Stored in `uploaded_assets` under the key `blog-post-{uuid}-featured`
 * so it survives Railway redeploys. Served via BrandAssetController.
 */
class BlogImageRenderer
{
    private const WIDTH  = 1200;
    private const HEIGHT = 630;

    // Central "safe zone" — everything readable must fit inside this box,
    // so a 1:1 card crop still shows the full title.
    private const SAFE_X_START = 250;
    private const SAFE_X_END   = 950;
    private const SAFE_WIDTH   = 700;
    private const SAFE_CENTER  = 600;  // (WIDTH / 2)

    // Brand palette
    private const NAVY_DEEP   = [8,  12, 24];    // base bg, bottom-left
    private const NAVY_LIGHT  = [20, 28, 48];    // base bg, top-right
    private const WHITE       = [248, 250, 252]; // #f8fafc
    private const MUTED       = [100, 116, 139]; // slate-500
    private const BRAND_BLUE  = [26, 86, 219];   // primary

    private string $fontBold;
    private string $fontSemi;
    private string $fontRegular;

    public function __construct()
    {
        $base = storage_path('fonts');
        $this->fontBold    = $base . '/BricolageGrotesque-Bold.ttf';
        $this->fontSemi    = $base . '/Outfit-SemiBold.ttf';
        $this->fontRegular = $base . '/Outfit-Regular.ttf';
    }

    /**
     * Generate a featured image and persist it to uploaded_assets.
     * Returns the asset key so callers can stamp it on the post.
     *
     * @throws \RuntimeException if GD is unavailable or fonts are missing
     */
    public function renderForPost(string $postId, string $title, ?BlogCategory $category = null): string
    {
        $this->assertReady();

        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagesavealpha($img, true);

        $this->drawGradientBackground($img);
        $this->drawGlow($img, $category);
        $this->drawGrain($img);
        $this->drawCategoryPill($img, $category);
        $this->drawTitle($img, $title);
        $this->drawBrand($img);

        ob_start();
        imagepng($img, null, 5);
        $bytes = ob_get_clean();
        imagedestroy($img);

        $key = "blog-post-{$postId}-featured";
        UploadedAsset::put($key, $bytes, 'image/png');

        return $key;
    }

    // ─── Template pieces ───────────────────────────────────────────────

    /** Subtle diagonal gradient: lighter top-right → darker bottom-left. */
    private function drawGradientBackground($img): void
    {
        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                // Diagonal blend factor (0 at bottom-left, 1 at top-right)
                $t = (($x / self::WIDTH) + ((self::HEIGHT - $y) / self::HEIGHT)) / 2;
                $r = (int) round(self::NAVY_DEEP[0] + ($t * (self::NAVY_LIGHT[0] - self::NAVY_DEEP[0])));
                $g = (int) round(self::NAVY_DEEP[1] + ($t * (self::NAVY_LIGHT[1] - self::NAVY_DEEP[1])));
                $b = (int) round(self::NAVY_DEEP[2] + ($t * (self::NAVY_LIGHT[2] - self::NAVY_DEEP[2])));
                imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
            }
        }
    }

    /**
     * Single large soft glow in the upper-right quadrant. Colour picks up
     * the category accent for visual identity without a rainbow.
     */
    private function drawGlow($img, ?BlogCategory $category): void
    {
        $hex = $category?->color ?? '#1a56db';
        [$r, $g, $b] = $this->hexToRgb($hex);

        $cx = (int) (self::WIDTH  * 0.82);
        $cy = (int) (self::HEIGHT * 0.15);

        // Build a concentric falloff by drawing 18 rings of decreasing alpha.
        // Cheaper than a per-pixel radial equation and visually identical.
        for ($i = 18; $i >= 1; $i--) {
            $radius = $i * 55;                    // up to ~1000 px
            $alpha  = 125 - (int) (110 * (1 - ($i / 18)));  // fades to edge
            if ($alpha >= 127) continue;
            $color = imagecolorallocatealpha($img, $r, $g, $b, $alpha);
            imagefilledellipse($img, $cx, $cy, $radius, $radius, $color);
        }
    }

    /**
     * Very sparse pseudo-random grain — replaces the dot grid. Deterministic
     * via fixed seed so the same post always gets the same texture (makes
     * CDN caching and visual regression catches meaningful).
     */
    private function drawGrain($img): void
    {
        mt_srand(42);
        $grain = imagecolorallocatealpha($img, 255, 255, 255, 118);
        $count = (int) ((self::WIDTH * self::HEIGHT) * 0.002);
        for ($i = 0; $i < $count; $i++) {
            $x = mt_rand(0, self::WIDTH  - 1);
            $y = mt_rand(0, self::HEIGHT - 1);
            imagesetpixel($img, $x, $y, $grain);
        }
    }

    private function drawCategoryPill($img, ?BlogCategory $category): void
    {
        if (! $category) {
            return;
        }

        $label  = strtoupper($category->name);
        $hex    = $category->color ?? '#1a56db';
        [$r, $g, $b] = $this->hexToRgb($hex);

        $fontSize = 13;
        $bbox  = imagettfbbox($fontSize, 0, $this->fontSemi, $label);
        $textW = $bbox[2] - $bbox[0];
        $padX  = 16;
        $pillW = $textW + ($padX * 2);
        $pillH = 30;

        // Centered horizontally in the image
        $x1 = (int) (self::SAFE_CENTER - ($pillW / 2));
        $y1 = 232;
        $x2 = $x1 + $pillW;
        $y2 = $y1 + $pillH;

        // Subtle coloured fill + stronger border
        $fill   = imagecolorallocatealpha($img, $r, $g, $b, 107);
        $border = imagecolorallocatealpha($img, $r, $g, $b, 35);
        $this->filledRoundedRect($img, $x1, $y1, $x2, $y2, 15, $fill);
        $this->strokeRoundedRect($img, $x1, $y1, $x2, $y2, 15, $border);

        // Label text
        $white = imagecolorallocate($img, ...self::WHITE);
        imagettftext($img, $fontSize, 0, $x1 + $padX, $y2 - 10, $white, $this->fontSemi, $label);
    }

    /**
     * Title: centered, wrapped, big. Dynamically sized down if the title is
     * long — long titles fall back from 60 → 50 → 42 so they always fit
     * inside the safe zone without truncation.
     */
    private function drawTitle($img, string $title): void
    {
        $white = imagecolorallocate($img, ...self::WHITE);

        // Pick the largest size that lets the title fit in ≤ 3 lines.
        // Each step tries a smaller font until the wrap budget works.
        foreach ([60, 54, 48, 42, 38] as $size) {
            $lines = $this->wrapText($title, $size, $this->fontBold, self::SAFE_WIDTH, 3);
            $wordsUsed = array_sum(array_map(fn($l) => count(preg_split('/\s+/', trim($l))), $lines));
            $allWords  = count(preg_split('/\s+/', trim($title)));
            if ($wordsUsed >= $allWords || $size === 38) {
                break;
            }
        }

        $lineHeight = $this->lineHeight($size, $this->fontBold);
        $lineGap    = (int) ($size * 0.28);
        $totalH     = (count($lines) * $lineHeight) + ((count($lines) - 1) * $lineGap);

        // Vertically centred between the pill (ends at y=262) and the brand
        // watermark (starts at y=580). That gives a tall central block.
        $blockTop = 290 + (int) ((290 - $totalH) / 2);

        foreach ($lines as $i => $line) {
            $bb = imagettfbbox($size, 0, $this->fontBold, $line);
            $lineW = $bb[2] - $bb[0];
            $x = (int) (self::SAFE_CENTER - ($lineW / 2));
            $y = $blockTop + ($i * ($lineHeight + $lineGap)) + $lineHeight;
            imagettftext($img, $size, 0, $x, $y, $white, $this->fontBold, $line);
        }
    }

    /** Tiny muted brand mark bottom-centre — doesn't compete with the title. */
    private function drawBrand($img): void
    {
        $muted = imagecolorallocate($img, ...self::MUTED);
        $brand = imagecolorallocate($img, ...self::WHITE);
        $accent = imagecolorallocate($img, ...self::BRAND_BLUE);

        $appName = config('app.name', 'TheCashFox');
        $size = 15;

        // Measure so we can centre everything horizontally
        $bb = imagettfbbox($size, 0, $this->fontSemi, $appName);
        $nameW = $bb[2] - $bb[0];

        $bullet = '·';
        $bbb = imagettfbbox($size, 0, $this->fontRegular, $bullet);
        $bulletW = $bbb[2] - $bbb[0];

        $tagline = 'cash flow tracking';
        $bbt = imagettfbbox($size, 0, $this->fontRegular, $tagline);
        $tagW = $bbt[2] - $bbt[0];

        $gap = 10;
        $totalW = $nameW + $gap + $bulletW + $gap + $tagW;
        $x = (int) (self::SAFE_CENTER - ($totalW / 2));
        $y = 590;

        imagettftext($img, $size, 0, $x, $y, $brand, $this->fontSemi, $appName);
        imagettftext($img, $size, 0, $x + $nameW + $gap, $y, $accent, $this->fontRegular, $bullet);
        imagettftext($img, $size, 0, $x + $nameW + $gap + $bulletW + $gap, $y, $muted, $this->fontRegular, $tagline);
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    private function assertReady(): void
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('GD extension not loaded — install php-gd on the server.');
        }
        if (! function_exists('imagettftext')) {
            $info = function_exists('gd_info') ? gd_info() : [];
            $freetype = $info['FreeType Support'] ?? false;
            throw new \RuntimeException(
                'GD present but FreeType missing (imagettftext unavailable). ' .
                'FreeType Support = ' . ($freetype ? 'yes' : 'no') . '. ' .
                'Rebuild PHP with --with-freetype.'
            );
        }
        foreach ([$this->fontBold, $this->fontRegular, $this->fontSemi] as $f) {
            if (! is_file($f)) {
                throw new \RuntimeException(
                    'Font file missing at ' . $f . '. ' .
                    'Check that storage/fonts/ is present in the deployed build.'
                );
            }
            if (! is_readable($f)) {
                throw new \RuntimeException('Font file not readable at ' . $f);
            }
        }
    }

    /**
     * Word-wrap text to fit within $maxWidth pixels at the given font size.
     * Truncates with "…" once $maxLines is reached.
     */
    private function wrapText(string $text, int $fontSize, string $font, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $try = $current === '' ? $word : $current . ' ' . $word;
            if ($this->textWidth($try, $fontSize, $font) <= $maxWidth) {
                $current = $try;
                continue;
            }
            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;
            if (count($lines) >= $maxLines) {
                break;
            }
        }

        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        // Truncate last line with ellipsis if we ran out of room
        if (count($lines) === $maxLines) {
            $last = $lines[$maxLines - 1];
            $wordsUsed = array_sum(array_map(fn($l) => count(preg_split('/\s+/', trim($l))), $lines));
            if ($wordsUsed < count($words)) {
                while ($this->textWidth($last . '…', $fontSize, $font) > $maxWidth && strlen($last) > 4) {
                    $last = rtrim(substr($last, 0, -1));
                }
                $lines[$maxLines - 1] = rtrim($last, " \t\n\r\0\x0B,-") . '…';
            }
        }

        return $lines;
    }

    private function textWidth(string $text, int $fontSize, string $font): int
    {
        $bbox = imagettfbbox($fontSize, 0, $font, $text);
        return $bbox[2] - $bbox[0];
    }

    private function lineHeight(int $fontSize, string $font): int
    {
        $bbox = imagettfbbox($fontSize, 0, $font, 'Mg');
        return $bbox[1] - $bbox[7];
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private function filledRoundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    private function strokeRoundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imageline($img, $x1 + $radius, $y1, $x2 - $radius, $y1, $color);
        imageline($img, $x1 + $radius, $y2, $x2 - $radius, $y2, $color);
        imageline($img, $x1, $y1 + $radius, $x1, $y2 - $radius, $color);
        imageline($img, $x2, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagearc($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color);
        imagearc($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color);
        imagearc($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color);
        imagearc($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color);
    }
}
