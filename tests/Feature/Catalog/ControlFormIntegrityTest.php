<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Controls\Form;
use App\Livewire\Controls\ListControls;
use App\Models\CatalogControl;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\ControlEnrichmentService;
use App\Services\Catalog\ControlService;
use App\Services\Catalog\ProductLabelService;
use App\Services\Catalog\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ControlFormIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => true]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_control_identity_is_allocated_and_preserved_by_the_server(): void
    {
        $service = app(ControlService::class);
        $control = $service->create([
            'code' => 'FORGED',
            'slug' => 'forged',
            'name_en' => 'Pressure',
            'name_ur' => 'دباؤ',
            'value_en' => '10 bar',
            'value_ur' => '۱۰ بار',
            'ai_status' => 'failed',
        ]);

        $this->assertMatchesRegularExpression('/^CTL-\d{5}$/', $control->code);
        $this->assertSame('pressure', $control->slug);

        $updated = $service->update($control, [
            'code' => 'CHANGED',
            'slug' => 'changed',
            'status' => 'inactive',
            'name_en' => 'Working Pressure',
            'name_ur' => 'کام کا دباؤ',
            'value_en' => '12 bar',
            'value_ur' => '۱۲ بار',
            'ai_status' => 'manual',
        ]);

        $this->assertSame($control->code, $updated->code);
        $this->assertSame('pressure', $updated->slug);
        $this->assertSame('inactive', $updated->status);
    }

    public function test_permissions_are_enforced_and_action_ui_is_conditional(): void
    {
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $permission = Permission::create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'View',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission);
        $viewer = User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]);
        $control = $this->control();

        $this->actingAs($viewer);
        Livewire::test(Form::class)->call('openForm', $control->id, 'view');
        Livewire::test(ListControls::class)
            ->assertOk()
            ->assertSee(__('agricart.controls.actions.view_short'))
            ->assertDontSee(__('agricart.controls.actions.edit_short'))
            ->assertDontSee(__('agricart.controls.actions.delete_short'));
        Livewire::test(Form::class)->call('openForm')->assertForbidden();
        Livewire::test(Form::class)->call('openForm', $control->id, 'edit')->assertForbidden();
        Livewire::test(ListControls::class)->call('deleteControl', $control->id)->assertForbidden();

        $this->actingAs(User::factory()->create(['status' => User::STATUS_ACTIVE]));
        Livewire::test(ListControls::class)->assertForbidden();
    }

    public function test_list_never_renders_control_values_or_accepts_value_columns(): void
    {
        $control = $this->control();

        Livewire::test(ListControls::class)
            ->assertSee($control->name_en)
            ->assertSee($control->name_ur)
            ->assertDontSee($control->value_en)
            ->assertDontSee($control->value_ur)
            ->call('toggleColumn', 'value_en', true)
            ->call('toggleColumn', 'value_ur', true)
            ->assertSet('visibleColumns', ['code', 'name_en', 'name_ur', 'status', 'ai_status', 'created_at'])
            ->assertDontSee($control->value_en)
            ->assertDontSee($control->value_ur);

        Livewire::test(Form::class)
            ->call('openForm', $control->id, 'view')
            ->assertSet('value_en', $control->value_en)
            ->assertSet('value_ur', $control->value_ur)
            ->assertSee('agricart-control-value-en')
            ->assertSee('agricart-control-value-ur');
    }

    public function test_linked_control_cannot_be_deleted(): void
    {
        $control = $this->control();
        $product = $this->product();
        $product->controls()->attach($control->id);

        Livewire::test(ListControls::class)->call('deleteControl', $control->id);

        $this->assertDatabaseHas('controls', ['id' => $control->id]);
        $this->assertDatabaseHas('product_control', [
            'product_id' => $product->id,
            'control_id' => $control->id,
        ]);
    }

    public function test_name_and_value_changes_refresh_linked_product_labels(): void
    {
        $control = $this->control();
        $product = $this->product();
        $product->controls()->attach($control->id);
        app(ProductLabelService::class)->generateForProduct($product->fresh('controls'));

        app(ControlService::class)->update($control, [
            'status' => 'active',
            'name_en' => 'Working Pressure',
            'name_ur' => 'کام کا دباؤ',
            'value_en' => '12 bar',
            'value_ur' => '۱۲ بار',
            'ai_status' => 'manual',
        ]);

        $product->refresh();
        $this->assertTrue(collect($product->label_qr['en']['lines'])->contains(
            fn (array $line): bool => $line['label'] === 'Working Pressure' && $line['value'] === '12 bar',
        ));
        $this->assertTrue(collect($product->label_qr['ur']['lines'])->contains(
            fn (array $line): bool => $line['label'] === 'کام کا دباؤ' && $line['value'] === '۱۲ بار',
        ));
    }

    public function test_ai_parser_and_distributed_lock_reject_unsafe_input(): void
    {
        $service = app(ControlEnrichmentService::class);

        $this->assertNull($service->parseResponse('[]'));
        $this->assertNull($service->parseResponse('{}'));
        $this->assertNull($service->parseResponse('{"name_en":["invalid"]}'));
        $this->assertNull($service->parseResponse('{"english_name":"Pressure"}'));
        $this->assertNull($service->parseResponse('{"name_en":"Pressure","unexpected":"value"}'));
        $this->assertNull($service->parseResponse('{"name_en":"'.str_repeat('x', 129).'"}'));
        $this->assertNull($service->parseResponse('{"value_en":"'.str_repeat('x', 1001).'"}'));
        $this->assertSame('Pressure', $service->parseResponse('{"name_en":"Pressure"}')['name_en']);

        $lock = Cache::lock('control-ai-enrichment:'.auth()->id().':new', 120);
        $this->assertTrue($lock->get());

        try {
            Livewire::test(Form::class)
                ->call('openForm')
                ->set('name_en', 'Pressure')
                ->call('enrichWithAi')
                ->assertSet('statusMessage', __('agricart.controls.ai.already_running'));
        } finally {
            $lock->release();
        }
    }

    public function test_inactive_control_selection_is_blocked_but_legacy_links_are_preserved(): void
    {
        $inactive = $this->control();
        $inactive->update(['status' => 'inactive']);
        $product = $this->product();

        Livewire::test(\App\Livewire\Products\Form::class)
            ->call('openForm')
            ->set('control_search', 'Pressure')
            ->assertDontSee('Pressure')
            ->call('addControl', $inactive->id)
            ->assertSet('selectedControls', []);

        try {
            app(ProductService::class)->syncControls($product, [$inactive->id]);
            $this->fail('Inactive forged control assignment was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('product_control', [
                'product_id' => $product->id,
                'control_id' => $inactive->id,
            ]);
        }

        $product->controls()->attach($inactive->id);
        app(ProductService::class)->syncControls($product, [$inactive->id]);

        $this->assertDatabaseHas('product_control', [
            'product_id' => $product->id,
            'control_id' => $inactive->id,
        ]);

        Livewire::test(\App\Livewire\Products\Form::class)
            ->call('openForm', $product->id, 'edit')
            ->assertSet('selectedControls.0.id', $inactive->id)
            ->assertSee('Pressure');
    }

    private function control(): CatalogControl
    {
        return CatalogControl::create([
            'code' => 'CTL-00001',
            'slug' => 'pressure',
            'status' => 'active',
            'name_en' => 'Pressure',
            'name_ur' => 'دباؤ',
            'value_en' => '10 bar',
            'value_ur' => '۱۰ بار',
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'slug' => 'control-product-'.str()->random(6),
            'code' => 'PRD-'.str()->upper(str()->random(8)),
            'status' => 'active',
            'name_en' => 'Control Product',
            'name_ur' => 'کنٹرول پراڈکٹ',
            'label_code' => 'R1-P'.random_int(1, 99999),
            'category_visual_map_code' => 'R1',
            'label_sequence' => random_int(1, 99999),
        ]);
    }
}
