<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Settings\ManageBrandReviewMessages;
use App\Models\BrandAgricartReviewMessage;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\BrandAgricartReviewMessageService;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrandAgricartReviewMessagesTest extends TestCase
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

    public function test_migration_seeds_message_for_every_criterion_including_product_availability(): void
    {
        $this->assertSame(
            BrandAgricartReviewCriteria::count(),
            BrandAgricartReviewMessage::query()->count(),
        );

        $this->assertDatabaseHas('brand_agricart_review_messages', [
            'criterion_key' => 'product_availability',
            'positive_en' => 'Products remain consistently available throughout the year.',
            'neutral_en' => 'Products are generally available, with occasional supply fluctuations.',
            'needs_improvement_en' => 'Product availability becomes limited during peak seasons.',
        ]);

        $this->assertDatabaseHas('brand_agricart_review_messages', [
            'criterion_key' => 'product_quality',
            'needs_improvement_en' => 'Product quality is not consistently maintained.',
        ]);
    }

    public function test_settings_manager_can_update_default_messages(): void
    {
        Livewire::test(ManageBrandReviewMessages::class)
            ->set('messages.product_availability.positive_en', 'Always stocked for peak season demand.')
            ->set('messages.product_availability.positive_ur', 'سیزن کی مانگ کے لیے ہمیشہ اسٹاک موجود۔')
            ->call('save')
            ->assertSet('statusType', 'success');

        $row = BrandAgricartReviewMessage::query()
            ->where('criterion_key', 'product_availability')
            ->firstOrFail();

        $this->assertSame('Always stocked for peak season demand.', $row->positive_en);

        $suggestion = app(BrandAgricartReviewMessageService::class)
            ->suggestionFor('product_availability', 9);

        $this->assertSame('Always stocked for peak season demand.', $suggestion['en']);
    }

    public function test_neutral_band_uses_neutral_message(): void
    {
        $suggestion = app(BrandAgricartReviewMessageService::class)
            ->suggestionFor('product_quality', 6);

        $this->assertSame(
            'Delivers satisfactory product quality for most applications.',
            $suggestion['en'],
        );
    }
}
