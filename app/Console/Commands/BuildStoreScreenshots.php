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
        $this->drawGlow($img);

        $y = $this->drawCaption($img, $headline, $subline, $isPro);
        $this->drawDevice($img, $capturePath, $y);

        imagepng($img, $outPath, 6);
        imagedestroy($img);
    }

    private function drawBackground($img): void
    {
        imagefilledrectangle($img, 0, 0, $this->w, $this->h, imagecolorallocate($img, ...self::NAVY));

        // Faint dot grid, 24px pitch on the reference canvas.
        $dot   = imagecolorallocatealpha($img, 255, 255, 255, 120);
        $pitch = (int) round(24 * $this->k);
        for ($x = 0; $x < $this->w; $x += $pitch) {
            for ($y = 0; $y < $this->h; $y += $pitch) {
                imagesetpixel($img, $x, $y, $dot);
            }
        }
    }

    /** One soft blue glow behind where the device sits. */
    private function drawGlow($img): void
    {
        $cx = (int) ($this->w / 2);
        $cy = (int) ($this->h * 0.52);

        for ($i = 16; $i >= 1; $i--) {
            $r     = (int) ($this->w * 0.14 * $i);
            $alpha = 122 - (int) (108 * (($i - 1) / 15));
            if ($alpha >= 127 || $alpha < 0) continue;
            imagefilledellipse($img, $cx, $cy, $r, $r, imagecolorallocatealpha($img, ...self::BLUE, ...[$alpha]));
        }
    }

    /** Caption block at the top. Returns the y where the device may start. */
    private function drawCaption($img, string $headline, string $subline, bool $isPro): int
    {
        $white = imagecolorallocate($img, ...self::WHITE);
        $light = imagecolorallocate($img, ...self::LIGHT);

        $margin  = (int) round(72 * $this->k);
        $maxW    = $this->w - ($margin * 2);
        $headPx  = (int) round(64 * $this->k);
        $subPx   = (int) round(30 * $this->k);

        // Shrink the headline until it fits two lines.
        $lines = [];
        foreach ([$headPx, (int) ($headPx * 0.86), (int) ($headPx * 0.74)] as $size) {
            $lines = $this->wrap($headline, $size, $this->fontDisplay, $maxW, 2);
            if ($this->joinedWordCount($lines) >= str_word_count($headline)) {
                $headPx = $size;
                break;
            }
        }

        $y = (int) round(150 * $this->k);

        foreach ($lines as $line) {
            imagettftext($img, $headPx, 0, $margin, $y, $white, $this->fontDisplay, $line);
            $y += (int) ($headPx * 1.24);
        }

        if ($isPro) {
            $this->drawProPill($img, $margin, $y);
            $y += (int) round(28 * $this->k);
        }

        $y += (int) round(18 * $this->k);

        foreach ($this->wrap($subline, $subPx, $this->fontBody, $maxW, 2) as $line) {
            imagettftext($img, $subPx, 0, $margin, $y, $light, $this->fontBody, $line);
            $y += (int) ($subPx * 1.42);
        }

        return $y + (int) round(56 * $this->k);
    }

    private function drawProPill($img, int $x, int $y): void
    {
        $size  = (int) round(20 * $this->k);
        $padX  = (int) round(18 * $this->k);
        $padY  = (int) round(10 * $this->k);

        $bbox  = imagettfbbox($size, 0, $this->fontBody, 'PRO');
        $textW = $bbox[2] - $bbox[0];

        $x2 = $x + $textW + ($padX * 2);
        $y2 = $y + $size + ($padY * 2);

        imagefilledrectangle($img, $x, $y, $x2, $y2, imagecolorallocatealpha($img, ...self::AMBER, ...[100]));
        imagettftext($img, $size, 0, $x + $padX, $y2 - $padY - 2, imagecolorallocate($img, ...self::AMBER), $this->fontBody, 'PRO');
    }

    /**
     * The capture, scaled to 82% of the canvas width and bled off the bottom
     * edge. Only scaled — never recomposed — so the UI stays exactly as the
     * app rendered it.
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

        $targetW = (int) round($this->w * 0.82);
        $targetH = (int) round($sh * ($targetW / $sw));

        $x = (int) round(($this->w - $targetW) / 2);

        // Bleed off the bottom rather than shrinking the UI to fit.
        $visibleH = min($targetH, $this->h - $top);

        $frame = imagecolorallocatealpha($img, 255, 255, 255, 108);
        imagesetthickness($img, max(2, (int) round(3 * $this->k)));
        imagerectangle($img, $x - 2, $top - 2, $x + $targetW + 2, $this->h, $frame);

        imagecopyresampled(
            $img, $src,
            $x, $top,
            0, 0,
            $targetW, $targetH,
            $sw, $sh
        );

        // Re-clip: anything drawn past the canvas is discarded by GD anyway,
        // but keep the visible height honest for callers reading this code.
        unset($visibleH);

        imagedestroy($src);
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
