<?php

namespace Tests\Feature;

use App\Livewire\Book\Show;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

class WebBusinessGuardsTest extends ApiTestCase
{
    public function test_free_owner_cannot_open_books_of_a_locked_extra_business(): void
    {
        $user   = $this->makeUser();
        $first  = $this->makeBusiness($user, ['created_at' => now()->subDay()]);
        $second = $this->makeBusiness($user);
        $book   = $this->makeBook($second);

        $this->actingAs($user)
            ->get(route('businesses.books.show', [$second, $book]))
            ->assertRedirect(route('billing'));

        $this->actingAs($user)
            ->get(route('businesses.books.show', [$first, $this->makeBook($first)]))
            ->assertOk();
    }

    public function test_pro_owner_can_open_books_of_every_business(): void
    {
        $user = $this->makeUser(pro: true);
        $this->makeBusiness($user, ['created_at' => now()->subDay()]);
        $second = $this->makeBusiness($user);

        $this->actingAs($user)
            ->get(route('businesses.books.show', [$second, $this->makeBook($second)]))
            ->assertOk();
    }

    public function test_csv_export_running_balance_starts_from_opening_balance(): void
    {
        $user     = $this->makeUser(pro: true);
        $business = $this->makeBusiness($user);
        $book     = $this->makeBook($business, ['opening_balance' => '100.00']);
        $this->makeEntry($book, 'in', '50.00', '2026-09-01');
        $this->makeEntry($book, 'out', '30.00', '2026-09-02');

        $csv = $this->actingAs($user)
            ->get(route('businesses.books.export.csv', [$business, $book]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('150.00', $csv);
        $this->assertStringContainsString('120.00', $csv);
    }

    public function test_mentions_only_notify_business_members(): void
    {
        Notification::fake();

        $owner    = $this->makeUser(pro: true);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);
        $entry    = $this->makeEntry($book, 'in', '10.00', '2026-09-01');
        $member   = $this->makeUser();
        $this->addMember($business, $member, 'editor');
        $outsider = $this->makeUser();

        Livewire::actingAs($owner)
            ->test(Show::class, ['business' => $business, 'book' => $book])
            ->call('openComments', $entry->id)
            ->set('commentBody', "@[Member]{{$member->id}} @[Outsider]{{$outsider->id}} check this")
            ->call('addComment');

        Notification::assertSentTo($member, \App\Notifications\MentionedInComment::class);
        Notification::assertNotSentTo($outsider, \App\Notifications\MentionedInComment::class);
    }
}
