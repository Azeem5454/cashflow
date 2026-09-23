<?php

namespace Tests\Feature\Api;

class CarryForwardOpeningBalanceTest extends ApiTestCase
{
    public function test_suggested_opening_returns_previous_books_closing_balance(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        // Older book — should NOT be the one suggested.
        $old = $this->makeBook($business, [
            'name' => 'January', 'opening_balance' => '10.00',
            'period_starts_at' => '2026-01-01', 'period_ends_at' => '2026-01-31',
        ]);
        $this->makeEntry($old, 'in', '5.00', '2026-01-10');

        // Latest period_ends_at wins.
        $latest = $this->makeBook($business, [
            'name' => 'February', 'opening_balance' => '100.00',
            'period_starts_at' => '2026-02-01', 'period_ends_at' => '2026-02-28',
        ]);
        $this->makeEntry($latest, 'in',  '250.50', '2026-02-05');
        $this->makeEntry($latest, 'out', '50.25',  '2026-02-06');

        $this->actingAsUser($owner);

        $this->getJson("/api/v1/businesses/{$business->id}/suggested-opening")
            ->assertOk()
            ->assertJson([
                'suggestedOpeningBalance' => '300.25',
                'previousBookId'          => $latest->id,
                'previousBookName'        => 'February',
            ]);
    }

    public function test_first_book_in_a_business_gets_no_carry_forward_offer(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->getJson("/api/v1/businesses/{$business->id}/suggested-opening")
            ->assertOk()
            ->assertJson([
                'suggestedOpeningBalance' => null,
                'previousBookId'          => null,
                'previousBookName'        => null,
            ]);
    }

    public function test_carry_forward_is_recomputed_server_side_not_taken_from_the_client(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        $previous = $this->makeBook($business, ['opening_balance' => '100.00', 'period_ends_at' => '2026-02-28']);
        $this->makeEntry($previous, 'in', '400.00', '2026-02-10');
        $this->actingAsUser($owner);

        // Client sends a bogus opening balance alongside carryForward — but no
        // openingBalance key, so the server computes 500.00 itself.
        $res = $this->postJson("/api/v1/businesses/{$business->id}/books", [
            'name'         => 'March',
            'carryForward' => true,
        ])->assertCreated();

        $book = \App\Models\Book::findOrFail($res->json('id'));
        $this->assertSame('500.00', bcadd((string) $book->opening_balance, '0', 2));
    }

    public function test_an_explicit_opening_balance_wins_over_carry_forward(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $previous = $this->makeBook($business, ['opening_balance' => '100.00']);
        $this->makeEntry($previous, 'in', '400.00', '2026-02-10');
        $this->actingAsUser($owner);

        $res = $this->postJson("/api/v1/businesses/{$business->id}/books", [
            'name'           => 'March',
            'carryForward'   => true,
            'openingBalance' => 7.50,
        ])->assertCreated();

        $book = \App\Models\Book::findOrFail($res->json('id'));
        $this->assertSame('7.50', bcadd((string) $book->opening_balance, '0', 2));
    }

    public function test_carry_forward_off_leaves_the_opening_balance_at_zero(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $previous = $this->makeBook($business, ['opening_balance' => '100.00']);
        $this->makeEntry($previous, 'in', '400.00', '2026-02-10');
        $this->actingAsUser($owner);

        $res = $this->postJson("/api/v1/businesses/{$business->id}/books", [
            'name'         => 'March',
            'carryForward' => false,
        ])->assertCreated();

        $book = \App\Models\Book::findOrFail($res->json('id'));
        $this->assertSame('0.00', bcadd((string) $book->opening_balance, '0', 2));
    }

    public function test_a_negative_closing_balance_carries_forward(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $previous = $this->makeBook($business, ['opening_balance' => '0.00']);
        $this->makeEntry($previous, 'out', '75.25', '2026-02-10');
        $this->actingAsUser($owner);

        $this->assertSame('-75.25', $previous->closingBalance());

        $res = $this->postJson("/api/v1/businesses/{$business->id}/books", [
            'name'         => 'March',
            'carryForward' => true,
        ])->assertCreated();

        $book = \App\Models\Book::findOrFail($res->json('id'));
        $this->assertSame('-75.25', bcadd((string) $book->opening_balance, '0', 2));
    }

    public function test_a_viewer_cannot_create_a_book_and_an_editor_can(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->makeBook($business, ['opening_balance' => '100.00']);

        $viewer = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');
        $this->actingAsUser($viewer);
        $this->postJson("/api/v1/businesses/{$business->id}/books", ['name' => 'Nope', 'carryForward' => true])
            ->assertForbidden();

        $editor = $this->makeUser();
        $this->addMember($business, $editor, 'editor');
        $this->actingAsUser($editor);
        $this->postJson("/api/v1/businesses/{$business->id}/books", ['name' => 'Yes', 'carryForward' => true])
            ->assertCreated();
    }

    public function test_a_viewer_can_still_read_the_carry_forward_suggestion(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->makeBook($business, ['name' => 'Jan', 'opening_balance' => '100.00']);

        $viewer = $this->makeUser();
        $this->addMember($business, $viewer, 'viewer');
        $this->actingAsUser($viewer);

        $this->getJson("/api/v1/businesses/{$business->id}/suggested-opening")
            ->assertOk()
            ->assertJsonPath('previousBookName', 'Jan');
    }

    public function test_a_non_member_cannot_read_the_carry_forward_suggestion(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->makeBook($business);

        $this->actingAsUser($this->makeUser());
        $this->getJson("/api/v1/businesses/{$business->id}/suggested-opening")->assertNotFound();
    }
}
