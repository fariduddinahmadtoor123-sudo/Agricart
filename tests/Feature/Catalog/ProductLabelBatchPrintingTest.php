<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Labels\PrintLabelsForm;
use App\Models\CompanyProfile;
use App\Models\Permission;
use App\Models\PrintProfile;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductLabelBatchPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_print_form_searches_label_ready_products_and_builds_a_queue(): void
    {
        CompanyProfile::current()->update([
            'business_name_en' => 'Agricart Store',
            'business_name_ur' => 'ایگری کارٹ سٹور',
        ]);
        $product = $this->product('Searchable Sprayer', 'PRD-SPRAYER', 'R1-P1');
        Product::create([
            'slug' => 'without-label',
            'code' => 'PRD-NO-LABEL',
            'status' => 'active',
            'name_en' => 'Unlabelled Product',
            'name_ur' => 'بغیر لیبل',
        ]);

        Livewire::test(PrintLabelsForm::class)
            ->call('openForm')
            ->set('search', 'Sprayer')
            ->assertSee('Searchable Sprayer')
            ->assertDontSee('Unlabelled Product')
            ->call('addProduct', $product->id)
            ->assertSet('selectedProductIds', [$product->id])
            ->assertSet("enabledVariants.{$product->id}", ['barcode_en'])
            ->assertSee('Agricart Store')
            ->assertSee('38 × 50 mm label')
            ->assertSee('50 × 30 mm label')
            ->assertSee('75 × 50 mm product label')
            ->assertSee('Barcode Label (English)')
            ->set('labelLanguage', 'ur')
            ->assertSet("enabledVariants.{$product->id}", ['barcode_ur'])
            ->assertSee('Barcode Label (Urdu)');
    }

    public function test_batch_print_renders_requested_copies_and_filters_optional_fields(): void
    {
        CompanyProfile::current()->update(['business_name_en' => 'Agricart Store']);
        $product = $this->product('Batch Product', 'PRD-BATCH-SECRET', 'R1-P2');
        $profile = PrintProfile::query()
            ->where('category', PrintProfile::CATEGORY_LABEL)
            ->where('label_kind', PrintProfile::LABEL_KIND_PRODUCT)
            ->firstOrFail();

        $response = $this->post(route('admin.labels.batch-print'), [
            'profile' => $profile->key,
            'language' => 'both',
            'fields' => ['store_name', 'product_name'],
            'items' => [
                $product->id => [
                    'product_id' => $product->id,
                    'enabled' => ['barcode_en', 'qr_ur'],
                    'copies' => [
                        'barcode_en' => 2,
                        'qr_ur' => 1,
                    ],
                ],
            ],
            'autoprint' => '0',
        ]);

        $response->assertOk()
            ->assertSee('Batch Product')
            ->assertSee('Agricart Store')
            ->assertDontSee('System Code')
            ->assertDontSee('PRD-BATCH-SECRET')
            ->assertSee('print-label__qr-layout', false)
            ->assertSee('margin-inline: auto', false)
            ->assertDontSee('window.setTimeout');
        $this->assertSame(3, substr_count($response->getContent(), '<article class="print-label"'));
    }

    public function test_batch_print_accepts_all_sticker_sizes_rejects_documents_and_limits_copies(): void
    {
        $product = $this->product('Protected Product', 'PRD-PROTECTED', 'R1-P3');
        $priceProfile = PrintProfile::query()
            ->where('category', PrintProfile::CATEGORY_LABEL)
            ->where('label_kind', PrintProfile::LABEL_KIND_PRICE)
            ->firstOrFail();

        $payload = [
            'profile' => $priceProfile->key,
            'language' => 'en',
            'fields' => ['product_name'],
            'items' => [
                $product->id => [
                    'product_id' => $product->id,
                    'enabled' => ['barcode_en'],
                    'copies' => ['barcode_en' => 1],
                ],
            ],
        ];

        $this->post(route('admin.labels.batch-print'), $payload)->assertOk();

        $documentProfile = PrintProfile::query()
            ->where('category', PrintProfile::CATEGORY_DOCUMENT)
            ->firstOrFail();
        $payload['profile'] = $documentProfile->key;
        $this->post(route('admin.labels.batch-print'), $payload)->assertNotFound();

        $productProfile = PrintProfile::query()
            ->where('category', PrintProfile::CATEGORY_LABEL)
            ->where('label_kind', PrintProfile::LABEL_KIND_PRODUCT)
            ->firstOrFail();
        $items = [];

        for ($index = 0; $index < 6; $index++) {
            $item = $this->product(
                "Bulk Product {$index}",
                "PRD-BULK-{$index}",
                "R2-P{$index}",
            );
            $items[$item->id] = [
                'product_id' => $item->id,
                'enabled' => ['qr_en'],
                'copies' => ['qr_en' => 100],
            ];
        }

        $this->post(route('admin.labels.batch-print'), [
            'profile' => $productProfile->key,
            'language' => 'en',
            'fields' => ['product_name'],
            'items' => $items,
        ])->assertSessionHasErrors('items');
    }

    public function test_catalog_view_permission_is_required_for_form_and_batch_print(): void
    {
        $viewerRole = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $viewPermission = Permission::create([
            'key' => 'catalog.labels.view',
            'group_key' => 'catalog',
            'label_en' => 'View labels',
            'sort_order' => 1,
        ]);
        Permission::create([
            'key' => 'catalog.labels.print',
            'group_key' => 'catalog',
            'label_en' => 'Print labels',
            'sort_order' => 2,
        ]);
        $viewerRole->permissions()->sync(
            Permission::query()->whereIn('key', ['catalog.labels.view', 'catalog.labels.print'])->pluck('id')->all()
        );
        $viewer = User::factory()->create([
            'role_id' => $viewerRole->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $product = $this->product('Viewer Product', 'PRD-VIEW', 'R1-P4');
        $profile = PrintProfile::query()
            ->where('category', PrintProfile::CATEGORY_LABEL)
            ->where('label_kind', PrintProfile::LABEL_KIND_PRODUCT)
            ->firstOrFail();

        $this->actingAs($viewer);
        Livewire::test(PrintLabelsForm::class)->assertOk()->call('openForm');
        $this->post(route('admin.labels.batch-print'), $this->payload($product, $profile))
            ->assertOk();

        $limitedRole = Role::create(['name' => 'Limited', 'slug' => 'limited-no-catalog']);
        $unauthorized = User::factory()->create(['status' => User::STATUS_ACTIVE, 'role_id' => $limitedRole->id]);
        $this->actingAs($unauthorized);
        Livewire::test(PrintLabelsForm::class)->call('openForm')->assertForbidden();
        $this->post(route('admin.labels.batch-print'), $this->payload($product, $profile))
            ->assertForbidden();
    }

    private function product(string $name, string $code, string $labelCode): Product
    {
        return Product::create([
            'slug' => str($name)->slug().'-'.str()->random(5),
            'code' => $code,
            'status' => 'active',
            'name_en' => $name,
            'name_ur' => 'پراڈکٹ',
            'label_code' => $labelCode,
            'category_visual_map_code' => 'R1',
            'label_sequence' => random_int(1, 60000),
        ]);
    }

    private function payload(Product $product, PrintProfile $profile): array
    {
        return [
            'profile' => $profile->key,
            'language' => 'en',
            'fields' => ['product_name', 'printed_code'],
            'items' => [
                $product->id => [
                    'product_id' => $product->id,
                    'enabled' => ['barcode_en'],
                    'copies' => ['barcode_en' => 1],
                ],
            ],
            'autoprint' => '0',
        ];
    }
}
