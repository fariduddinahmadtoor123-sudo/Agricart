<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Units\Form;
use App\Livewire\Units\ListUnits;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Services\Catalog\ProductLabelService;
use App\Services\Catalog\UnitEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitFormIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]));
    }

    public function test_unit_can_be_created_with_server_generated_identity(): void
    {
        Livewire::test(Form::class)->call('openForm')
            ->set('name_en', 'Kilogram')->set('abbreviation_en', 'kg')
            ->set('name_ur', 'کلوگرام')->set('abbreviation_ur', 'کلو')
            ->call('saveAndClose');

        $unit = Unit::where('name_en', 'Kilogram')->firstOrFail();
        $this->assertMatchesRegularExpression('/^UNT-\d{5}$/', $unit->code);
        $this->assertSame('kilogram', $unit->slug);
    }

    public function test_catalog_viewer_cannot_create_edit_or_delete_units(): void
    {
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $permission = Permission::create(['key' => 'catalog.view', 'group_key' => 'catalog', 'label_en' => 'View', 'sort_order' => 1]);
        $role->permissions()->attach($permission);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]));
        $unit = $this->unit();

        Livewire::test(Form::class)->call('openForm')->assertForbidden();
        Livewire::test(Form::class)->call('openForm', $unit->id, 'edit')->assertForbidden();
        Livewire::test(ListUnits::class)->call('deleteUnit', $unit->id)->assertForbidden();
    }

    public function test_linked_unit_cannot_be_deleted_and_rename_refreshes_labels(): void
    {
        $unit = $this->unit();
        $product = Product::create([
            'unit_id' => $unit->id, 'slug' => 'unit-product', 'code' => 'PRD-UNIT',
            'status' => 'active', 'name_en' => 'Unit Product', 'name_ur' => 'یونٹ پراڈکٹ',
            'label_code' => 'R1-P1', 'category_visual_map_code' => 'R1', 'label_sequence' => 1,
        ]);
        app(ProductLabelService::class)->generateForProduct($product);

        Livewire::test(ListUnits::class)->call('deleteUnit', $unit->id);
        $this->assertDatabaseHas('units', ['id' => $unit->id]);

        Livewire::test(Form::class)->call('openForm', $unit->id, 'edit')
            ->set('name_en', 'Updated Unit')->call('saveAndClose');

        $lines = collect($product->fresh()->label_qr['en']['lines']);
        $this->assertTrue($lines->contains(fn (array $line): bool => $line['label'] === 'Unit' && str_contains($line['value'], 'Updated Unit')));
    }

    public function test_ai_parser_rejects_invalid_shapes(): void
    {
        $service = app(UnitEnrichmentService::class);
        $this->assertNull($service->parseResponse('[]'));
        $this->assertNull($service->parseResponse('{}'));
        $this->assertNull($service->parseResponse('{"name_en":["invalid"]}'));
        $this->assertSame('kg', $service->parseResponse('{"abbreviation_en":"kg"}')['abbreviation_en']);
    }

    public function test_empty_ai_attempt_does_not_hold_the_distributed_lock(): void
    {
        Livewire::test(Form::class)->call('openForm')
            ->call('enrichWithAi')
            ->assertSet('statusMessage', __('agricart.units.ai.name_required'))
            ->set('name_en', 'Kilogram')
            ->call('enrichWithAi')
            ->assertSet('statusMessage', __('agricart.units.ai.no_configuration'));
    }

    public function test_inactive_units_are_not_offered_for_new_product_selection(): void
    {
        $inactive = $this->unit();
        $inactive->update(['status' => 'inactive']);
        Unit::create([
            'code' => 'UNT-00002', 'slug' => 'kilogram-active', 'status' => 'active',
            'name_en' => 'Kilogram Active', 'abbreviation_en' => 'kg',
            'name_ur' => 'کلوگرام فعال', 'abbreviation_ur' => 'کلو',
        ]);

        Livewire::test(\App\Livewire\Products\Form::class)
            ->call('openForm')
            ->set('unit_search', 'Kilogram')
            ->assertSee('Kilogram Active')
            ->assertDontSee('Kilogram (kg)');
    }

    private function unit(): Unit
    {
        return Unit::create([
            'code' => 'UNT-00001', 'slug' => 'kilogram', 'status' => 'active',
            'name_en' => 'Kilogram', 'abbreviation_en' => 'kg',
            'name_ur' => 'کلوگرام', 'abbreviation_ur' => 'کلو',
        ]);
    }
}
