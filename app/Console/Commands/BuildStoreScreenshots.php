<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Turns raw device captures into store-ready screenshots.
 *
 * The capture itself is only ever scaled and positioned — never redrawn — so
 * what ships is the real app. Play's metadata policy requires that, and an
 * AI-generated approximation of your own UI would fail it.
 *
 * Output is exactly 1080×1920 (Play) or 1290×2796 (App Store). Play rejects
 * images whose long side is more than twice the short side, which is why a
 * raw 1080×2340 phone capture can't be uploaded as-is.
 *
 *   php artisan store:screenshots ~/Desktop/captures
 *
 * Input files are matched by leading number: 1-scan.png, 2-type.png, ...
 * Captions come from the table below and match docs/launch/screenshot-plan.md.
 */
class BuildStoreScreenshots extends Command
{
    protected $signature = 'store:screenshots
        {source : Directory holding the raw captures, named 1-*.png … 8-*.png}
        {--out= : Where to write (default: <source>/store)}
        {--ios : Render 1290×2796 for the App Store instead of 1080×1920 for Play}';

    protected $description = 'Compose raw app captures into branded store screenshots';

    /** @var array<int, array{0:string,1:string,2:bool}> headline, subline, isPro */
    private const SHOTS = [
        1 => ["Snap it. It's logged.",     'AI reads your receipt and fills in the entry.',          false],
        2 => ['Type it. Done.',            'Describe a transaction and the form fills itself.',      false],
        3 => ['Your balance, always live', 'Cash in, cash out and net, updated instantly.',          false],
        4 => ['See where money goes',      'Charts by category, payment method and time.',           true],
        5 => ['Insights in plain English', 'AI explains what changed this month.',                   true],
        6 => ['Your whole team, one book', 'Owner, Editor and Viewer roles, with the right access.', false],
        7 => ['Free to start',             'Unlimited books and entries. Go Pro when you grow.',     false],
        8 => ['Nothing gets lost',         'Every change is logged, with who made it.',              false],
    ];

    private const NAVY  = [10, 15, 30];
    private const WHITE = [248, 250, 252];
    private const LIGHT = [147, 197, 253];
    private const BLUE  = [26, 86, 219];
    private const AMBER = [251, 191, 36];

    private int $w;
    private int $h;
    private float $k;      // scale factor against the 1080 reference canvas
    private string $fontDisplay;
    private string $fontBody;

    public function handle(): int
    {
        if (! extension_loaded('gd') || ! function_exists('imagettftext')) {
            $this->error('GD with FreeType is required.');
            return self::FAILURE;
        }

        $this->fontDisplay = storage_path('fonts/BricolageGrotesque-Bold.ttf');
        $this->fontBody    = storage_path('fonts/Outfit-Regular.ttf');

        foreach ([$this->fontDisplay, $this->fontBody] as $f) {
            if (! is_file($f)) {
                $this->error("Font missing: {$f}");
                return self::FAILURE;
            }
        }

        [$this->w, $this->h] = $this->option('ios') ? [1290, 2796] : [1080, 1920];
        $this->k = $this->w / 1080;

        $source = rtrim((string) $this->argument('source'), '/');
        if (! is_dir($source)) {
            $this->error("No such directory: {$source}");
            return self::FAILURE;
        }

        $out = rtrim((string) ($this->option('out') ?: $source . '/store'), '/');
        if (! is_dir($out) && ! mkdir($out, 0755, true)) {
            $this->error("Could not create {$out}");
            return self::FAILURE;
        }

        $made = 0;

        foreach (self::SHOTS as $n => [$headline, $subline, $isPro]) {
            $capture = $this->findCapture($source, $n);

            if ($capture === null) {
                $this->warn("  {$n}. no capture found (expected {$n}-*.png) — skipped");
                continue;
            }

            $path = $out . '/' . sprintf('%02d', $n) . '-' . $this->slug($headline) . '.png';
            $this->compose($capture, $headline, $subline, $isPro, $path);

            $this->info("  {$n}. {$headline} → " . basename($path));
            $made++;
        }

        $this->newLine();

        if ($made === 0) {
            $this->error('Nothing composed. Name your captures 1-something.png … 8-something.png.');
            return self::FAILURE;
        }

        $this->info("{$made} screenshot(s) written to {$out} at {$this->w}×{$this->h}.");

        if ($made < 4) {
            $this->warn('Play needs at least 2, and 4+ at 1080px wide to be eligible for promotion.');
        }

        return self::SUCCESS;
    }

    private function findCapture(string $dir, int $n): ?string
    {
        foreach (['png', 'PNG', 'jpg', 'jpeg', 'JPG'] as $ext) {
            $hits = glob($dir . '/' . $n . '-*.' . $ext) ?: [];
            if ($hits !== []) {
                return $hits[0];
            }
        }

        return null;
    }

    private function compose(string $capturePath, string $headline, string $subline, bool $isPro, string $outPath): void
    {
        $img = imagecreatetruecolor($this->w, $this->h);

        $this->drawBackground($img);

        $y = $this->drawCaption($img, $headline, $subline, $isPro);
        $this->drawDevice($img, $capturePath, $y);

        imagepng($img, $outPath, 6);
        imagedestroy($img);
    }

