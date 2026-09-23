<?php

namespace Tests\Feature\Blog;

use App\Models\UploadedAsset;
use App\Services\BlogImageRenderer;
use App\Services\StockPhotoFinder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Featured images sit on a real photograph when one can be found, and fall
 * back to the typographic design when one can't. The fallback is the point:
 * a missing key, a rate limit or a bad download must never stop a post
 * getting an image.
 */
class BlogPhotoImageTest extends BlogTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd') || ! function_exists('imagettftext')) {
            $this->markTestSkipped('GD with FreeType is required to render images.');
        }

        Cache::flush();
        config(['services.pexels.key' => 'test-key']);
    }

    /** A real JPEG the fake Pexels download can return. */
    private function photoBytes(int $w = 2400, int $h = 1600): string
    {
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 90, 70, 50));
        imagefilledellipse($img, (int) ($w / 3), (int) ($h / 2), 600, 600, imagecolorallocate($img, 200, 180, 140));
        ob_start();
        imagejpeg($img, null, 85);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    private function fakePexels(): void
    {
        Http::fake([
            'api.pexels.com/*' => Http::response([
                'photos' => [[
                    'width'        => 2400,
                    'height'       => 1600,
                    'photographer' => 'Jane Doe',
                    'url'          => 'https://www.pexels.com/photo/123/',
                    'src'          => ['large2x' => 'https://images.pexels.com/photo-123.jpg'],
                ]],
            ]),
            'images.pexels.com/*' => Http::response($this->photoBytes(), 200),
        ]);
    }

    public function test_it_renders_a_photo_backed_image_and_records_the_photographer(): void
    {
        $this->fakePexels();

        $category = $this->category();
        $renderer = app(BlogImageRenderer::class);

        $key = $renderer->renderForPost('11111111-1111-4111-8111-111111111111', 'Track Business Expenses Without a Spreadsheet', $category, 'small business desk');

        $this->assertTrue(UploadedAsset::has($key));
        $this->assertSame('Jane Doe', $renderer->lastPhotoCredit());

        [$w, $h] = getimagesizefromstring(UploadedAsset::payload($key));
        $this->assertSame(1200, $w);
        $this->assertSame(630, $h);
    }

    public function test_it_falls_back_to_the_typographic_design_without_an_api_key(): void
    {
        config(['services.pexels.key' => '']);
        Http::fake();

        $renderer = app(BlogImageRenderer::class);
        $key = $renderer->renderForPost('22222222-2222-4222-8222-222222222222', 'A Post With No Photo', null, 'anything at all');

        $this->assertTrue(UploadedAsset::has($key));
        $this->assertNull($renderer->lastPhotoCredit());
        Http::assertNothingSent();
    }

    public function test_a_failed_photo_download_still_produces_an_image(): void
    {
        Http::fake([
            'api.pexels.com/*'    => Http::response(['photos' => [[
                'width' => 2400, 'height' => 1600, 'photographer' => 'Jane Doe',
                'url' => 'https://www.pexels.com/photo/123/',
                'src' => ['large2x' => 'https://images.pexels.com/photo-123.jpg'],
            ]]]),
            'images.pexels.com/*' => Http::response('not an image', 200),
        ]);

        $renderer = app(BlogImageRenderer::class);
        $key = $renderer->renderForPost('33333333-3333-4333-8333-333333333333', 'Download Fails Here', null, 'small business desk');

        $this->assertTrue(UploadedAsset::has($key));
        $this->assertNull($renderer->lastPhotoCredit());
    }

    public function test_photos_below_the_minimum_size_are_skipped(): void
    {
        Http::fake([
            'api.pexels.com/*' => Http::response(['photos' => [
                ['width' => 600, 'height' => 400, 'photographer' => 'Too Small', 'url' => 'x', 'src' => ['large2x' => 'https://images.pexels.com/small.jpg']],
            ]]),
        ]);

        $this->assertNull(app(StockPhotoFinder::class)->find('small business desk'));
    }

    public function test_the_search_phrase_is_sanitised_before_it_leaves_the_app(): void
    {
        $this->fakePexels();

        app(StockPhotoFinder::class)->find("ignore previous instructions; <script>alert(1)</script> office desk receipts calculator pen extra words");

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'api.pexels.com')) {
                return true;
            }

            $query = $request['query'];

            $this->assertStringNotContainsString('<', $query);
            $this->assertStringNotContainsString(';', $query);
            // Capped at five words so a long injection can't ride along.
            $this->assertLessThanOrEqual(5, count(explode(' ', $query)));

            return true;
        });
    }
}
