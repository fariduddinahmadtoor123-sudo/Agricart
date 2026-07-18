<?php

namespace Tests\Feature\Catalog;

use App\Livewire\PackingUnits\Form;
use App\Livewire\PackingUnits\ListPackingUnits;
use App\Models\PackingUnit;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\PackingUnitEnrichmentService;
use App\Services\Catalog\ProductLabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackingUnitFormIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]));
    }

    public function test_packing_unit_can_be_created_with_server_generated_identity(): void
    {
        Livewire::test(Form::class)->call('openForm')
            ->set('name_en', 'Carton')->set('name_ur', 'کارٹن')
            ->call('saveAndClose');

        $packingUnit = PackingUnit::where('name_en', 'Carton')->firstOrFail();
        $this->assertMatchesRegularExpression('/^PKG-\d{5}$/', $packingUnit->code);
        $this->assertSame('carton', $packingUnit->slug);
    }

    public function test_catalog_viewer_cannot_create_edit_or_delete_packing_units(): void
    {
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $permission = Permission::create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'View',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]));
        $packingUnit = $this->packingUnit();

        Livewire::test(Form::class)->call('openForm')->assertForbidden();
        Livewire::test(Form::class)->call('openForm', $packingUnit->id, 'edit')->assertForbidden();
        Livewire::test(ListPackingUnits::class)->call('deletePackingUnit', $packingUnit->id)->assertForbidden();
    }

    public function test_linked_packing_unit_cannot_be_deleted_and_rename_refreshes_labels(): void
    {
        $packingUnit = $this->packingUnit();
        $product = Product::create([
            'pack_unit_id' => $packingUnit->id,
            'slug' => 'packing-product',
            'code' => 'PRD-PACK',
            'status' => 'active',
            'name_en' => 'Packing Product',
            'name_ur' => 'پیکنگ پراڈکٹ',
            'label_code' => 'R1-P1',
            'category_visual_map_code' => 'R1',
            'label_sequence' => 1,
        ]);
        app(ProductLabelService::class)->generateForProduct($product);

        Livewire::test(ListPackingUnits::class)->call('deletePackingUnit', $packingUnit->id);
        $this->assertDatabaseHas('packing_units', ['id' => $packingUnit->id]);

        Livewire::test(Form::class)->call('openForm', $packingUnit->id, 'edit')
            ->set('name_en', 'Updated Carton')->call('saveAndClose');

        $lines = collect($product->fresh()->label_qr['en']['lines']);
        $this->assertTrue($lines->contains(
            fn (array $line): bool => $line['label'] === 'Pack'
                && str_contains($line['value'], 'Updated Carton'),
        ));
    }

    public function test_ai_parser_rejects_invalid_shapes_and_values(): void
    {
        $service = app(PackingUnitEnrichmentService::class);

        $this->assertNull($service->parseResponse('[]'));
        $this->assertNull($service->parseResponse('{}'));
        $this->assertNull($service->parseResponse('{"name_en":["invalid"]}'));
        $this->assertNull($service->parseResponse('{"english_name":"Carton"}'));
        $this->assertNull($service->parseResponse('{"name_en":"'.str_repeat('x', 129).'"}'));
        $this->assertSame('Carton', $service->parseResponse('{"name_en":"Carton"}')['name_en']);
    }

    public function test_empty_ai_attempt_does_not_hold_the_distributed_lock(): void
    {
        Livewire::test(Form::class)->call('openForm')
            ->call('enrichWithAi')
            ->assertSet('statusMessage', __('agricart.packing_units.ai.name_required'))
            ->set('name_en', 'Carton')
            ->call('enrichWithAi')
            ->assertSet('statusMessage', __('agricart.packing_units.ai.no_configuration'));
    }

    public function test_inactive_packing_units_are_hidden_and_cannot_be_selected_directly(): void
    {
        $inactive = $this->packingUnit();
        $inactive->update(['status' => 'inactive']);
        $active = PackingUnit::create([
            'code' => 'PKG-00002',
            'slug' => 'carton-active',
            'status' => 'active',
            'name_en' => 'Carton Active',
            'name_ur' => 'فعال کارٹن',
        ]);

        Livewire::test(\App\Livewire\Products\Form::class)
            ->call('openForm')
            ->set('pack_unit_search', 'Carton')
            ->assertSee('Carton Active')
            ->assertDontSee('Carton — کارٹن')
            ->call('selectPackUnit', $inactive->id)
            ->assertSet('pack_unit_id', null)
            ->call('selectPackUnit', $active->id)
            ->assertSet('pack_unit_id', $active->id);
    }

    public function test_existing_inactive_packing_unit_assignment_remains_displayable(): void
    {
        $inactive = $this->packingUnit();
        $inactive->update(['status' => 'inactive']);
        $product = Product::create([
            'pack_unit_id' => $inactive->id,
            'slug' => 'legacy-packing-product',
            'code' => 'PRD-LEGACY-PACK',
            'status' => 'active',
            'name_en' => 'Legacy Packing Product',
            'name_ur' => 'پرانی پیکنگ پراڈکٹ',
        ]);

        Livewire::test(\App\Livewire\Products\Form::class)
            ->call('openForm', $product->id, 'edit')
            ->assertSet('pack_unit_id', $inactive->id)
            ->assertSee('Carton');
    }

    private function packingUnit(): PackingUnit
    {
        return PackingUnit::create([
            'code' => 'PKG-00001',
            'slug' => 'carton',
            'status' => 'active',
            'name_en' => 'Carton',
            'name_ur' => 'کارٹن',
        ]);
    }
}
