<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Business;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The optional per-business branding (logo + phone + email) has to actually
 * reach the places it was promised: PDF export, CSV export, email report.
 */
class BusinessBrandingOutputTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Business, 2: Book} */
    private function proBook(bool $withBranding): array
    {
        $owner = User::factory()->create();
        $owner->plan = 'pro';
        $owner->save();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name'     => 'Acme Trading',
            'currency' => 'USD',
        ] + ($withBranding ? [
            'contact_phone' => '+1 555 0142',
            'contact_email' => 'books@acme.test',
        ] : []));
        $business->members()->attach($owner->id, ['role' => 'owner']);

        if ($withBranding) {
            // A real 2×2 PNG so the GD normaliser has something to decode.
            $img = imagecreatetruecolor(2, 2);
            ob_start();
            imagepng($img);
            $business->storeLogo((string) ob_get_clean());
            imagedestroy($img);
        }

        $book = $business->books()->create(['name' => 'March', 'opening_balance' => '10.00']);
        $book->entries()->create([
            'type' => 'in', 'amount' => '25.00', 'description' => 'Invoice 1', 'date' => '2026-03-02',
        ]);

        return [$owner, $business, $book];
    }

    public function test_csv_export_carries_the_business_name_and_contact_details(): void
    {
        [$owner, $business, $book] = $this->proBook(withBranding: true);

        $csv = $this->actingAs($owner)
            ->get(route('businesses.books.export.csv', [$business, $book]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Acme Trading', $csv);
        $this->assertStringContainsString('+1 555 0142', $csv);
        $this->assertStringContainsString('books@acme.test', $csv);
        // The ledger itself is still intact.
        $this->assertStringContainsString('Running Balance', $csv);
        $this->assertStringContainsString('Invoice 1', $csv);
    }

    public function test_pdf_export_header_embeds_the_logo_and_contact_details(): void
    {
        [, $business, $book] = $this->proBook(withBranding: true);

        $entries = $book->entries()->get();
        $entries->each(fn ($e) => $e->running_balance = '35.00');

        $html = view('exports.book-pdf', [
            'business'    => $business,
            'book'        => $book,
            'entries'     => $entries,
            'totalIn'     => '25.00',
            'totalOut'    => '0.00',
            'balance'     => '35.00',
            'logoDataUri' => 'data:image/png;base64,AAAA',
        ])->render();

        $this->assertStringContainsString('data:image/png;base64,AAAA', $html);
        $this->assertStringContainsString('+1 555 0142', $html);
        $this->assertStringContainsString('books@acme.test', $html);
    }

    public function test_pdf_export_renders_without_any_branding(): void
    {
        [, $business, $book] = $this->proBook(withBranding: false);

        $entries = $book->entries()->get();
        $entries->each(fn ($e) => $e->running_balance = '35.00');

        $html = view('exports.book-pdf', [
            'business'    => $business,
            'book'        => $book,
            'entries'     => $entries,
            'totalIn'     => '25.00',
            'totalOut'    => '0.00',
            'balance'     => '35.00',
            'logoDataUri' => null,
        ])->render();

        $this->assertStringNotContainsString('biz-logo"', $html);
        $this->assertStringContainsString('Acme Trading', $html);
    }

    public function test_email_report_renders_with_and_without_a_logo(): void
    {
        foreach ([true, false] as $branded) {
            [, $business, $book] = $this->proBook(withBranding: $branded);

            $schedule = ReportSchedule::create([
                'book_id'    => $book->id,
                'frequency'  => 'monthly',
                'recipients' => ['someone@example.test'],
                'is_active'  => true,
            ]);

            $html = (new \App\Mail\BookEmailReport(
                $book->fresh(['business']),
                $schedule->buildReportData(),
                'monthly'
            ))->render();

            $this->assertStringContainsString('Acme Trading', $html);

            if ($branded) {
                $this->assertStringContainsString($business->logo_key, $html);
                $this->assertStringContainsString('+1 555 0142', $html);
            } else {
                $this->assertStringNotContainsString('business-' . $business->id . '-logo', $html);
            }
        }
    }
}
