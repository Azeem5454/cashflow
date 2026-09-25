<?php

namespace Tests\Feature;

use App\Livewire\Book\Show as BookShow;
use App\Livewire\Business\Settings;
use App\Models\Business;
use App\Models\User;
use App\Services\StarterWorkspace;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

/**
 * Simplified first-run: email-first check, no confirm-password, starter
 * workspace on sign-up, and soft email verification.
 */
class OnboardingTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setDate(2026, 9, 22)->setTime(10, 0));
    }

    // ── API register ────────────────────────────────────────────────

    public function test_api_register_creates_no_business_so_the_user_picks_the_currency(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name'     => 'Sara Khan',
            'email'    => 'sara@example.com',
            'password' => 'Secret-pass-1',
            'currency' => 'PKR',
        ])->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'emailVerified'], 'token'])
            ->assertJsonMissingPath('onboarding')
            ->assertJsonPath('user.emailVerified', false);

        $user = User::where('email', 'sara@example.com')->firstOrFail();

        // Currency is a per-business decision and cannot be guessed: a device
        // locale of en-GB on a phone in Pakistan reports GBP, and entries
        // recorded in the wrong currency can't be converted afterwards.
        $this->assertSame(0, $user->businesses()->count());
        $this->assertSame(0, Business::count());
    }

    public function test_api_register_still_accepts_a_currency_without_creating_anything(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'No Currency', 'email' => 'nc@example.com', 'password' => 'Secret-pass-1',
        ])->assertCreated();

        $this->assertSame(0, User::where('email', 'nc@example.com')->firstOrFail()->businesses()->count());
    }

    public function test_api_register_still_checks_confirmation_when_sent_and_validates_currency(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'Secret-pass-1', 'password_confirmation' => 'different',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'Secret-pass-1', 'currency' => 'usd',
        ])->assertStatus(422)->assertJsonValidationErrors('currency');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertSame(0, User::count());
    }

    public function test_invited_signup_creates_no_business_either(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $business->invitations()->create(['email' => 'invitee@example.com', 'role' => 'editor']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Invitee', 'email' => 'Invitee@Example.com', 'password' => 'Secret-pass-1',
        ])->assertCreated()->assertJsonMissingPath('onboarding');

        $this->assertSame(0, User::where('email', 'Invitee@Example.com')->first()->businesses()->count());
    }

    public function test_neither_register_nor_login_returns_an_onboarding_target(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Once', 'email' => 'once@example.com', 'password' => 'Secret-pass-1',
        ])->assertCreated()->assertJsonMissingPath('onboarding');

        $this->postJson('/api/v1/auth/login', ['email' => 'once@example.com', 'password' => 'Secret-pass-1'])
            ->assertOk()->assertJsonMissingPath('onboarding');

        $this->assertSame(0, Business::count());
    }

    public function test_a_new_free_user_can_create_their_first_business_but_not_a_second(): void
    {
        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Free', 'email' => 'free@example.com', 'password' => 'Secret-pass-1',
        ])->json('token');

        // The first one is theirs to create, with a currency they chose.
        $this->withToken($token)
            ->postJson('/api/v1/businesses', ['name' => 'Mine', 'currency' => 'PKR'])
            ->assertCreated();

        $this->assertSame('PKR', Business::where('name', 'Mine')->firstOrFail()->currency);

        // The Free plan still allows only one.
        $this->withToken($token)->postJson('/api/v1/businesses', ['name' => 'Second', 'currency' => 'USD'])
            ->assertForbidden();
    }

    // ── check-email ─────────────────────────────────────────────────

    public function test_check_email_reports_next_step_without_leaking_profile(): void
    {
        $this->postJson('/api/v1/auth/check-email', ['email' => 'nobody@example.com'])
            ->assertOk()->assertExactJson(['exists' => false, 'provider' => null, 'hasPassword' => false]);

        $this->makeUser(attrs: ['email' => 'pw@example.com', 'name' => 'Secret Name']);
        $this->postJson('/api/v1/auth/check-email', ['email' => 'PW@example.com'])
            ->assertOk()->assertExactJson(['exists' => true, 'provider' => null, 'hasPassword' => true]);

        $social = $this->makeUser(attrs: ['email' => 'g@example.com']);
        $social->provider = 'google';
        $social->provider_id = 'g-1';
        $social->has_password = false;
        $social->save();
        $this->postJson('/api/v1/auth/check-email', ['email' => 'g@example.com'])
            ->assertOk()->assertExactJson(['exists' => true, 'provider' => 'google', 'hasPassword' => false]);

        $this->postJson('/api/v1/auth/check-email', ['email' => 'not-an-email'])->assertStatus(422);
    }

    // ── Soft verification (API) ─────────────────────────────────────

    public function test_unverified_user_can_create_business_book_and_entry(): void
    {
        $user = $this->actingAsUser($this->makeUser(attrs: ['email_verified_at' => null]));

        $businessId = $this->postJson('/api/v1/businesses', ['name' => 'Shop', 'currency' => 'USD'])
            ->assertCreated()->json('id');
        $bookId = $this->postJson("/api/v1/businesses/{$businessId}/books", ['name' => 'Sept'])
            ->assertCreated()->json('id');
        $this->postJson("/api/v1/books/{$bookId}/entries", [
            'type' => 'in', 'amount' => '150.00', 'description' => 'First sale', 'date' => '2026-09-22',
        ])->assertCreated();

        $this->getJson('/api/v1/businesses')->assertOk();
        $this->getJson('/api/v1/notifications')->assertOk();
        $this->assertSame(1, $user->businesses()->count());
    }

    public function test_unverified_user_is_blocked_from_invites_and_report_schedules(): void
    {
        Mail::fake();
        $owner    = $this->actingAsUser($this->makeUser(pro: true, attrs: ['email_verified_at' => null]));
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        $expected = ['message' => 'Please verify your email address first.', 'code' => 'email_unverified'];

        $this->postJson("/api/v1/businesses/{$business->id}/invitations", ['email' => 'mate@example.com', 'role' => 'editor'])
            ->assertForbidden()->assertExactJson($expected);
        $this->putJson("/api/v1/books/{$book->id}/report-schedule", ['frequency' => 'weekly', 'recipients' => ['a@example.com']])
            ->assertForbidden()->assertExactJson($expected);

        // Reading an existing schedule is still fine.
        $this->getJson("/api/v1/books/{$book->id}/report-schedule")->assertOk();
        $this->assertSame(0, $business->invitations()->count());
        Mail::assertNothingQueued();

        // Once verified, both work.
        $owner->forceFill(['email_verified_at' => now()])->save();
        $this->postJson("/api/v1/businesses/{$business->id}/invitations", ['email' => 'mate@example.com', 'role' => 'editor'])
            ->assertCreated();
        $this->putJson("/api/v1/books/{$book->id}/report-schedule", ['frequency' => 'weekly', 'recipients' => ['a@example.com']])
            ->assertOk();
    }

    // ── Web ─────────────────────────────────────────────────────────

    public function test_web_register_signs_in_without_creating_a_business(): void
    {
        $this->post('/register', [
            'name' => 'Web User', 'email' => 'web@example.com', 'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertSame(0, User::where('email', 'web@example.com')->firstOrFail()->businesses()->count());
    }

    public function test_web_register_from_invitation_skips_workspace_and_returns_to_invite(): void
    {
        $owner      = $this->makeUser();
        $business   = $this->makeBusiness($owner);
        $invitation = $business->invitations()->create(['email' => 'someone@example.com', 'role' => 'viewer']);
        $acceptPath = "/invitations/{$invitation->token}/accept";

        $this->get('/register?redirect=' . urlencode(url($acceptPath)))
            ->assertOk()->assertSee('name="redirect"', false);

        // Registers with a different email than the invite — the redirect alone skips it.
        $this->post('/register', [
            'name' => 'Joiner', 'email' => 'joiner@example.com', 'password' => 'password', 'redirect' => $acceptPath,
        ])->assertRedirect($acceptPath);

        $this->assertSame(0, User::where('email', 'joiner@example.com')->first()->businesses()->count());
    }

    public function test_web_register_ignores_foreign_redirects(): void
    {
        $this->post('/register', [
            'name' => 'Evil', 'email' => 'evil@example.com', 'password' => 'password', 'redirect' => 'https://evil.example.com/invitations/abcdefabcdefabcdef/accept',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame(0, User::where('email', 'evil@example.com')->firstOrFail()->businesses()->count());
    }

    public function test_unverified_web_user_can_use_app_and_sees_banner(): void
    {
        $user     = $this->makeUser(attrs: ['email_verified_at' => null]);
        $business = $this->makeBusiness($user);
        $book     = $this->makeBook($business);

        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertSee('data-testid="verify-email-banner"', false)
            ->assertSee('Verify your email to invite teammates and send reports.');
        $this->actingAs($user)->get(route('businesses.show', $business))->assertOk();
        $this->actingAs($user)->get(route('businesses.books.show', [$business, $book]))->assertOk();
    }

    public function test_verified_web_user_sees_no_banner(): void
    {
        $this->actingAs($this->makeUser())->get('/dashboard')->assertOk()
            ->assertDontSee('data-testid="verify-email-banner"', false);
    }

    public function test_unverified_web_user_cannot_send_invites_until_verified(): void
    {
        Mail::fake();
        $owner    = $this->makeUser(pro: true, attrs: ['email_verified_at' => null]);
        $business = $this->makeBusiness($owner);

        Livewire::actingAs($owner)->test(Settings::class, ['business' => $business])
            ->set('inviteEmail', 'mate@example.com')->set('inviteRole', 'editor')->call('sendInvite')
            ->assertSet('emailVerificationRequired', true)
            ->assertSet('inviteSent', false)
            ->assertSee('Please verify your email address first.')
            ->call('resendVerificationEmail')
            ->assertSet('verificationEmailResent', true);

        $this->assertSame(0, $business->invitations()->count());
        Notification::assertSentTo($owner, \App\Notifications\CustomVerifyEmail::class);
        Mail::assertNothingSent();
    }

    public function test_unverified_web_user_cannot_save_or_test_email_reports(): void
    {
        Mail::fake();
        $owner    = $this->makeUser(pro: true, attrs: ['email_verified_at' => null]);
        $business = $this->makeBusiness($owner);
        $book     = $this->makeBook($business);

        Livewire::actingAs($owner)->test(BookShow::class, ['business' => $business, 'book' => $book])
            ->call('openEmailReportModal')
            ->set('emailReportRecipients', 'a@example.com')
            ->call('saveEmailReport')
            ->assertSet('emailVerificationRequired', true)
            ->call('sendTestReport')
            ->assertSet('emailVerificationRequired', true);

        $this->assertNull($book->fresh()->reportSchedule);
        Mail::assertNothingQueued();
    }
}
