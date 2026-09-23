<?php

namespace Tests\Feature;

use App\Livewire\Business\Settings;
use App\Models\UploadedAsset;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Feature\Api\ApiTestCase;

class WebBusinessBrandingTest extends ApiTestCase
{
    public function test_owner_can_upload_and_remove_the_logo_from_settings(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $key      = 'business-' . $business->id . '-logo';

        $component = Livewire::actingAs($owner)
            ->test(Settings::class, ['business' => $business])
            ->set('logoUpload', UploadedFile::fake()->image('logo.png', 60, 60))
            ->assertHasNoErrors();

        $this->assertTrue(UploadedAsset::has($key));
        $this->assertSame($key, $business->fresh()->logo_key);

        $component->call('removeLogo');

        $this->assertFalse(UploadedAsset::has($key));
        $this->assertNull($business->fresh()->logo_key);
    }

    public function test_settings_rejects_a_non_image_and_an_oversized_logo(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        Livewire::actingAs($owner)
            ->test(Settings::class, ['business' => $business])
            ->set('logoUpload', UploadedFile::fake()->create('ledger.pdf', 10, 'application/pdf'))
            ->assertHasErrors('logoUpload');

        Livewire::actingAs($owner)
            ->test(Settings::class, ['business' => $business])
            ->set('logoUpload', UploadedFile::fake()->image('huge.png', 60, 60)->size(2048))
            ->assertHasErrors('logoUpload');

        $this->assertNull($business->fresh()->logo_key);
    }

    public function test_contact_details_save_and_clear_to_null(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);

        Livewire::actingAs($owner)
            ->test(Settings::class, ['business' => $business])
            ->set('contactPhone', '+1 555 0100')
            ->set('contactEmail', 'books@example.test')
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $business->refresh();
        $this->assertSame('+1 555 0100', $business->contact_phone);
        $this->assertSame('books@example.test', $business->contact_email);

        Livewire::actingAs($owner)
            ->test(Settings::class, ['business' => $business])
            ->set('contactEmail', 'nope')
            ->call('saveGeneral')
            ->assertHasErrors('contactEmail');

        Livewire::actingAs($owner)
            ->test(Settings::class, ['business' => $business])
            ->set('contactPhone', '')
            ->set('contactEmail', '')
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $business->refresh();
        $this->assertNull($business->contact_phone);
        $this->assertNull($business->contact_email);
    }

    public function test_a_non_owner_cannot_open_business_settings(): void
    {
        $owner    = $this->makeUser();
        $business = $this->makeBusiness($owner);
        $editor   = $this->makeUser();
        $this->addMember($business, $editor, 'editor');

        $this->actingAs($editor)
            ->get(route('businesses.settings', $business))
            ->assertForbidden();
    }
}
