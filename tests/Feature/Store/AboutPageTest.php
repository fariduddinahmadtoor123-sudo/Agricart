<?php

namespace Tests\Feature\Store;

use App\Models\CompanyProfile;
use App\Models\StoreAboutSetting;
use App\Models\StorePage;
use App\Services\Store\StoreAboutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_renders_story_ceo_office_and_team(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('ceo.jpg', 400, 400);
        $office = UploadedFile::fake()->image('office.jpg', 800, 600);
        $service = app(StoreAboutService::class);

        $service->saveText([
            'story_en' => 'Agricart has served farmers for decades with spare parts and advice.',
            'story_ur' => null,
            'hero_years_label' => '27 years of trust',
            'ceo_name' => 'Founder Name',
            'ceo_title_en' => 'CEO & Founder',
            'ceo_title_ur' => null,
            'ceo_bio_en' => 'Leading Agricart with hands-on experience since the beginning.',
            'ceo_bio_ur' => null,
            'office_images' => [],
            'team_members' => [
                [
                    'name' => 'Team Member',
                    'role_en' => 'Sales',
                    'role_ur' => null,
                    'photo_path' => null,
                    'sort' => 0,
                ],
            ],
        ]);

        $ceoPath = $service->storeCeoPhoto($photo);
        $service->addOfficeImage($office, 'Front counter');

        $this->assertStringEndsWith('.jpg', $ceoPath);
        $this->assertStringEndsWith('.jpg', (string) StoreAboutSetting::current()->office_images[0]['path']);

        $this->get(route('store.about'))
            ->assertOk()
            ->assertSee(__('agricart.storefront.about.page_title'))
            ->assertSee('27 years of trust')
            ->assertSee('Agricart has served farmers for decades')
            ->assertSee('Founder Name')
            ->assertSee('CEO & Founder')
            ->assertSee('Leading Agricart with hands-on')
            ->assertSee('Front counter')
            ->assertSee('Team Member')
            ->assertSee('Sales')
            ->assertSee('ag-store-about__ceo', false)
            ->assertSee('ag-store-about__gallery', false)
            ->assertSee('ag-store-about__team', false);
    }

    public function test_about_page_falls_back_to_cms_about_us_content_when_story_empty(): void
    {
        $about = StoreAboutSetting::current();
        $about->forceFill([
            'story_en' => null,
            'story_ur' => null,
        ])->save();

        StorePage::query()->create([
            'title_en' => 'About Us',
            'title_ur' => null,
            'slug' => 'about-us',
            'content_en' => 'CMS about body that should appear on /about.',
            'content_ur' => null,
            'is_active' => true,
            'show_in_quick_links' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('store.about'))
            ->assertOk()
            ->assertSee('CMS about body that should appear on /about.');
    }

    public function test_about_us_cms_slug_redirects_to_about_route(): void
    {
        StorePage::query()->create([
            'title_en' => 'About Us',
            'title_ur' => null,
            'slug' => 'about-us',
            'content_en' => 'Legacy about copy',
            'content_ur' => null,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('store.page', 'about-us'))
            ->assertRedirect(route('store.about'));
    }

    public function test_migration_seed_copies_about_us_story_when_present(): void
    {
        $about = StoreAboutSetting::current();

        $this->assertNotNull($about->id);
        $this->assertSame('27 years of trust', $about->hero_years_label);
    }

    public function test_store_page_about_us_public_url_points_to_about_route(): void
    {
        $page = StorePage::query()->create([
            'title_en' => 'About Us',
            'slug' => 'about-us',
            'content_en' => 'Story',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertSame(route('store.about'), $page->publicUrl());
    }

    public function test_about_page_shows_business_profile_contact_and_tax_details(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company/tax-certificates/test.jpg', 'certificate');

        CompanyProfile::current()->update([
            'business_name_en' => 'Agricart',
            'email' => 'hello@agricart.pk',
            'whatsapp_number' => '923001234567',
            'website' => 'agricart.pk',
            'ntn' => '1234567-8',
            'strn' => 'STRN-987654',
            'tax_certificate_path' => 'company/tax-certificates/test.jpg',
            'phones' => [[
                'designation' => 'general',
                'contact_person_en' => 'Sales Desk',
                'contact_person_ur' => '',
                'phone_number' => '0300 7654321',
                'is_primary' => true,
            ]],
            'addresses' => [[
                'address_en' => 'Main Bazaar, Gujranwala, Pakistan',
                'address_ur' => '',
                'postal_code' => '52250',
                'is_primary' => true,
            ]],
        ]);

        $response = $this->get(route('store.about'));

        $response->assertOk()
            ->assertViewHas('hasBusinessContact', true)
            ->assertSee('Main Bazaar, Gujranwala, Pakistan', false)
            ->assertSee('0300 7654321', false)
            ->assertSee('hello@agricart.pk', false)
            ->assertSee('1234567-8', false)
            ->assertSee('STRN-987654', false)
            ->assertSee('https://wa.me/923001234567', false)
            ->assertSee('ag-store-about__business', false)
            ->assertSee('ag-store-about__tax-certificate', false);
    }
}