    /**
     * Flat brand background with a barely-there vertical lift.
     *
     * The premium references are flat: no texture, no glow. Decoration behind
     * a full-bleed capture reads as filler, so the only gradient here is a
     * slight lightening towards the caption.
     */
    private function drawBackground($img): void
    {
        for ($y = 0; $y < $this->h; $y++) {
            $t = 1 - ($y / $this->h);                    // 1 at top, 0 at bottom
            $r = (int) round(self::NAVY[0] + ($t * 8));
            $g = (int) round(self::NAVY[1] + ($t * 11));
            $b = (int) round(self::NAVY[2] + ($t * 20));
            imagefilledrectangle($img, 0, $y, $this->w, $y, imagecolorallocate($img, $r, $g, $b));
        }
    }

    /** Caption block at the top. Returns the y where the device may start. */
    private function drawCaption($img, string $headline, string $subline, bool $isPro): int
    {
        $white = imagecolorallocate($img, ...self::WHITE);
        $light = imagecolorallocate($img, ...self::LIGHT);

        $margin = (int) round(72 * $this->k);
        $maxW   = $this->w - ($margin * 2);

        // Headline sized per the screenshot plan: Bricolage 800 at ~88px on
        // the 1080 canvas, stepping down only if it will not fit two lines.
        $headPx = (int) round(88 * $this->k);
        $lines  = [];

        foreach ([$headPx, (int) round(78 * $this->k), (int) round(68 * $this->k)] as $size) {
            $lines = $this->wrap($headline, $size, $this->fontDisplay, $maxW, 2);
            if ($this->joinedWordCount($lines) >= str_word_count($headline)) {
                $headPx = $size;
                break;
            }
        }

        $y = (int) round(400 * $this->k);

        foreach ($lines as $line) {
            imagettftext($img, $headPx, 0, $margin, $y, $white, $this->fontDisplay, $line);
            $y += (int) round($headPx * 1.22);
        }

        if ($isPro) {
            $y += (int) round(14 * $this->k);
            $y = $this->drawProPill($img, $margin, $y);
        }

        $y += (int) round(26 * $this->k);

        $subPx = (int) round(40 * $this->k);

        foreach ($this->wrap($subline, $subPx, $this->fontBody, $maxW, 2) as $line) {
            imagettftext($img, $subPx, 0, $margin, $y, $light, $this->fontBody, $line);
            $y += (int) round($subPx * 1.45);
        }

        // Every sheet starts its capture on the same line, so the eight read as
        // a series rather than eight separate images. The floor is set above
        // the tallest caption (two headline lines + PRO chip + two subline
        // lines), so a short caption simply gets more air.
        return max($y + (int) round(40 * $this->k), (int) round($this->h * 0.46));
    }

    /**
     * Solid amber chip so paid features are never mistaken for free ones.
     * Returns the y baseline below it.
     */
    private function drawProPill($img, int $x, int $y): int
    {
        $size = (int) round(30 * $this->k);
        $padX = (int) round(26 * $this->k);
        $padY = (int) round(16 * $this->k);

        $bbox  = imagettfbbox($size, 0, $this->fontBody, 'PRO');
        $textW = $bbox[2] - $bbox[0];
        $textH = $bbox[1] - $bbox[7];

        $x2 = $x + $textW + ($padX * 2);
        $y2 = $y + $textH + ($padY * 2);

        $amber = imagecolorallocate($img, ...self::AMBER);
        $this->filledRoundedRect($img, $x, $y, $x2, $y2, (int) round(($y2 - $y) / 2), $amber);

        // Dark text on solid amber — the contrast is the point.
        $ink = imagecolorallocate($img, 17, 24, 39);
        imagettftext($img, $size, 0, $x + $padX, $y2 - $padY - 2, $ink, $this->fontBody, 'PRO');

        return $y2;
    }

    /** Rounded rectangle — GD has no primitive for it. */
    private function filledRoundedRect($img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        $r = max(1, min($r, (int) (($x2 - $x1) / 2), (int) (($y2 - $y1) / 2)));

        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);

        imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    /**
     * The capture, scaled to the FULL canvas width and bled off the bottom.
     *
     * Edge-to-edge is what separates a premium store sheet from a screenshot
     * pasted on a background: there is no device frame and no margin, so the
     * UI reads as the product rather than as an image of the product. The
     * capture is only scaled — never redrawn — so what ships is the real app.
     */
    private function drawDevice($img, string $capturePath, int $top): void
    {
        $src = @imagecreatefromstring((string) file_get_contents($capturePath));
        if ($src === false) {
            $this->warn('  could not read ' . basename($capturePath));
            return;
        }

        $sw = imagesx($src);
        $sh = imagesy($src);

        $targetW = $this->w;
        $targetH = (int) round($sh * ($targetW / $sw));

        imagecopyresampled($img, $src, 0, $top, 0, 0, $targetW, $targetH, $sw, $sh);
        imagedestroy($src);

        // Hairline where the capture meets the background — separates the two
        // planes without drawing a box around the screenshot.
        $line = imagecolorallocatealpha($img, 255, 255, 255, 112);
        imagefilledrectangle($img, 0, $top, $this->w, $top, $line);
    }

    /** @return array<int, string> */
    private function wrap(string $text, int $size, string $font, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line  = '';

        foreach ($words as $word) {
            $try  = $line === '' ? $word : $line . ' ' . $word;
            $bbox = imagettfbbox($size, 0, $font, $try);

            if (($bbox[2] - $bbox[0]) > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;

                if (count($lines) === $maxLines) {
                    return $lines;
                }
            } else {
                $line = $try;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function joinedWordCount(array $lines): int
    {
        return str_word_count(implode(' ', $lines));
    }

    private function slug(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)) ?? '', '-');
    }
}
