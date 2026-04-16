<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\UploadedAsset;

/**
 * Renders a branded 1200×630 featured/OG image for a blog post using GD.
 *
 * Template: navy gradient + dot-grid texture + TheCashFox wordmark + coloured
 * category badge pill + wrapped title in Bricolage Grotesque.
 *
 * The image is stored in the `uploaded_assets` table under the key
 * `blog-post-{uuid}-featured` so it survives Railway redeploys. Served via
 * `BrandAssetController` at `/brand-asset/{key}`.
 */
class BlogImageRenderer
{
    private const WIDTH  = 1200;
    private const HEIGHT = 630;

    // Brand palette (hex → RGB tuples used by GD)
    private const NAVY     = [10, 15, 30];      // #0a0f1e
    private const DARK     = [17, 24, 39];      // #111827
    private const PRIMARY  = [26, 86, 219];     // #1a56db
    private const WHITE    = [248, 250, 252];   // #f8fafc
    private const MUTED    = [148, 163, 184];   // slate-400

    private string $fontBold;
    private string $fontRegular;
    private string $fontSemi;

    public function __construct()
    {
        $base = storage_path('fonts');
        $this->fontBold    = $base . '/BricolageGrotesque-Bold.ttf';
        $this->fontRegular = $base . '/Outfit-Regular.ttf';
        $this->fontSemi    = $base . '/Outfit-SemiBold.ttf';
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

        $this->drawBackground($img);
        $this->drawDotGrid($img);
        $this->drawBrandStrip($img);
        $this->drawAccentBar($img, $category);
        $this->drawCategoryPill($img, $category);
        $this->drawTitle($img, $title);
        $this->drawFooter($img);

        ob_start();
        imagepng($img, null, 5);
        $bytes = ob_get_clean();
        imagedestroy($img);

        $key = "blog-post-{$postId}-featured";
        UploadedAsset::put($key, $bytes, 'image/png');

        return $key;
    }

    // ─── Template pieces ───────────────────────────────────────────────

    private function drawBackground($img): void
    {
        // Vertical gradient: navy → slightly-lighter navy (subtle depth)
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / self::HEIGHT;
            $r = (int) round(self::NAVY[0] + ($t * 8));   // 10 → 18
            $g = (int) round(self::NAVY[1] + ($t * 12));  // 15 → 27
            $b = (int) round(self::NAVY[2] + ($t * 18));  // 30 → 48
            $c = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $c);
        }

        // Radial-ish glow in top-right (brand primary at low opacity)
        $glow = imagecolorallocatealpha($img, self::PRIMARY[0], self::PRIMARY[1], self::PRIMARY[2], 108);
        imagefilledellipse($img, (int) (self::WIDTH * 0.85), (int) (self::HEIGHT * 0.1), 520, 520, $glow);
    }

    private function drawDotGrid($img): void
    {
        // Subtle blue dots on a 32px grid — visual texture only
        $dot = imagecolorallocatealpha($img, 59, 130, 246, 115); // accent @ low alpha
        for ($x = 40; $x < self::WIDTH; $x += 32) {
            for ($y = 40; $y < self::HEIGHT; $y += 32) {
                imagefilledellipse($img, $x, $y, 2, 2, $dot);
            }
        }
    }

    private function drawBrandStrip($img): void
    {
        // Top-left brand text ("TheCashFox") — serves as wordmark
        $white = imagecolorallocate($img, ...self::WHITE);
        $appName = config('app.name', 'TheCashFox');
        imagettftext($img, 22, 0, 64, 78, $white, $this->fontBold, $appName);

        // Tagline below
        $muted = imagecolorallocate($img, ...self::MUTED);
        imagettftext($img, 13, 0, 64, 104, $muted, $this->fontRegular, 'Cash flow tracking, simplified.');
    }

    private function drawAccentBar($img, ?BlogCategory $category): void
    {
        // Left-edge vertical bar in the category colour
        $hex = $category?->color ?? '#1a56db';
        [$r, $g, $b] = $this->hexToRgb($hex);
        $bar = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, 0, 8, self::HEIGHT, $bar);
    }

    private function drawCategoryPill($img, ?BlogCategory $category): void
    {
        if (! $category) {
            return;
        }

        $label  = strtoupper($category->name);
        $hex    = $category->color ?? '#1a56db';
        [$r, $g, $b] = $this->hexToRgb($hex);

        // Measure text → compute pill width
        $bbox = imagettfbbox(14, 0, $this->fontSemi, $label);
        $textW = $bbox[2] - $bbox[0];
        $padX  = 18;
        $pillW = $textW + ($padX * 2);
        $pillH = 34;
        $x     = 64;
        $y     = 220;

        // Pill background (brand colour @ low opacity → reads as a tint)
        $bg = imagecolorallocatealpha($img, $r, $g, $b, 90);
        $this->filledRoundedRect($img, $x, $y, $x + $pillW, $y + $pillH, 17, $bg);

        // Pill border (brand colour full)
        $border = imagecolorallocate($img, $r, $g, $b);
        $this->strokeRoundedRect($img, $x, $y, $x + $pillW, $y + $pillH, 17, $border);

        // Label text
        $white = imagecolorallocate($img, ...self::WHITE);
        imagettftext($img, 14, 0, $x + $padX, $y + 23, $white, $this->fontSemi, $label);
    }

    private function drawTitle($img, string $title): void
    {
        $white    = imagecolorallocate($img, ...self::WHITE);
        $fontSize = 52;
        $maxWidth = self::WIDTH - 128;
        $x        = 64;
        $yStart   = 310;
        $lineGap  = 18;

        $lines = $this->wrapText($title, $fontSize, $this->fontBold, $maxWidth, 4);

        foreach ($lines as $i => $line) {
            $lineHeight = $this->lineHeight($fontSize, $this->fontBold);
            $y = $yStart + ($i * ($lineHeight + $lineGap));
            imagettftext($img, $fontSize, 0, $x, $y, $white, $this->fontBold, $line);
        }
    }

    private function drawFooter($img): void
    {
        $muted = imagecolorallocate($img, ...self::MUTED);
        imagettftext($img, 16, 0, 64, self::HEIGHT - 42, $muted, $this->fontSemi, 'thecashfox.com/blog');
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
     * Truncates with "…" once $maxLines is reached. Handles "all one word"
     * gracefully by breaking on character if needed (unlikely for titles).
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
            $totalWords = count($words);
            $wordsUsed = array_sum(array_map(fn($l) => count(preg_split('/\s+/', trim($l))), $lines));
            if ($wordsUsed < $totalWords) {
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
        // Top, bottom, left, right lines
        imageline($img, $x1 + $radius, $y1, $x2 - $radius, $y1, $color);
        imageline($img, $x1 + $radius, $y2, $x2 - $radius, $y2, $color);
        imageline($img, $x1, $y1 + $radius, $x1, $y2 - $radius, $color);
        imageline($img, $x2, $y1 + $radius, $x2, $y2 - $radius, $color);
        // Corner arcs
        imagearc($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color);
        imagearc($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color);
        imagearc($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color);
        imagearc($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color);
    }
}
