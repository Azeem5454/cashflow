<?php

namespace Tests\Feature\Api;

class BusinessBalanceTest extends ApiTestCase
{
    public function test_business_list_and_detail_include_net_balance_with_opening_balances(): void
    {
        $owner = $this->makeUser(pro: true);
        $a = $this->makeBusiness($owner);
        $b = $this->makeBusiness($owner);

        $book1 = $this->makeBook($a, ['opening_balance' => '100.00']);
        $book2 = $this->makeBook($a, ['opening_balance' => '50.50']);
        $this->makeEntry($book1, 'in', '200.00', '2026-09-01');
        $this->makeEntry($book2, 'out', '30.25', '2026-09-02');
        // $b has no books at all
        $this->actingAsUser($owner);

        $list = collect($this->getJson('/api/v1/businesses')->assertOk()->json('data'))->keyBy('id');
        $this->assertSame('320.25', $list[$a->id]['balance']);
        $this->assertSame('0.00', $list[$b->id]['balance']);

        $this->getJson("/api/v1/businesses/{$a->id}")->assertOk()->assertJsonPath('data.balance', '320.25');
    }
}
