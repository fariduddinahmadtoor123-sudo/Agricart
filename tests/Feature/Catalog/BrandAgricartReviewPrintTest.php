<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Role;
use App\Models\User;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandAgricartReviewPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_brand_review_print_sheet_renders_english_and_urdu(): void
    {
        $criteria = [];
        foreach (BrandAgricartReviewCriteria::keys() as $index => $key) {
            $pass = $index < 9;
            $criteria[$key] = [
                'pass' => $pass,
                'reason_en' => $pass ? 'Pass EN '.$key : 'Fail EN '.$key,
                'reason_ur' => $pass ? 'پاس اردو '.$key : 'فیل اردو '.$key,
            ];
        }

        $brand = Brand::query()->create([
            'code' => 'BRD-PRINT1',
            'slug' => 'print-brand',
            'status' => 'active',
            'name_en' => 'Print Brand',
            'name_ur' => 'پرنٹ برانڈ',
            'description_en' => 'English brand story for dealers.',
            'description_ur' => 'ڈیلرز کے لیے اردو برانڈ کہانی۔',
            'agricart_rating' => 9,
            'agricart_rating_criteria' => $criteria,
            'agricart_rating_reason_en' => 'Polished English summary.',
            'agricart_rating_reason_ur' => 'پالش شدہ اردو خلاصہ۔',
            'image_path' => 'brands/print.webp',
            'ai_status' => 'none',
        ]);

        $en = $this->get(route('admin.brands.print', ['brand' => $brand, 'locale' => 'en']));
        $en->assertOk()
            ->assertSee('Print Brand', false)
            ->assertSee('English brand story for dealers.', false)
            ->assertSee('Pass EN product_quality', false)
            ->assertSee('Polished English summary.', false)
            ->assertSee('9', false)
            ->assertSee('/ 11', false);

        $ur = $this->get(route('admin.brands.print', ['brand' => $brand, 'locale' => 'ur']));
        $ur->assertOk()
            ->assertSee('پرنٹ برانڈ', false)
            ->assertSee('ڈیلرز کے لیے اردو برانڈ کہانی۔', false)
            ->assertSee('پاس اردو product_quality', false)
            ->assertSee('پالش شدہ اردو خلاصہ۔', false)
            ->assertSee('dir="rtl"', false);
    }
}
