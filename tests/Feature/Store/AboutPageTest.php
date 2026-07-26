<?php

namespace Tests\Feature\Store;

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

        $service->storeCeoPhoto($photo);
        $service->addOfficeImage($office, 'Front counter');

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
}
