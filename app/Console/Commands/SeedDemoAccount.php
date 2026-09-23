<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Business;
use App\Models\Entry;
use App\Models\EntryComment;
use App\Models\Invitation;
use App\Models\RecurringEntry;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\PlanService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds the reviewer / demo account described in
 * project-mobile/docs/launch/demo-account.md.
 *
 * App Store and Play reviewers cannot create accounts, so a login-only app has
 * to ship working credentials. This seeds a believable studio with two months
 * of books so every gated feature (reports, recurring, comments, email
 * reports, team roles) has something real behind it.
 *
 * Dates are anchored to the current month and entries dated after today are
 * skipped, so the account never looks stale or shows future transactions.
 *
 * Idempotent: --fresh deletes the demo users first (cascades to their data).
 */
class SeedDemoAccount extends Command
{
    protected $signature = 'demo:seed
        {--password= : Password for all demo accounts (generated when omitted)}
        {--fresh : Delete existing demo accounts and rebuild}';

    protected $description = 'Create the store-reviewer demo account with sample books and entries';

    private const OWNER  = 'demo-reviewer@thecashfox.com';
    private const EDITOR = 'maya.demo@thecashfox.com';
    private const VIEWER = 'daniel.demo@thecashfox.com';
    private const INVITE = 'sam.demo@thecashfox.com';

    private const CATEGORIES = [
        'Client Work', 'Retainers', 'Workshops', 'Rent', 'Utilities', 'Software',
        'Contractors', 'Travel', 'Meals', 'Office Supplies', 'Cleaning',
        'Printing', 'Shipping', 'Professional Fees',
    ];

    private const PAYMENT_MODES = ['Bank Transfer', 'Card', 'Cash', 'PayPal', 'Stripe'];

    public function handle(PlanService $plans): int
    {
        $existing = User::whereIn('email', [self::OWNER, self::EDITOR, self::VIEWER])->get();

        if ($existing->isNotEmpty()) {
            if (! $this->option('fresh')) {
                $this->error('Demo accounts already exist. Re-run with --fresh to rebuild them.');
                return self::FAILURE;
            }

            $this->warn('Deleting ' . $existing->count() . ' existing demo account(s) and their data…');
            $existing->each->delete();
        }

        $password = $this->option('password') ?: 'FoxDemo!' . random_int(1000, 9999);

        DB::transaction(function () use ($password, $plans) {
            $owner  = $this->makeUser('Alex Rivera', self::OWNER, $password);
            $editor = $this->makeUser('Maya Chen', self::EDITOR, $password);
            $viewer = $this->makeUser('Daniel Okafor', self::VIEWER, $password);

            // Force Pro without Stripe — reviewers must see Pro features free.
            $plans->forcePro($owner);

            $studio = $this->makeBusiness($owner, 'Brightside Studio', 'Branding and design studio');
            $studio->members()->attach($editor->id, ['role' => 'editor']);
            $studio->members()->attach($viewer->id, ['role' => 'viewer']);

            // A pending invite so the team screen shows that state too.
            Invitation::create([
                'business_id' => $studio->id,
                'email'       => self::INVITE,
                'role'        => 'editor',
                'token'       => Str::random(48),
                'expires_at'  => now()->addDays(3),
            ]);

            $cafe = $this->makeBusiness($owner, 'Harbor Street Café', 'Neighbourhood coffee shop');

            $thisMonth = CarbonImmutable::today()->startOfMonth();
            $lastMonth = $thisMonth->subMonthNoOverflow();

            $prev = $this->makeBook($studio, $lastMonth, '2800.00');
            $this->fill($prev, $this->lastMonthEntries(), $lastMonth, $owner, $editor);

            $hero = $this->makeBook($studio, $thisMonth, $prev->closingBalance());
            $this->fill($hero, $this->thisMonthEntries(), $thisMonth, $owner, $editor);

            $this->addRecurring($hero, $thisMonth);
            $this->addComments($hero, $owner, $editor);

            ReportSchedule::create([
                'book_id'    => $hero->id,
                'frequency'  => 'weekly',
                'recipients' => [self::OWNER],
                'is_active'  => true,
            ]);

            $cafeBook = $this->makeBook($cafe, $thisMonth, '1200.00');
            $this->fill($cafeBook, [
                [3,  'in',  '1840.00', 'Weekend sales',                    'Sales',      'Cash',          null],
                [4,  'out', '412.60',  'Coffee beans & milk — supplier',   'Stock',      'Bank Transfer', null],
                [6,  'out', '95.00',   'Petty cash — napkins, cups',       'Petty Cash', 'Cash',          null],
            ], $thisMonth, $owner, $editor);
        });

        $this->newLine();
        $this->info('Demo account ready.');
        $this->newLine();
        $this->table(['Field', 'Value'], [
            ['Reviewer email', self::OWNER],
            ['Password',       $password],
            ['Plan',           'Pro (admin grant, no Stripe charge)'],
            ['Team',           self::EDITOR . ' (editor), ' . self::VIEWER . ' (viewer)'],
        ]);
        $this->newLine();
        $this->line('Paste the email + password into Play Console → App content → Sign in details,');
        $this->line('and into App Store Connect → App Review Information.');

        return self::SUCCESS;
    }

