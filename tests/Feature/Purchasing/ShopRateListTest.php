<?php

namespace Tests\Feature\Purchasing;

use App\Filament\Clusters\Purchasing\RateListPage;
use App\Livewire\RateLists\ListRateLists;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Purchasing\ShopRateListService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShopRateListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_rate_list_page_is_reachable(): void
    {
        $this->get(RateListPage::getUrl())->assertOk();
    }

    public function test_only_products_with_a_retail_rate_are_listed_alphabetically(): void
    {
        $urea = $this->makeProduct('READY', 'Urea 50kg', 'یوریا', 12, '250');
        $bag = $this->makeProduct('NO-STOCK', 'Out of stock bag', 'ختم', 0, '180');
        $this->makeProduct('NO-RATE', 'Unpriced seed', 'بیج', 8, null);
        $this->makeProduct('ZERO-RATE', 'Zero rate tool', 'اوزار', 5, '0');

        $rows = app(ShopRateListService::class)->rowsForPrint(null, 'both');

        $this->assertCount(2, $rows);
        $this->assertSame($bag->id, $rows[0]['product_id']);
        $this->assertSame('Out of stock bag', $rows[0]['name_en']);
        $this->assertSame('180', $rows[0]['rate']);
        $this->assertSame($urea->id, $rows[1]['product_id']);
        $this->assertSame('Urea 50kg', $rows[1]['name_en']);
        $this->assertSame('یوریا', $rows[1]['name_ur']);
        $this->assertSame('250', $rows[1]['rate']);
    }

    public function test_print_page_uses_english_urdu_or_both_name_columns(): void
    {
        $this->makeProduct('READY', 'DAP Bag', 'ڈی اے پی', 4, '410');

        $this->get(route('admin.rate-list.print', ['locale' => 'en', 'autoprint' => 0]))
            ->assertOk()
            ->assertSee('DAP Bag', false)
            ->assertSee(__('rate_lists.columns.name'), false)
            ->assertDontSee(__('rate_lists.columns.name_ur'), false);

        $this->get(route('admin.rate-list.print', ['locale' => 'ur', 'autoprint' => 0]))
            ->assertOk()
            ->assertSee('ڈی اے پی', false)
            ->assertSee(__('rate_lists.columns.sr', [], 'ur'), false);

        $this->get(route('admin.rate-list.print', ['locale' => 'both', 'autoprint' => 0]))
            ->assertOk()
            ->assertSee('DAP Bag', false)
            ->assertSee('ڈی اے پی', false)
            ->assertSee(__('rate_lists.columns.name_en'), false)
            ->assertSee(__('rate_lists.columns.name_ur'), false);
    }

    public function test_print_chunks_about_thirty_products_per_page(): void
    {
        for ($i = 1; $i <= 31; $i++) {
            $this->makeProduct('SKU-'.$i, 'Item '.$i, 'آئٹم '.$i, 2, (string) (100 + $i));
        }

        $pages = app(ShopRateListService::class)->chunkForPages(
            app(ShopRateListService::class)->rowsForPrint(null, 'en'),
        );

        $this->assertCount(2, $pages);
        $this->assertCount(30, $pages[0]);
        $this->assertCount(1, $pages[1]);
        $this->assertSame(31, $pages[1][0]['sr']);
    }

    public function test_list_toolbar_print_opens_current_locale(): void
    {
        $this->makeProduct('READY', 'Spray pump', 'سپرے', 3, '1500');

        Livewire::test(ListRateLists::class)
            ->assertSee('Spray pump')
            ->set('locale', 'ur')
            ->call('openPrint')
            ->assertDispatched('rate-list-open-print');
    }

    private function makeProduct(
        string $sku,
        string $nameEn,
        string $nameUr,
        float $stock,
        ?string $retail,
    ): Product {
        return Product::query()->create([
            'code' => $sku,
            'slug' => strtolower($sku).'-'.uniqid(),
            'status' => 'active',
            'name_en' => $nameEn,
            'name_ur' => $nameUr,
            'sku' => $sku,
            'stock_qty' => $stock,
            'retail_price' => $retail,
        ]);
    }
}
