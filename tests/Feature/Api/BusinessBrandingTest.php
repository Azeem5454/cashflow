<?php

namespace Tests\Feature\Api;

use App\Models\UploadedAsset;
use Illuminate\Http\UploadedFile;

class BusinessBrandingTest extends ApiTestCase
{
    /** A tiny real PNG, so `image` / `mimetypes` validation and GD both pass. */
    private function pngFile(string $name = 'logo.png', int $w = 40, int $h = 40): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }

    public function test_owner_can_upload_replace_and_remove_the_logo(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/businesses/{$business->id}/logo", ['logo' => $this->pngFile()])
            ->assertOk()
            ->assertJsonStructure(['message', 'logoUrl']);

        $business->refresh();
        $key = 'business-' . $business->id . '-logo';
        $this->assertSame($key, $business->logo_key);
        $this->assertTrue(UploadedAsset::has($key));
        $this->assertTrue($business->hasLogo());

        // Replace — same key, still exactly one row.
        $this->postJson("/api/v1/businesses/{$business->id}/logo", ['logo' => $this->pngFile('other.png', 80, 80)])
            ->assertOk();
        $this->assertSame(1, UploadedAsset::query()->where('key', $key)->count());

        // The public brand-asset route serves it.
        $this->get(route('brand-asset', $key))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->deleteJson("/api/v1/businesses/{$business->id}/logo")->assertOk();
        $business->refresh();
        $this->assertNull($business->logo_key);
        $this->assertFalse(UploadedAsset::has($key));
    }

    public function test_the_stored_logo_is_re_encoded_as_png_and_capped_at_512px(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/businesses/{$business->id}/logo", [
            'logo' => UploadedFile::fake()->image('big.jpg', 1600, 900),
        ])->assertOk();

        $business->refresh();
        $bytes = UploadedAsset::payload($business->logo_key);
        $this->assertNotNull($bytes);

        $info = getimagesizefromstring($bytes);
        $this->assertSame('image/png', $info['mime']);
        $this->assertLessThanOrEqual(512, max($info[0], $info[1]));
    }

    public function test_a_non_owner_cannot_change_branding(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        $editor = $this->makeUser();
        $this->addMember($business, $editor, 'editor');
        $this->actingAsUser($editor);

        $this->postJson("/api/v1/businesses/{$business->id}/logo", ['logo' => $this->pngFile()])->assertForbidden();
        $this->deleteJson("/api/v1/businesses/{$business->id}/logo")->assertForbidden();
        $this->putJson("/api/v1/businesses/{$business->id}", ['contactPhone' => '123'])->assertForbidden();
    }

    public function test_logo_upload_validates_type_and_size(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->postJson("/api/v1/businesses/{$business->id}/logo", [
            'logo' => UploadedFile::fake()->create('ledger.pdf', 10, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('logo');

        $this->postJson("/api/v1/businesses/{$business->id}/logo", [
            'logo' => UploadedFile::fake()->image('huge.png', 100, 100)->size(2048), // 2 MB > 1 MB cap
        ])->assertStatus(422)->assertJsonValidationErrors('logo');

        $this->postJson("/api/v1/businesses/{$business->id}/logo", [])
            ->assertStatus(422)->assertJsonValidationErrors('logo');
    }

    public function test_contact_details_round_trip_and_validate(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $this->actingAsUser($owner);

        $this->putJson("/api/v1/businesses/{$business->id}", [
            'contactPhone' => '+1 555 0100',
            'contactEmail' => 'books@example.com',
        ])->assertOk();

        $this->getJson("/api/v1/businesses/{$business->id}")
            ->assertOk()
            ->assertJsonPath('data.contactPhone', '+1 555 0100')
            ->assertJsonPath('data.contactEmail', 'books@example.com');

        $this->putJson("/api/v1/businesses/{$business->id}", ['contactEmail' => 'not-an-email'])
            ->assertStatus(422)->assertJsonValidationErrors('contactEmail');

        // Clearing sends empty strings; they must land as NULL, not "".
        $this->putJson("/api/v1/businesses/{$business->id}", ['contactPhone' => '', 'contactEmail' => ''])
            ->assertOk();
        $business->refresh();
        $this->assertNull($business->contact_phone);
        $this->assertNull($business->contact_email);
    }

    public function test_an_unknown_business_logo_key_is_not_servable(): void
    {
        $this->get('/brand-asset/business-not-a-uuid-logo')->assertNotFound();
        $this->get('/brand-asset/' . str_repeat('a', 60))->assertNotFound();
    }
}
