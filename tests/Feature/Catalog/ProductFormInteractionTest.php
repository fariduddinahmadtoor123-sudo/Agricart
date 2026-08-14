<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Products\Form;
use App\Livewire\Products\ListProducts;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\PackingUnit;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Enums\ShippingChargeMode;
use App\Services\Catalog\ProductEnrichmentService;
use App\Services\Catalog\ProductService;
use Database\Seeders\QuantityControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFormInteractionTest extends TestCase
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

    public function test_parent_category_search_works_from_the_first_character_and_by_code(): void
    {
        $category = Category::query()->create([
            'slug' => 'wheat-seed',
            'code' => 'CAT-WHEAT',
            'status' => 'active',
            'name_en' => 'Wheat Seed',
            'name_ur' => 'گندم کا بیج',
        ]);

        Livewire::test(Form::class)
            ->call('openForm')
            ->set('parent_search', 'W')
            ->assertSee('Wheat Seed')
            ->set('parent_search', 'CAT-WHEAT')
            ->assertSee('Wheat Seed')
            ->call('selectParent', $category->id)
            ->assertSet('parent_id', $category->id)
            ->assertSet('parent_search', '')
            ->assertSet('hierarchySlots.0', $category->id)
            ->assertSee('Wheat Seed');
    }

    public function test_default_quantity_channels_are_available_in_product_form(): void
    {
        $this->seed(QuantityControlSeeder::class);

        Livewire::test(Form::class)
            ->call('openForm')
            ->assertSee(__('agricart.products.fields.retail_quantity'))
            ->assertSee(__('agricart.products.fields.wholesale_quantity'))
            ->assertSee(__('agricart.products.fields.super_wholesale_quantity'))
            ->assertSee(__('agricart.products.fields.distributor_quantity'))
            ->assertSet('price_tier_quantities.retail_price', 1)
            ->assertSet('price_tier_quantities.wholesale_price', 5)
            ->assertSet('price_tier_quantities.super_wholesale_price', 10)
            ->assertSet('price_tier_quantities.distributor_price', 20);
    }

    public function test_ai_prompt_is_initialized_and_rendered_in_the_textarea(): void
    {
        $prompt = ProductEnrichmentService::defaultPrompt();

        $this->assertNotSame('', trim($prompt));
        $this->assertStringContainsString('Preserve this English name exactly as written', $prompt);

        Livewire::test(Form::class)
            ->call('openForm')
            ->assertSet('enrichmentPrompt', $prompt)
            ->assertSee(e($prompt), false);
    }

    public function test_ai_enrichment_data_cannot_replace_the_entered_english_name(): void
    {
        $form = new Form;
        $form->name_en = 'User Entered Product Name';

        $method = new \ReflectionMethod(Form::class, 'applyEnrichmentData');
        $method->invoke($form, [
            'name_en' => 'AI Changed Name',
            'name_ur' => 'صارف کا درج کردہ پروڈکٹ نام',
            'short_description_en' => 'Generated description',
        ]);

        $this->assertSame('User Entered Product Name', $form->name_en);
        $this->assertSame('صارف کا درج کردہ پروڈکٹ نام', $form->name_ur);
        $this->assertSame('Generated description', $form->short_description_en);
    }

    public function test_control_picker_searches_and_displays_only_control_names(): void
    {
        $control = CatalogControl::query()->create([
            'code' => 'CTL-POLICY',
            'slug' => 'policy',
            'status' => 'active',
            'name_en' => 'Policy',
            'name_ur' => 'پالیسی',
            'value_en' => 'This long policy text belongs on the storefront product view.',
            'value_ur' => 'یہ تفصیلی پالیسی اسٹور فرنٹ پر دکھائی جائے گی۔',
        ]);

        Livewire::test(Form::class)
            ->call('openForm')
            ->set('control_search', 'Pol')
            ->assertSee('Policy')
            ->assertDontSee('This long policy text belongs on the storefront product view.')
            ->call('addControl', $control->id)
            ->assertSee('Policy')
            ->assertDontSee('This long policy text belongs on the storefront product view.');
    }

    public function test_catalog_viewer_cannot_open_the_product_create_form(): void
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

        Livewire::test(Form::class)
            ->call('openForm')
            ->assertForbidden();
    }

    public function test_catalog_viewer_cannot_delete_a_product_through_livewire(): void
    {
        $role = Role::query()->create([
            'name' => 'Catalog Viewer',
            'slug' => 'catalog-viewer-delete-check',
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

        $product = Product::query()->create([
            'slug' => 'protected-product',
            'code' => 'PRD-PROTECTED',
            'status' => 'active',
            'name_en' => 'Protected Product',
            'name_ur' => 'محفوظ پراڈکٹ',
        ]);

        Livewire::test(ListProducts::class)
            ->call('deleteProduct', $product->id)
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_invalid_search_terms_json_does_not_erase_existing_terms(): void
    {
        Storage::fake('public');

        $category = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEEDS',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);
        $catalog = $this->requiredCatalog();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $catalog['brand']->id,
            'unit_id' => $catalog['unit']->id,
            'unit_qty' => '1',
            'pack_unit_id' => $catalog['packUnit']->id,
            'slug' => 'seed-pack',
            'code' => 'PRD-SEED',
            'status' => 'active',
            'name_en' => 'Seed Pack',
            'name_ur' => 'بیج پیک',
            'search_terms' => ['seed', 'farm'],
        ]);
        $product->attributes()->sync([$catalog['attribute']->id => ['value' => 'Standard']]);
        $product->controls()->sync([$catalog['control']->id]);
        $product->deliveryMethods()->sync([$catalog['delivery']->id]);

        Livewire::test(Form::class)
            ->call('openForm', $product->id, 'edit')
            ->set('search_terms_json', '["seed",}')
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertSame(['seed', 'farm'], $product->fresh()->search_terms);
    }

    public function test_gallery_paths_beyond_four_are_preserved_on_edit_save(): void
    {
        Storage::fake('public');

        $category = Category::query()->create([
            'slug' => 'tools',
            'code' => 'CAT-TOOLS',
            'status' => 'active',
            'name_en' => 'Tools',
            'name_ur' => 'اوزار',
        ]);
        $gallery = [
            'products/gallery/1.jpg',
            'products/gallery/2.jpg',
            'products/gallery/3.jpg',
            'products/gallery/4.jpg',
            'products/gallery/5.jpg',
            'products/gallery/6.jpg',
        ];
        $catalog = $this->requiredCatalog();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $catalog['brand']->id,
            'unit_id' => $catalog['unit']->id,
            'unit_qty' => '1',
            'pack_unit_id' => $catalog['packUnit']->id,
            'slug' => 'tool-kit',
            'code' => 'PRD-TOOLS',
            'status' => 'active',
            'name_en' => 'Tool Kit',
            'name_ur' => 'اوزار کٹ',
            'gallery_images' => $gallery,
        ]);
        $product->attributes()->sync([$catalog['attribute']->id => ['value' => 'Standard']]);
        $product->controls()->sync([$catalog['control']->id]);
        $product->deliveryMethods()->sync([$catalog['delivery']->id]);

        Livewire::test(Form::class)
            ->call('openForm', $product->id, 'edit')
            ->assertSet('galleryOverflowPaths', array_slice($gallery, 4))
            ->set('name_en', 'Updated Tool Kit')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $this->assertSame($gallery, $product->fresh()->gallery_images);
    }

    public function test_product_image_stays_temporary_until_the_form_is_saved(): void
    {
        Storage::fake('public');

        $category = Category::query()->create([
            'slug' => 'sprayers',
            'code' => 'CAT-SPRAY',
            'status' => 'active',
            'name_en' => 'Sprayers',
            'name_ur' => 'سپرے مشینیں',
        ]);
        $catalog = $this->requiredCatalog();

        $component = Livewire::test(Form::class)
            ->call('openForm')
            ->set('imageUpload', UploadedFile::fake()->image('battery-sprayer.jpg'));

        $this->assertSame(
            [],
            array_values(array_filter(
                Storage::disk('public')->allFiles(),
                fn (string $path): bool => ! str_starts_with($path, 'livewire-previews/'),
            )),
        );

        $this->fillRequiredProductFields($component, $category->id, $catalog)
            ->set('name_ur', 'بیٹری سپریئر')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $product = Product::query()->where('name_en', 'Battery Sprayer')->firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_save_and_add_next_keeps_category_hierarchy(): void
    {
        Storage::fake('public');

        $parent = Category::query()->create([
            'slug' => 'agriculture',
            'code' => 'CAT-AGRI',
            'status' => 'active',
            'name_en' => 'Agriculture',
            'name_ur' => 'زراعت',
        ]);
        $child = Category::query()->create([
            'slug' => 'seeds-child',
            'code' => 'CAT-SEED-C',
            'status' => 'active',
            'parent_id' => $parent->id,
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);
        $catalog = $this->requiredCatalog();

        $component = Livewire::test(Form::class)->call('openForm');

        $this->fillRequiredProductFields($component, $child->id, $catalog)
            ->set('name_en', 'First Seed Pack')
            ->set('name_ur', 'پہلا بیج پیک')
            ->call('addDisplayCategory', $parent->id)
            ->assertSet('parent_id', $child->id)
            ->assertSet('hierarchySlots.0', $parent->id)
            ->assertSet('hierarchySlots.1', $child->id)
            ->call('saveAndAddNext')
            ->assertHasNoErrors()
            ->assertSet('productId', null)
            ->assertSet('mode', 'create')
            ->assertSet('name_en', '')
            ->assertSet('parent_id', $child->id)
            ->assertSet('hierarchySlots.0', $parent->id)
            ->assertSet('hierarchySlots.1', $child->id)
            ->assertSet('display_category_ids', [$parent->id])
            ->assertSee(__('agricart.products.messages.saved_next'), false);

        $this->assertSame(1, Product::query()->where('name_en', 'First Seed Pack')->count());
    }

    public function test_cannot_save_product_without_brand_unit_pack_attribute_or_control(): void
    {
        $category = Category::query()->create([
            'slug' => 'glass',
            'code' => 'CAT-GLASS',
            'status' => 'active',
            'name_en' => 'Glass',
            'name_ur' => 'گلاس',
        ]);

        Livewire::test(Form::class)
            ->call('openForm')
            ->set('name_en', 'Glass')
            ->set('name_ur', 'گلاس')
            ->call('selectParent', $category->id)
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertSame(0, Product::query()->count());
    }

    public function test_deleting_a_product_removes_its_main_and_gallery_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/main.jpg', 'main');
        Storage::disk('public')->put('products/gallery/one.jpg', 'one');

        $product = Product::query()->create([
            'slug' => 'media-product',
            'code' => 'PRD-MEDIA',
            'status' => 'active',
            'name_en' => 'Media Product',
            'name_ur' => 'میڈیا پراڈکٹ',
            'image_path' => 'products/main.jpg',
            'gallery_images' => ['products/gallery/one.jpg'],
        ]);

        app(ProductService::class)->delete($product);

        Storage::disk('public')->assertMissing('products/main.jpg');
        Storage::disk('public')->assertMissing('products/gallery/one.jpg');
    }

    public function test_hidden_legacy_quantity_and_price_values_survive_an_edit_save(): void
    {
        Storage::fake('public');

        $category = Category::query()->create([
            'slug' => 'fertilizer',
            'code' => 'CAT-FERT',
            'status' => 'active',
            'name_en' => 'Fertilizer',
            'name_ur' => 'کھاد',
        ]);
        $catalog = $this->requiredCatalog();

        $product = Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $catalog['brand']->id,
            'unit_id' => $catalog['unit']->id,
            'unit_qty' => '1',
            'pack_unit_id' => $catalog['packUnit']->id,
            'slug' => 'legacy-product',
            'code' => 'PRD-LEGACY',
            'status' => 'active',
            'name_en' => 'Legacy Product',
            'name_ur' => 'پرانا پراڈکٹ',
            'pack_qty' => '12',
            'required_qty' => '25',
            'alert_qty' => '5',
            'retail_price' => '100',
            'wholesale_price' => '90',
            'super_wholesale_price' => '80',
            'distributor_price' => '70',
        ]);
        $product->attributes()->sync([$catalog['attribute']->id => ['value' => 'Standard']]);
        $product->controls()->sync([$catalog['control']->id]);
        $product->deliveryMethods()->sync([$catalog['delivery']->id]);

        Livewire::test(Form::class)
            ->call('openForm', $product->id, 'edit')
            ->assertSet('pack_qty', '12')
            ->assertSet('required_qty', '25')
            ->assertSet('alert_qty', '5')
            ->assertSet('retail_price', '100')
            ->assertSet('wholesale_price', '90')
            ->assertSet('super_wholesale_price', '80')
            ->assertSet('distributor_price', '70')
            ->set('name_en', 'Updated Legacy Product')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $product->refresh();

        $this->assertSame('12', $product->pack_qty);
        $this->assertSame('25', $product->required_qty);
        $this->assertSame('5', $product->alert_qty);
        $this->assertSame('100', $product->retail_price);
        $this->assertSame('90', $product->wholesale_price);
        $this->assertSame('80', $product->super_wholesale_price);
        $this->assertSame('70', $product->distributor_price);
    }

    /**
     * @return array{
     *     brand: Brand,
     *     unit: Unit,
     *     packUnit: PackingUnit,
     *     attribute: CatalogAttribute,
     *     control: CatalogControl,
     *     delivery: DeliveryMethod
     * }
     */
    private function requiredCatalog(): array
    {
        return [
            'brand' => Brand::query()->create([
                'slug' => 'brand-'.uniqid(),
                'code' => 'BRD-'.uniqid(),
                'status' => 'active',
                'name_en' => 'Test Brand',
                'name_ur' => 'ٹیسٹ برانڈ',
                'image_path' => 'brands/test-brand.jpg',
            ]),
            'unit' => Unit::query()->create([
                'slug' => 'unit-'.uniqid(),
                'code' => 'UNT-'.uniqid(),
                'status' => 'active',
                'name_en' => 'Piece',
                'abbreviation_en' => 'pc',
                'name_ur' => 'عدد',
                'abbreviation_ur' => 'عدد',
            ]),
            'packUnit' => PackingUnit::query()->create([
                'slug' => 'pack-'.uniqid(),
                'code' => 'PU-'.uniqid(),
                'status' => 'active',
                'name_en' => 'Box',
                'name_ur' => 'باکس',
            ]),
            'attribute' => CatalogAttribute::query()->create([
                'slug' => 'attr-'.uniqid(),
                'code' => 'ATR-'.uniqid(),
                'status' => 'active',
                'name_en' => 'Color',
                'name_ur' => 'رنگ',
            ]),
            'control' => CatalogControl::query()->create([
                'slug' => 'ctrl-'.uniqid(),
                'code' => 'CTL-'.uniqid(),
                'status' => 'active',
                'name_en' => 'Warranty',
                'name_ur' => 'وارنٹی',
                'value_en' => '1 year',
                'value_ur' => '1 سال',
            ]),
            'delivery' => DeliveryMethod::query()->create([
                'slug' => 'delivery-'.uniqid(),
                'name_en' => 'Courier',
                'name_ur' => 'کورئیر',
                'status' => DeliveryMethod::STATUS_ACTIVE,
                'sort_order' => 1,
            ]),
        ];
    }

    /**
     * @param  array{
     *     brand: Brand,
     *     unit: Unit,
     *     packUnit: PackingUnit,
     *     attribute: CatalogAttribute,
     *     control: CatalogControl,
     *     delivery: DeliveryMethod
     * }  $catalog
     */
    private function fillRequiredProductFields(Testable $component, int $categoryId, array $catalog): Testable
    {
        $catalog['delivery']->rule()->create([
            'allowed_payment_methods' => ['cash', 'bank_transfer'],
            'cod_allowed' => true,
            'cod_min_advance_percent' => 0,
            'full_advance_required' => false,
            'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
        ]);

        return $component
            ->call('selectParent', $categoryId)
            ->call('selectBrand', $catalog['brand']->id)
            ->call('selectUnit', $catalog['unit']->id)
            ->set('unit_qty', '1')
            ->call('selectPackUnit', $catalog['packUnit']->id)
            ->call('selectAttribute', 0, $catalog['attribute']->id)
            ->set('attributeRows.0.value', 'Standard')
            ->call('addControl', $catalog['control']->id)
            ->call('addDeliveryMethod', $catalog['delivery']->id);
    }
}
