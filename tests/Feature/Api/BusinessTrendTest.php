<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessTrendTest extends ApiTestCase
{
    public function test_trend_holds_seven_daily_nets_oldest_first(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        // Today, and three days ago.
        $this->makeEntry($book, 'in',  '100.00', '2026-09-23');
        $this->makeEntry($book, 'out', '40.00',  '2026-09-23');
        $this->makeEntry($book, 'out', '25.00',  '2026-09-20');
        // Outside the 7-day window — must not appear.
        $this->makeEntry($book, 'in',  '999.00', '2026-09-01');

        $trend = Business::trends([$business->id])[$business->id]['trend'];

        $this->assertCount(7, $trend);
        $this->assertSame(60.0,  $trend[6]);  // today: 100 − 40
        $this->assertSame(-25.0, $trend[3]);  // 2026-09-20
        $this->assertSame(0.0,   $trend[0]);

        Carbon::setTestNow();
    }

    public function test_month_change_compares_this_month_with_last_month(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        $this->makeEntry($book, 'in', '100.00', '2026-08-10'); // last month net = 100
        $this->makeEntry($book, 'in', '150.00', '2026-09-02'); // this month net = 150

        $this->assertSame(50.0, Business::trends([$business->id])[$business->id]['monthChangePct']);

        Carbon::setTestNow();
    }

    public function test_month_change_is_null_when_last_month_had_no_activity(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->makeEntry($book, 'in', '150.00', '2026-09-02');

        $this->assertNull(Business::trends([$business->id])[$business->id]['monthChangePct']);

        Carbon::setTestNow();
    }

    public function test_trends_are_per_business_and_cost_one_query_for_the_whole_set(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner = $this->makeUser(pro: true);
        $a     = $this->makeBusiness($owner, ['currency' => 'USD']);
        $b     = $this->makeBusiness($owner, ['currency' => 'EUR']);
        $c     = $this->makeBusiness($owner); // no books at all

        $this->makeEntry($this->makeBook($a), 'in',  '10.00', '2026-09-23');
        $this->makeEntry($this->makeBook($b), 'out', '7.00',  '2026-09-23');

        $ids = [$a->id, $b->id, $c->id];

        DB::enableQueryLog();
        $trends = Business::trends($ids);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries, 'Business::trends() must stay one query for any number of businesses.');

        $this->assertSame(10.0, $trends[$a->id]['trend'][6]);
        $this->assertSame(-7.0, $trends[$b->id]['trend'][6]);
        $this->assertSame(array_fill(0, 7, 0.0), $trends[$c->id]['trend']);
        $this->assertNull($trends[$c->id]['monthChangePct']);

        Carbon::setTestNow();
    }

    public function test_business_list_exposes_trend_and_month_change(): void
    {
        Carbon::setTestNow('2026-09-23 10:00:00');

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $this->makeEntry($book, 'in', '100.00', '2026-08-10');
        $this->makeEntry($book, 'in', '50.00',  '2026-09-22');

        $this->actingAsUser($owner);
        $row = collect($this->getJson('/api/v1/businesses')->assertOk()->json('data'))
            ->firstWhere('id', $business->id);

        $this->assertCount(7, $row['trend']);
        // JSON round-trips whole numbers as ints — compare loosely.
        $this->assertEquals(50, $row['trend'][5]);
        $this->assertEquals(-50, $row['monthChangePct']);

        // Detail endpoint doesn't compute it — the key is simply absent.
        $detail = $this->getJson("/api/v1/businesses/{$business->id}")->assertOk()->json('data');
        $this->assertArrayNotHasKey('trend', $detail);

        Carbon::setTestNow();
    }
}
