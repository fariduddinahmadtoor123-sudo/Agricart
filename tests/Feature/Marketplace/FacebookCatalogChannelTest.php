<?php

namespace Tests\Feature\Marketplace;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesChannel;
use App\Models\User;
use App\Services\SalesChannels\FacebookCatalogFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FacebookCatalogChannelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_facebook_feed_requires_enabled_channel_and_token(): void
    {
        $channel = SalesChannel::facebook();
        $token = (string) $channel->setting('feed_token');

        $this->get(route('store.facebook_catalog_feed', ['token' => $token]))
            ->assertNotFound();

        $channel->update(['is_enabled' => true]);
        app(FacebookCatalogFeedService::class)->clearCache();

        $this->get(route('store.facebook_catalog_feed', ['token' => 'wrong']))
            ->assertForbidden();

        $this->get(route('store.facebook_catalog_feed', ['token' => $token]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_facebook_feed_includes_priced_imaged_products_with_store_links(): void
    {
        $channel = SalesChannel::facebook();
        $channel->update(['is_enabled' => true]);
        $token = (string) $channel->setting('feed_token');

        $category = Category::query()->create([
            'slug' => 'fb-cat',
            'code' => 'CAT-FB',
            'status' => 'active',
            'name_en' => 'FB Cat',
            'name_ur' => 'ایف بی',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-FB-1',
            'sku' => 'SKU-FB-1',
            'name_en' => 'Facebook Mesh',
            'name_ur' => 'میش',
            'slug' => 'facebook-mesh',
            'wholesale_price' => 250,
            'retail_price' => 300,
            'image_path' => 'products/mesh.jpg',
            'stock_qty' => 5,
            'short_description_en' => 'Strong mesh for farms.',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-FB-2',
            'sku' => 'SKU-FB-2',
            'name_en' => 'No Price Item',
            'name_ur' => 'بغیر قیمت',
            'slug' => 'no-price-item',
            'image_path' => 'products/x.jpg',
            'stock_qty' => 1,
        ]);

        app(FacebookCatalogFeedService::class)->clearCache();

        $response = $this->get(route('store.facebook_catalog_feed', ['token' => $token]));
        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringContainsString('SKU-FB-1', $body);
        $this->assertStringContainsString('250.00 PKR', $body);
        $this->assertStringContainsString(route('store.product', $product->slug), $body);
        $this->assertStringNotContainsString('SKU-FB-2', $body);
    }

    public function test_sales_channel_form_can_save_facebook_settings(): void
    {
        Livewire::test(\App\Livewire\SalesChannels\Form::class)
            ->call('openForm', null, 'create', SalesChannel::SLUG_FACEBOOK)
            ->set('facebook_page_url', 'https://www.facebook.com/AgricartPK')
            ->set('facebook_price_field', 'wholesale_price')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $channel = SalesChannel::query()->where('slug', SalesChannel::SLUG_FACEBOOK)->first();
        $this->assertNotNull($channel);
        $this->assertSame('https://www.facebook.com/AgricartPK', $channel->setting('page_url'));
        $this->assertNotEmpty($channel->setting('feed_token'));
    }
}
