<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The account-deletion page is a Google Play submission requirement: it must be
 * reachable by a signed-out visitor and stay linked from the policy pages.
 */
class PublicLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_account_page_is_public_and_explains_the_flow(): void
    {
        $this->get('/delete-account')
            ->assertOk()
            ->assertSee('Delete Your Account')
            ->assertSee('Delete from the mobile app')
            ->assertSee('What is deleted')
            ->assertSee('What is kept, and why');
    }

    public function test_delete_account_page_is_indexable(): void
    {
        $this->get('/delete-account')->assertSee('index, follow', escape: false);

        $this->assertStringContainsString(
            'Allow: /delete-account',
            file_get_contents(public_path('robots.txt'))
        );
    }

    public function test_sitemap_lists_the_deletion_page(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee('/delete-account');
    }

    public function test_policy_pages_link_to_the_deletion_page(): void
    {
        $this->get('/privacy')->assertOk()->assertSee(route('delete-account'), escape: false);
        $this->get('/terms')->assertOk()->assertSee(route('delete-account'), escape: false);
    }

    public function test_store_billing_is_disclosed_in_the_policies(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('RevenueCat');

        $this->get('/terms')
            ->assertOk()
            ->assertSee('we cannot cancel or refund it for you')
            ->assertSee('does not cancel a store subscription', escape: false);
    }
}
