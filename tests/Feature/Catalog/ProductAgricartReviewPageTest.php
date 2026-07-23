<?php

namespace Tests\Feature\Catalog;

use App\Filament\Clusters\Catalog\ProductReviewsPage;
use App\Livewire\Products\AgricartReviewEditor;
use App\Livewire\Products\ListProductAgricartReviews;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAgricartReviewPageTest extends TestCase
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

    public function test_catalog_product_reviews_page_shows_list_not_inline_editor(): void
    {
        $product = $this->createRatedProduct();

        $this->get(ProductReviewsPage::getUrl())
            ->assertOk()
            ->assertSeeLivewire(ListProductAgricartReviews::class)
            ->assertDontSeeLivewire(AgricartReviewEditor::class)
            ->assertSee('Prat Sprayer', false)
            ->assertSee(__('agricart.products.review_page.actions.add'), false);
    }

    public function test_list_only_includes_products_with_agricart_rating(): void
    {
        $rated = $this->createRatedProduct();
        $this->createProduct('Unrated Sprayer', 'prat-unrated');

        Livewire::test(ListProductAgricartReviews::class)
            ->assertSee('Prat Sprayer', false)
            ->assertDontSee('Unrated Sprayer', false);
    }

    public function test_editor_can_select_and_save_agricart_review_via_panel_actions(): void
    {
        $product = $this->createProduct();

        $component = Livewire::test(AgricartReviewEditor::class)
            ->call('openForm', null, 'create')
            ->assertSet('mode', 'create')
            ->set('product_search', 'Prat')
            ->assertSee('Prat Sprayer', false)
            ->call('selectProduct', $product->id)
            ->assertSet('productId', $product->id)
            ->assertSet('productNameEn', 'Prat Sprayer')
            ->assertSet('brandNameEn', 'Prat Brand');

        foreach (BrandAgricartReviewCriteria::keys() as $index => $key) {
            $rating = $index === 0 ? 9 : 8;
            $component->call('setAgricartCriterion', $key, $rating);
        }

        $component
            ->call('handlePanelAction', 'save_close')
            ->assertDispatched('product-reviews-saved')
            ->assertDispatched('close-form-panel');

        $product->refresh();

        $this->assertSame(8, $product->agricart_rating);
        $this->assertNotEmpty($product->agricart_rating_reason_en);
    }

    public function test_edit_from_list_opens_form_with_product_loaded(): void
    {
        $product = $this->createRatedProduct();

        Livewire::test(ListProductAgricartReviews::class)
            ->call('openEdit', $product->id)
            ->assertDispatched(
                'open-form-panel',
                formKey: 'product-reviews',
                productId: $product->id,
                mode: 'edit',
            );

        Livewire::test(AgricartReviewEditor::class)
            ->call('openForm', $product->id, 'edit')
            ->assertSet('mode', 'edit')
            ->assertSet('productId', $product->id)
            ->assertSet('agricart_rating', (string) $product->agricart_rating);
    }

    private function createRatedProduct(): Product
    {
        $product = $this->createProduct();
        $criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $criteria[$key] = [
                'rating' => 8,
                'reason_en' => 'Strong '.$key,
                'reason_ur' => 'مضبوط '.$key,
            ];
        }

        $product->update([
            'agricart_rating' => BrandAgricartReviewCriteria::score($criteria),
            'agricart_rating_criteria' => BrandAgricartReviewCriteria::forStorage($criteria),
            'agricart_rating_reason_en' => 'Solid product.',
            'agricart_rating_reason_ur' => 'مضبوط پروڈکٹ۔',
        ]);

        return $product->fresh();
    }

    private function createProduct(string $nameEn = 'Prat Sprayer', string $slug = 'prat-sprayer-review-page'): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'cat-product-review-page'],
            [
                'code' => 'CAT-PRP',
                'status' => 'active',
                'name_en' => 'Tools',
                'name_ur' => 'اوزار',
            ],
        );

        $brand = Brand::query()->firstOrCreate(
            ['slug' => 'brand-prp'],
            [
                'code' => 'BRD-PRP',
                'status' => 'active',
                'name_en' => 'Prat Brand',
                'name_ur' => 'پرات برانڈ',
                'image_path' => 'brands/prp.webp',
                'ai_status' => 'none',
            ],
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'code' => 'PRD-'.strtoupper(substr(md5($slug), 0, 6)),
            'sku' => 'SKU-'.strtoupper(substr(md5($slug), 0, 4)),
            'name_en' => $nameEn,
            'name_ur' => 'پرات سپرے',
            'slug' => $slug,
            'full_description_en' => 'A sprayer.',
        ]);
    }
}
