<?php

namespace Tests\Feature\Billing;

use App\Models\Book;
use App\Models\RecurringEntry;
use App\Models\ReportSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\Feature\Api\ApiTestCase;

abstract class BillingTestCase extends ApiTestCase
{
    protected const SECRET = 'rc_whsec_test_123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.revenuecat.webhook_secret' => self::SECRET,
            'services.revenuecat.secret_key'     => 'sk_test_rc',
            'services.stripe.pro_price_id'       => 'price_test_pro',
        ]);
    }

    protected function stripeUser(string $status = 'active', ?Carbon $endsAt = null): User
    {
        $user = $this->makeUser(pro: $status === 'active');
        $user->stripe_id = 'cus_' . uniqid();
        if ($status === 'active') {
            $user->plan_source = 'stripe';
        }
        $user->save();

        $user->subscriptions()->create([
            'type'          => 'default',
            'stripe_id'     => 'sub_' . uniqid(),
            'stripe_status' => $status,
            'stripe_price'  => 'price_test_pro',
            'quantity'      => 1,
            'ends_at'       => $endsAt,
        ]);

        return $user->fresh();
    }

    protected function giveStoreEntitlement(User $user, Carbon $expires, string $platform = 'app_store'): User
    {
        $user->store_pro_expires_at = $expires;
        $user->store_product_id = 'thecashfox_pro_monthly';
        $user->store_platform = $platform;
        $user->save();

        return $user;
    }

    /** Fire the Stripe webhook listener exactly the way Cashier does. */
    protected function stripeWebhook(User $user, string $status, string $type = 'customer.subscription.updated'): void
    {
        event(new WebhookReceived([
            'type' => $type,
            'data' => ['object' => [
                'customer' => $user->stripe_id,
                'status'   => $status,
            ]],
        ]));
    }

    /** A book owned by $user with one active recurring rule + one active email report schedule. */
    protected function withActiveProFeatures(User $user): Book
    {
        $book = $this->makeBook($this->makeBusiness($user));

        $book->recurringEntries()->create([
            'type'        => 'out',
            'amount'      => '15.00',
            'description' => 'Hosting',
            'frequency'   => 'weekly',
            'starts_at'   => '2026-03-01',
            'next_run_at' => '2026-03-08',
            'status'      => 'active',
        ]);

        ReportSchedule::create([
            'book_id'    => $book->id,
            'frequency'  => 'weekly',
            'recipients' => ['owner@example.com'],
            'is_active'  => true,
        ]);

        return $book;
    }

    protected function assertProFeaturesActive(Book $book): void
    {
        $this->assertSame('active', RecurringEntry::where('book_id', $book->id)->value('status'));
        $this->assertTrue((bool) ReportSchedule::where('book_id', $book->id)->value('is_active'));
    }

    protected function assertProFeaturesPaused(Book $book): void
    {
        $this->assertSame('paused', RecurringEntry::where('book_id', $book->id)->value('status'));
        $this->assertFalse((bool) ReportSchedule::where('book_id', $book->id)->value('is_active'));
    }

    protected function rcEvent(string $type, array $attrs = []): array
    {
        return ['api_version' => '1.0', 'event' => array_merge([
            'id'                 => 'evt_' . uniqid('', true),
            'type'               => $type,
            'store'              => 'APP_STORE',
            'product_id'         => 'thecashfox_pro_monthly',
            'entitlement_ids'    => ['pro'],
            'event_timestamp_ms' => now()->getTimestampMs(),
            'expiration_at_ms'   => now()->addMonth()->getTimestampMs(),
        ], $attrs)];
    }

    protected function postRc(array $payload, ?string $auth = 'Bearer ' . self::SECRET)
    {
        $headers = $auth === null ? [] : ['Authorization' => $auth];

        return $this->postJson('/api/webhooks/revenuecat', $payload, $headers);
    }
}
