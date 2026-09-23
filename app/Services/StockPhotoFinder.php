<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds a real photograph to sit behind a blog post's featured image.
 *
 * Uses the Pexels API. The Pexels licence allows free commercial use and
 * modification without attribution, but we keep the photographer's name so
 * the post can credit them anyway — it costs nothing and it's the decent
 * thing to do.
 *
 * Every failure path returns null: no API key, a search with no usable
 * result, a timeout, a download that isn't an image. The caller then falls
 * back to the typographic design, so a photo is always an upgrade and never
 * a dependency.
 */
class StockPhotoFinder
{
    /** Pexels caps at 200 requests/hour on the free tier; we make ~1/day. */
    private const ENDPOINT = 'https://api.pexels.com/v1/search';

    private const TIMEOUT = 12;

    /** Landscape only, large enough that a 1200×630 crop stays sharp. */
    private const MIN_WIDTH  = 1200;
    private const MIN_HEIGHT = 630;

    /**
     * @return array{bytes: string, credit: string, url: string}|null
     */
    public function find(string $query): ?array
    {
        $key = trim((string) config('services.pexels.key'));
        if ($key === '') {
            return null;
        }

        $query = $this->normaliseQuery($query);
        if ($query === '') {
            return null;
        }

        try {
            $photo = $this->search($key, $query);
            if ($photo === null) {
                return null;
            }

            $bytes = $this->download($photo['src']);
            if ($bytes === null) {
                return null;
            }

            return [
                'bytes'  => $bytes,
                'credit' => $photo['photographer'],
                'url'    => $photo['url'],
            ];
        } catch (\Throwable $e) {
            Log::warning('StockPhotoFinder: lookup failed', [
                'query' => $query,
                'err'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Pick a photo for the query. Results are cached for a week so a
     * regenerate doesn't spend a request, and so the same topic keeps the
     * same picture within a run of retries.
     *
     * @return array{src: string, photographer: string, url: string}|null
     */
    private function search(string $apiKey, string $query): ?array
    {
        return Cache::remember(
            'pexels:' . md5($query),
            now()->addWeek(),
            function () use ($apiKey, $query) {
                $response = Http::withHeaders(['Authorization' => $apiKey])
                    ->timeout(self::TIMEOUT)
                    ->get(self::ENDPOINT, [
                        'query'       => $query,
                        'orientation' => 'landscape',
                        'per_page'    => 15,
                        // Dark photos sit under a navy scrim far better than
                        // bright ones, and the title stays readable.
                        'color'       => 'black',
                    ]);

                if (! $response->successful()) {
                    Log::warning('StockPhotoFinder: Pexels returned ' . $response->status(), ['query' => $query]);
                    return null;
                }

                foreach ($response->json('photos') ?? [] as $photo) {
                    if (($photo['width'] ?? 0) < self::MIN_WIDTH || ($photo['height'] ?? 0) < self::MIN_HEIGHT) {
                        continue;
                    }

                    $src = $photo['src']['large2x'] ?? $photo['src']['large'] ?? $photo['src']['original'] ?? null;
                    if (! is_string($src) || $src === '') {
                        continue;
                    }

                    return [
                        'src'          => $src,
                        'photographer' => (string) ($photo['photographer'] ?? 'Pexels'),
                        'url'          => (string) ($photo['url'] ?? 'https://www.pexels.com'),
                    ];
                }

                return null;
            }
        );
    }

    private function download(string $url): ?string
    {
        $response = Http::timeout(self::TIMEOUT)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $bytes = $response->body();

        // Cheap sanity check — GD will reject anything that isn't an image
        // anyway, but failing here keeps the error message useful.
        if (strlen($bytes) < 1024 || @getimagesizefromstring($bytes) === false) {
            return null;
        }

        return $bytes;
    }

    /**
     * Keep the query to a few plain words. Claude writes these, so strip
     * anything that looks like an instruction or a URL before it reaches a
     * third-party API.
     */
    private function normaliseQuery(string $query): string
    {
        $query = preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $query) ?? '';
        $words = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_slice($words, 0, 5));
    }
}