    private function makeUser(string $name, string $email, string $password): User
    {
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function makeBusiness(User $owner, string $name, string $description): Business
    {
        $business = Business::create([
            'owner_id'    => $owner->id,
            'name'        => $name,
            'description' => $description,
            'currency'    => 'USD',
        ]);

        $business->members()->attach($owner->id, ['role' => 'owner']);

        return $business;
    }

    private function makeBook(Business $business, CarbonImmutable $month, string $opening): Book
    {
        $book = $business->books()->create([
            'name'             => $month->format('F Y'),
            'opening_balance'  => $opening,
            'period_starts_at' => $month->toDateString(),
            'period_ends_at'   => $month->endOfMonth()->toDateString(),
        ]);

        foreach (self::CATEGORIES as $name) {
            $book->categories()->create(['name' => $name]);
        }

        foreach (self::PAYMENT_MODES as $name) {
            $book->paymentModes()->create(['name' => $name]);
        }

        return $book;
    }

    /**
     * @param  array<int, array{0:int,1:string,2:string,3:string,4:string,5:string,6:?string}>  $rows
     *         [day, type, amount, description, category, payment mode, reference]
     */
    private function fill(Book $book, array $rows, CarbonImmutable $month, User $owner, User $editor): void
    {
        $today = CarbonImmutable::today();

        // Give the editor a share of the entries so the ledger shows "by Maya"
        // and the activity feed has two people in it.
        $editorDays = [4, 9, 12, 18, 22];

        foreach ($rows as [$day, $type, $amount, $description, $category, $mode, $reference]) {
            $date = $month->addDays($day - 1);

            // Never show a transaction dated in the future.
            if ($date->greaterThan($today)) {
                continue;
            }

            $book->entries()->create([
                'type'         => $type,
                'amount'       => $amount,
                'description'  => $description,
                'date'         => $date->toDateString(),
                'category'     => $category,
                'payment_mode' => $mode,
                'reference'    => $reference,
                'created_by'   => in_array($day, $editorDays, true) ? $editor->id : $owner->id,
            ]);
        }

        $book->touch();
    }

    /** @return array<int, array{0:int,1:string,2:string,3:string,4:string,5:string,6:?string}> */
    private function lastMonthEntries(): array
    {
        return [
            [1,  'out', '1350.00', 'Studio rent',                                   'Rent',        'Bank Transfer', null],
            [3,  'in',  '3200.00', 'Website redesign — Atlas Physio (milestone 1)',  'Client Work', 'Bank Transfer', 'INV-1036'],
            [5,  'out', '54.99',   'Adobe Creative Cloud',                          'Software',    'Card',          null],
            [8,  'out', '89.00',   'Internet — fibre plan',                         'Utilities',   'Bank Transfer', null],
            [12, 'in',  '600.00',  'Retainer — Lumen Yoga',                         'Retainers',   'PayPal',        'INV-1037'],
            [14, 'out', '132.40',  'Electricity bill',                              'Utilities',   'Bank Transfer', null],
            [18, 'out', '700.00',  'Freelance copywriter',                          'Contractors', 'Bank Transfer', 'CT-06'],
            [21, 'out', '96.00',   'Fuel & tolls — client shoot',                   'Travel',      'Card',          null],
            [25, 'in',  '1500.00', 'Menu design — Harbor Street Café',              'Client Work', 'Bank Transfer', 'INV-1038'],
            [28, 'out', '64.25',   'Team lunch — project wrap',                     'Meals',       'Card',          null],
        ];
    }

    /** @return array<int, array{0:int,1:string,2:string,3:string,4:string,5:string,6:?string}> */
    private function thisMonthEntries(): array
    {
        return [
            [1,  'in',  '2400.00', 'Logo & brand kit — Northwind Bakery (50% deposit)', 'Client Work',       'Bank Transfer', 'INV-1041'],
            [1,  'out', '1350.00', 'Studio rent',                                       'Rent',              'Bank Transfer', null],
            [2,  'out', '54.99',   'Adobe Creative Cloud',                              'Software',          'Card',          null],
            [2,  'out', '18.40',   'Coffee & pastries — client kickoff',                'Meals',             'Cash',          null],
            [3,  'out', '120.00',  'Fuel — site visit to client warehouse',             'Travel',            'Card',          null],
            [4,  'in',  '850.00',  'Social media templates — Lumen Yoga',               'Client Work',       'PayPal',        'INV-1042'],
            [5,  'out', '60.00',   'Office cleaning',                                   'Cleaning',          'Cash',          null],
            [5,  'out', '42.75',   'Printer paper & ink',                               'Office Supplies',   'Card',          null],
            [8,  'out', '89.00',   'Internet — fibre plan',                             'Utilities',         'Bank Transfer', null],
            [8,  'in',  '3200.00', 'Website redesign — Atlas Physio (milestone 2)',     'Client Work',       'Bank Transfer', 'INV-1039'],
            [9,  'out', '15.00',   'Figma — extra seat',                                'Software',          'Card',          null],
            [10, 'out', '236.50',  'Train tickets — Berlin design conference',          'Travel',            'Card',          null],
            [11, 'out', '32.60',   'Team lunch',                                        'Meals',             'Card',          null],
            [12, 'out', '60.00',   'Office cleaning',                                   'Cleaning',          'Cash',          null],
            [12, 'out', '900.00',  'Freelance illustrator',                             'Contractors',       'Bank Transfer', 'CT-07'],
            [14, 'in',  '450.00',  'Workshop ticket sales — Intro to Branding',         'Workshops',         'Stripe',        null],
            [15, 'out', '148.20',  'Electricity bill',                                  'Utilities',         'Bank Transfer', null],
            [15, 'out', '25.00',   'Courier — print proofs to client',                  'Shipping',          'Cash',          null],
            [16, 'in',  '2400.00', 'Logo & brand kit — Northwind Bakery (final 50%)',   'Client Work',       'Bank Transfer', 'INV-1041'],
            [17, 'out', '310.00',  'Business cards & brochures print run',              'Printing',          'Card',          'PO-221'],
            [18, 'out', '12.99',   'Stock photo credits',                               'Software',          'Card',          null],
            [19, 'out', '60.00',   'Office cleaning',                                   'Cleaning',          'Cash',          null],
            [19, 'out', '74.30',   'Hotel taxi & meals — Berlin',                       'Travel',            'Card',          null],
            [21, 'in',  '600.00',  'Retainer — Lumen Yoga',                             'Retainers',         'PayPal',        'INV-1043'],
            [22, 'out', '38.90',   'Desk lamp & cables',                                'Office Supplies',   'Card',          null],
            [22, 'out', '9.50',    'Parking — client meeting',                          'Travel',            'Cash',          null],
            [23, 'in',  '1200.00', 'Packaging design — Green Leaf Tea',                 'Client Work',       'Bank Transfer', 'INV-1044'],
            [24, 'out', '220.00',  'Accountant — quarterly review',                     'Professional Fees', 'Bank Transfer', null],
            [25, 'out', '47.80',   'Groceries for studio kitchen',                      'Office Supplies',   'Cash',          null],
        ];
    }

    /** Weekly cleaning rule, with the entries it already produced linked to it. */
    private function addRecurring(Book $book, CarbonImmutable $month): void
    {
        $start = $month->addDays(4); // the 5th — first cleaning of the month

        $rule = RecurringEntry::create([
            'book_id'      => $book->id,
            'type'         => 'out',
            'amount'       => '60.00',
            'description'  => 'Office cleaning',
            'category'     => 'Cleaning',
            'payment_mode' => 'Cash',
            'frequency'    => 'weekly',
            'starts_at'    => $start->toDateString(),
            'next_run_at'  => $this->nextWeeklyRun($start)->toDateString(),
            'status'       => 'active',
        ]);

        $book->entries()
            ->where('description', 'Office cleaning')
            ->update(['recurring_entry_id' => $rule->id]);

        // A paused rule too, so the pause state is visible in the tab.
        RecurringEntry::create([
            'book_id'      => $book->id,
            'type'         => 'in',
            'amount'       => '600.00',
            'description'  => 'Retainer — Lumen Yoga',
            'category'     => 'Retainers',
            'payment_mode' => 'PayPal',
            'frequency'    => 'biweekly',
            'starts_at'    => $month->addDays(20)->toDateString(),
            'next_run_at'  => $month->addDays(34)->toDateString(),
            'status'       => 'paused',
        ]);
    }

    private function nextWeeklyRun(CarbonImmutable $start): CarbonImmutable
    {
        $today = CarbonImmutable::today();
        $next  = $start;

        while ($next->lessThanOrEqualTo($today)) {
            $next = $next->addWeek();
        }

        return $next;
    }

    private function addComments(Book $book, User $owner, User $editor): void
    {
        $entry = $book->entries()->where('description', 'like', 'Train tickets%')->first()
            ?? $book->entries()->latest('date')->first();

        if (! $entry instanceof Entry) {
            return;
        }

        EntryComment::create([
            'entry_id' => $entry->id,
            'user_id'  => $editor->id,
            'body'     => 'Can you attach the booking confirmation for this?',
        ]);

        EntryComment::create([
            'entry_id' => $entry->id,
            'user_id'  => $owner->id,
            'body'     => 'Done — attached the PDF from the rail operator.',
        ]);
    }
}
