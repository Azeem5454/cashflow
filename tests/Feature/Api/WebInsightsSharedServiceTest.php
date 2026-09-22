<?php

namespace Tests\Feature\Api;

use App\Livewire\Book\Show;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/** Guards the web Reports tab after extracting insights building into BookInsightsService. */
class WebInsightsSharedServiceTest extends ApiTestCase
{
    public function test_web_generate_insights_still_works_via_shared_service(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => '{"sentiment":"watch","sentiment_reason":"Tight","bullets":["one"],"tip":"Save"}']],
                'usage'   => ['input_tokens' => 10, 'output_tokens' => 5],
            ]),
        ]);

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        foreach (range(1, 3) as $i) {
            $this->makeEntry($book, 'in', '10.00', "2026-01-0{$i}");
        }

        $this->actingAs($owner);

        Livewire::test(Show::class, ['business' => $business, 'book' => $book])
            ->call('generateInsights')
            ->assertSet('aiInsightsError', '')
            ->assertSet('aiInsightsData.sentiment', 'watch');

        $this->assertSame('watch', json_decode($book->fresh()->ai_insights_cache, true)['sentiment']);
    }
}
