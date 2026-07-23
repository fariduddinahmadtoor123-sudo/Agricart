<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Brands\Form;
use App\Livewire\Brands\ListBrands;
use App\Models\AiConfiguration;
use App\Models\Brand;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Ai\OpenRouterClient;
use App\Services\Catalog\BrandAgricartReviewAiService;
use App\Services\Catalog\BrandEnrichmentService;
use App\Services\Catalog\ProductLabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class BrandFormIntegrityTest extends TestCase
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

    public function test_brand_image_remains_temporary_until_save(): void
    {
        Storage::fake('public');

        $component = Livewire::test(Form::class)
            ->call('openForm')
            ->set('name_en', 'Agricart Tools')
            ->set('name_ur', 'ایگری کارٹ ٹولز')
            ->set('imageUpload', UploadedFile::fake()->image('brand-logo.jpg'));

        $component->assertSet('storedImagePath', null);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $component->call('saveAndClose')->assertHasNoErrors();

        $brand = Brand::query()->where('name_en', 'Agricart Tools')->firstOrFail();
        $this->assertNotNull($brand->image_path);
        $this->assertMatchesRegularExpression('/^BRD-\d{5}$/', $brand->code);
        Storage::disk('public')->assertExists($brand->image_path);
    }

    public function test_upload_does_not_replace_an_entered_english_name(): void
    {
        Storage::fake('public');

        Livewire::test(Form::class)
            ->call('openForm')
            ->set('name_en', 'Exact Brand Name')
            ->set('imageUpload', UploadedFile::fake()->image('different-name.jpg'))
            ->assertSet('name_en', 'Exact Brand Name');
    }

    public function test_catalog_viewer_cannot_create_or_delete_brands(): void
    {
        $role = Role::query()->create([
            'name' => 'Catalog Viewer',
            'slug' => 'catalog-viewer',
        ]);
        $permission = Permission::query()->create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'View catalog',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
        $brand = $this->brand('protected-brand', 'Protected Brand');

        Livewire::test(Form::class)
            ->call('openForm')
            ->assertForbidden();

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->assertForbidden();

        Livewire::test(ListBrands::class)
            ->call('deleteBrand', $brand->id)
            ->assertForbidden();

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_admin_without_catalog_permission_cannot_open_brand_form(): void
    {
        $role = Role::query()->create([
            'name' => 'Unrelated Admin',
            'slug' => 'unrelated-admin',
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
        $brand = $this->brand('private-brand', 'Private Brand');

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'view')
            ->assertForbidden();
    }

    public function test_failed_save_removes_newly_stored_brand_image(): void
    {
        Storage::fake('public');

        Livewire::test(Form::class)
            ->call('openForm')
            ->set('name_en', 'Invalid Website Brand')
            ->set('name_ur', 'غلط ویب سائٹ برانڈ')
            ->set('website_url', 'not-a-valid-url')
            ->set('imageUpload', UploadedFile::fake()->image('invalid.jpg'))
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertDatabaseMissing('brands', ['name_en' => 'Invalid Website Brand']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_deleting_brand_removes_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/delete.jpg', 'image');
        $brand = $this->brand('delete-brand', 'Delete Brand', [
            'image_path' => 'brands/delete.jpg',
        ]);

        Livewire::test(ListBrands::class)->call('deleteBrand', $brand->id);

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        Storage::disk('public')->assertMissing('brands/delete.jpg');
    }

    public function test_replacing_brand_image_removes_the_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/old.jpg', 'old');
        $brand = $this->brand('replace-brand', 'Replace Brand', [
            'image_path' => 'brands/old.jpg',
        ]);

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->set('imageUpload', UploadedFile::fake()->image('new.jpg'))
            ->call('saveAndClose');

        $newPath = $brand->fresh()->image_path;
        $this->assertNotSame('brands/old.jpg', $newPath);
        Storage::disk('public')->assertMissing('brands/old.jpg');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_brand_with_products_cannot_be_deleted(): void
    {
        $brand = $this->brand('linked-brand', 'Linked Brand');
        Product::query()->create([
            'brand_id' => $brand->id,
            'slug' => 'linked-brand-product',
            'code' => 'PRD-BRAND',
            'status' => 'active',
            'name_en' => 'Linked Product',
            'name_ur' => 'منسلک پراڈکٹ',
        ]);

        Livewire::test(ListBrands::class)->call('deleteBrand', $brand->id);

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_brand_name_change_refreshes_stored_product_labels(): void
    {
        $brand = $this->brand('label-brand', 'Old Brand', [
            'name_ur' => 'پرانا برانڈ',
        ]);
        $product = Product::query()->create([
            'brand_id' => $brand->id,
            'slug' => 'brand-label-product',
            'code' => 'PRD-BRAND-LABEL',
            'status' => 'active',
            'name_en' => 'Brand Label Product',
            'name_ur' => 'برانڈ لیبل پراڈکٹ',
            'label_code' => 'R1-P1',
            'category_visual_map_code' => 'R1',
            'label_sequence' => 1,
        ]);
        app(ProductLabelService::class)->generateForProduct($product);

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->set('name_en', 'New Brand')
            ->set('name_ur', 'نیا برانڈ')
            ->call('saveAndClose');

        $englishLines = collect($product->fresh()->label_qr['en']['lines']);

        $this->assertTrue($englishLines->contains(
            fn (array $line): bool => $line['label'] === 'Brand' && $line['value'] === 'New Brand',
        ));
    }

    public function test_inactive_brand_requires_bilingual_reason_and_deactivates_products(): void
    {
        Storage::fake('public');

        $brand = $this->brand('cascade-brand', 'Cascade Brand');
        $activeProduct = Product::query()->create([
            'brand_id' => $brand->id,
            'slug' => 'cascade-active-product',
            'code' => 'PRD-CASCADE-A',
            'status' => 'active',
            'name_en' => 'Cascade Active',
            'name_ur' => 'کیسکیڈ فعال',
        ]);
        $draftProduct = Product::query()->create([
            'brand_id' => $brand->id,
            'slug' => 'cascade-draft-product',
            'code' => 'PRD-CASCADE-D',
            'status' => 'draft',
            'name_en' => 'Cascade Draft',
            'name_ur' => 'کیسکیڈ ڈرافٹ',
        ]);

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->set('status', 'inactive')
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertSame('active', $brand->fresh()->status);
        $this->assertSame('active', $activeProduct->fresh()->status);

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->set('status', 'inactive')
            ->set('inactive_reason_en', 'Supplier stopped delivery')
            ->set('inactive_reason_ur', 'سپلائر نے سپلائی بند کر دی')
            ->call('saveAndClose')
            ->assertSet('statusType', 'success');

        $brand->refresh();
        $this->assertSame('inactive', $brand->status);
        $this->assertSame('Supplier stopped delivery', $brand->inactive_reason_en);
        $this->assertSame('سپلائر نے سپلائی بند کر دی', $brand->inactive_reason_ur);
        $this->assertSame('inactive', $activeProduct->fresh()->status);
        $this->assertSame('inactive', $draftProduct->fresh()->status);
    }

    public function test_agricart_checklist_auto_scores_and_requires_all_fields(): void
    {
        Storage::fake('public');

        $brand = $this->brand('rated-brand', 'Rated Brand');

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->call('setAgricartCriterion', 'product_quality', 9)
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertNull($brand->fresh()->agricart_rating);

        $component = Livewire::test(Form::class)->call('openForm', $brand->id, 'edit');

        foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $index => $key) {
            $rating = $index < 8 ? 10 : 1;
            $component
                ->call('setAgricartCriterion', $key, $rating)
                ->set('agricart_rating_criteria.'.$key.'.reason_en', $rating >= 8 ? 'Positive reason '.$key : 'Needs improvement '.$key)
                ->set('agricart_rating_criteria.'.$key.'.reason_ur', $rating >= 8 ? 'مثبت وجہ '.$key : 'بہتری وجہ '.$key);
        }

        // Average of eight 10s and three 1s ≈ 7.55 → 8 / 10
        $component
            ->assertSet('agricart_rating', '8')
            ->call('saveAndClose')
            ->assertSet('statusType', 'success');

        $brand->refresh();
        $this->assertSame(8, $brand->agricart_rating);
        $this->assertSame(10, $brand->agricart_rating_criteria['product_quality']['rating']);
        $this->assertSame('Positive reason product_quality', $brand->agricart_rating_criteria['product_quality']['reason_en']);
        $this->assertSame(1, $brand->agricart_rating_criteria['overall_market_acceptance']['rating']);
        $this->assertStringContainsString('Product Quality', (string) $brand->agricart_rating_reason_en);
        $this->assertStringContainsString('8 / 10', (string) $brand->agricart_rating_reason_en);
    }

    public function test_saving_brand_persists_editable_site_review_notice(): void
    {
        Storage::fake('public');

        $brand = $this->brand('notice-brand', 'Notice Brand');

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->set('brand_review_notice_en', 'Custom EN legal notice for Agricart reviews.')
            ->set('brand_review_notice_ur', 'ایگری کارٹ ریویو کے لیے حسبِ ضرورت اردو قانونی نوٹ۔')
            ->call('saveAndClose')
            ->assertSet('statusType', 'success');

        $settings = \App\Models\StoreSetting::current()->fresh();
        $this->assertSame('Custom EN legal notice for Agricart reviews.', $settings->brand_review_notice_en);
        $this->assertSame('ایگری کارٹ ریویو کے لیے حسبِ ضرورت اردو قانونی نوٹ۔', $settings->brand_review_notice_ur);
    }

    public function test_brand_can_save_without_agricart_rating(): void
    {
        Storage::fake('public');

        Livewire::test(Form::class)
            ->call('openForm')
            ->set('name_en', 'Unrated Brand')
            ->set('name_ur', 'بغیر ریٹنگ برانڈ')
            ->set('imageUpload', UploadedFile::fake()->image('unrated.jpg'))
            ->call('saveAndClose')
            ->assertSet('statusType', 'success');

        $brand = Brand::query()->where('name_en', 'Unrated Brand')->firstOrFail();
        $this->assertNull($brand->agricart_rating);
        $this->assertSame('active', $brand->status);
    }

    public function test_rating_auto_fills_manageable_suggested_messages_and_can_save_as_default(): void
    {
        Storage::fake('public');

        $brand = $this->brand('suggest-brand', 'Suggest Brand');

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->call('setAgricartCriterion', 'product_quality', 9)
            ->assertSet(
                'agricart_rating_criteria.product_quality.reason_en',
                'Consistently delivers high-quality products.',
            )
            ->assertSet(
                'agricart_rating_criteria.product_quality.reason_ur',
                'مسلسل اعلیٰ معیار کی مصنوعات فراہم کرتا ہے۔',
            )
            ->call('setAgricartCriterion', 'value_for_money', 3)
            ->call('setAgricartCriterion', 'market_reputation', 9)
            ->call('setAgricartCriterion', 'durability', 9)
            ->call('setAgricartCriterion', 'performance', 9)
            ->call('setAgricartCriterion', 'innovation_technology', 9)
            ->call('setAgricartCriterion', 'warranty_after_sales', 9)
            ->call('setAgricartCriterion', 'spare_parts_availability', 9)
            ->call('setAgricartCriterion', 'brand_reliability', 9)
            ->call('setAgricartCriterion', 'product_availability', 9)
            ->call('setAgricartCriterion', 'overall_market_acceptance', 9)
            ->set('agricart_rating_criteria.product_quality.reason_en', 'Custom high quality line for this brand.')
            ->set('agricart_rating_criteria.product_quality.reason_ur', 'اس برانڈ کے لیے حسبِ ضرورت معیار کی لائن۔')
            ->set('saveCriterionMessageAsDefault.product_quality', true)
            ->call('saveAndClose')
            ->assertSet('statusType', 'success');

        $message = \App\Models\BrandAgricartReviewMessage::query()
            ->where('criterion_key', 'product_quality')
            ->firstOrFail();

        $this->assertSame('Custom high quality line for this brand.', $message->positive_en);
        $this->assertSame('اس برانڈ کے لیے حسبِ ضرورت معیار کی لائن۔', $message->positive_ur);

        $suggestion = app(\App\Services\Catalog\BrandAgricartReviewMessageService::class)
            ->suggestionFor('product_quality', 9);

        $this->assertSame(
            'Custom high quality line for this brand.',
            $suggestion['en'],
        );
    }

    public function test_save_auto_fills_missing_reasons_from_suggested_messages(): void
    {
        Storage::fake('public');

        $brand = $this->brand('autofill-brand', 'Autofill Brand');
        $component = Livewire::test(Form::class)->call('openForm', $brand->id, 'edit');

        foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $index => $key) {
            $component->call('setAgricartCriterion', $key, $index < 9 ? 9 : 3);
        }

        // Simulate Livewire wiping reason text after rating change (previous bug).
        foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $key) {
            $component
                ->set('agricart_rating_criteria.'.$key.'.reason_en', '')
                ->set('agricart_rating_criteria.'.$key.'.reason_ur', '');
        }

        $component
            ->call('saveAndClose')
            ->assertSet('statusType', 'success');

        $brand->refresh();
        // Nine 9s + two 3s → average ≈ 7.91 → 8
        $this->assertSame(8, $brand->agricart_rating);
        $this->assertSame(
            'Consistently delivers high-quality products.',
            $brand->agricart_rating_criteria['product_quality']['reason_en'],
        );
        $this->assertSame(
            'Market acceptance is comparatively limited.',
            $brand->agricart_rating_criteria['overall_market_acceptance']['reason_en'],
        );
    }

    public function test_ai_parser_ignores_non_string_brand_fields(): void
    {
        $parsed = app(BrandEnrichmentService::class)->parseResponse(json_encode([
            'name_ur' => ['invalid'],
            'description_en' => ['invalid'],
            'description_ur' => 'درست تفصیل',
            'slug' => ['invalid'],
        ]));

        $this->assertArrayNotHasKey('name_ur', $parsed);
        $this->assertArrayNotHasKey('description_en', $parsed);
        $this->assertArrayNotHasKey('slug', $parsed);
        $this->assertSame('درست تفصیل', $parsed['description_ur']);
        $this->assertNull(app(BrandEnrichmentService::class)->parseResponse('[]'));
        $this->assertNull(app(BrandEnrichmentService::class)->parseResponse('{}'));
        $this->assertNull(app(BrandEnrichmentService::class)->parseResponse(json_encode([
            'name_ur' => str_repeat('ا', 256),
        ])));
    }

    public function test_agricart_review_ai_fills_reasons_and_polished_review(): void
    {
        Storage::fake('public');

        AiConfiguration::query()->create([
            'provider' => 'openrouter',
            'api_key' => 'test-key',
            'model_id' => 'test/model',
            'model_name' => 'Test',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        $criteriaPayload = [];
        foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $key) {
            $criteriaPayload[$key] = [
                'reason_en' => 'AI EN reason for '.$key,
                'reason_ur' => 'AI UR وجہ '.$key,
            ];
        }

        $client = Mockery::mock(OpenRouterClient::class);
        $client->shouldReceive('sendChat')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'ok',
                'response' => json_encode([
                    'criteria' => $criteriaPayload,
                    'review_en' => 'Polished Agricart review for TestBrand in English.',
                    'review_ur' => 'ٹیسٹ برانڈ کے لیے پالش شدہ ایگری کارٹ ریویو۔',
                ], JSON_UNESCAPED_UNICODE),
                'http_status' => 200,
            ]);
        $this->app->instance(OpenRouterClient::class, $client);

        $brand = $this->brand('ai-review-brand', 'TestBrand');
        $component = Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->set('name_en', 'TestBrand')
            ->set('name_ur', 'ٹیسٹ برانڈ');

        foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $index => $key) {
            $component->call('setAgricartCriterion', $key, $index < 7 ? 9 : 3);
        }

        $component
            ->call('generateAgricartReviewWithAi')
            ->assertSet('statusType', 'success')
            // Seven 9s + four 3s → average ≈ 6.82 → 7
            ->assertSet('agricart_rating', '7')
            ->assertSet('agricart_rating_reason_en', 'Polished Agricart review for TestBrand in English.')
            ->assertSet('agricart_rating_reason_ur', 'ٹیسٹ برانڈ کے لیے پالش شدہ ایگری کارٹ ریویو۔')
            ->assertSet('agricart_rating_criteria.product_quality.reason_en', 'AI EN reason for product_quality')
            ->assertSet('agricart_rating_criteria.product_quality.reason_ur', 'AI UR وجہ product_quality')
            ->call('saveAndClose')
            ->assertSet('statusType', 'success');

        $brand->refresh();
        $this->assertSame(7, $brand->agricart_rating);
        $this->assertSame('Polished Agricart review for TestBrand in English.', $brand->agricart_rating_reason_en);
        $this->assertSame('AI EN reason for product_quality', $brand->agricart_rating_criteria['product_quality']['reason_en']);
    }

    public function test_agricart_review_ai_requires_all_ratings(): void
    {
        Storage::fake('public');

        $brand = $this->brand('partial-review-brand', 'Partial Brand');

        Livewire::test(Form::class)
            ->call('openForm', $brand->id, 'edit')
            ->call('setAgricartCriterion', 'product_quality', 9)
            ->call('generateAgricartReviewWithAi')
            ->assertSet('statusType', 'warning');
    }

    public function test_agricart_review_ai_parser_requires_full_payload(): void
    {
        $service = app(BrandAgricartReviewAiService::class);

        $this->assertNull($service->parseResponse('{}'));
        $this->assertNull($service->parseResponse(json_encode([
            'review_en' => 'Only English',
            'review_ur' => 'صرف اردو',
            'criteria' => [],
        ])));

        $criteria = [];
        foreach (\App\Support\Catalog\BrandAgricartReviewCriteria::keys() as $key) {
            $criteria[$key] = [
                'reason_en' => 'EN '.$key,
                'reason_ur' => 'UR '.$key,
            ];
        }

        $parsed = $service->parseResponse(json_encode([
            'criteria' => $criteria,
            'review_en' => 'Full EN review',
            'review_ur' => 'مکمل اردو ریویو',
        ], JSON_UNESCAPED_UNICODE));

        $this->assertNotNull($parsed);
        $this->assertSame('Full EN review', $parsed['review_en']);
        $this->assertSame('EN product_quality', $parsed['criteria']['product_quality']['reason_en']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function brand(string $slug, string $name, array $overrides = []): Brand
    {
        return Brand::query()->create(array_merge([
            'code' => 'BRD-'.strtoupper(substr(md5($slug), 0, 8)),
            'slug' => $slug,
            'status' => 'active',
            'name_en' => $name,
            'name_ur' => $name.' اردو',
            'image_path' => 'brands/test.jpg',
        ], $overrides));
    }
}
