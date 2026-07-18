<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Attributes\Form;
use App\Livewire\Attributes\ListAttributes;
use App\Models\CatalogAttribute;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\AttributeEnrichmentService;
use App\Services\Catalog\AttributeService;
use App\Services\Catalog\ProductLabelService;
use App\Services\Catalog\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AttributeFormIntegrityTest extends TestCase
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

    public function test_attribute_identity_is_allocated_by_the_server(): void
    {
        $attribute = app(AttributeService::class)->create([
            'code' => 'FORGED',
            'slug' => 'forged',
            'name_en' => 'Voltage',
            'name_ur' => 'وولٹیج',
        ]);

        $this->assertMatchesRegularExpression('/^ATR-\d{5}$/', $attribute->code);
        $this->assertNotSame('FORGED', $attribute->code);
        $this->assertSame('voltage', $attribute->slug);
    }

    public function test_attribute_permissions_are_enforced_for_mutations_view_and_list_rendering(): void
    {
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $permission = Permission::create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'View',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission);
        $user = User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]);
        $attribute = $this->attribute();

        $this->actingAs($user);
        Livewire::test(Form::class)->call('openForm', $attribute->id, 'view');
        Livewire::test(ListAttributes::class)->assertOk();
        Livewire::test(Form::class)->call('openForm')->assertForbidden();
        Livewire::test(Form::class)->call('openForm', $attribute->id, 'edit')->assertForbidden();
        Livewire::test(ListAttributes::class)->call('deleteAttribute', $attribute->id)->assertForbidden();

        $this->actingAs(User::factory()->create(['status' => User::STATUS_ACTIVE]));
        Livewire::test(ListAttributes::class)->assertForbidden();
    }

    public function test_linked_attribute_cannot_be_deleted(): void
    {
        $attribute = $this->attribute();
        $product = $this->product();
        $product->attributes()->attach($attribute->id, ['value' => '220V']);

        Livewire::test(ListAttributes::class)->call('deleteAttribute', $attribute->id);

        $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
        $this->assertDatabaseHas('product_attribute', [
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'value' => '220V',
        ]);
    }

    public function test_bilingual_rename_refreshes_labels_without_changing_pivot_value(): void
    {
        $attribute = $this->attribute();
        $product = $this->product();
        $product->attributes()->attach($attribute->id, ['value' => '220V']);
        app(ProductLabelService::class)->generateForProduct($product->fresh('attributes'));

        Livewire::test(Form::class)
            ->call('openForm', $attribute->id, 'edit')
            ->set('name_en', 'Operating Voltage')
            ->set('name_ur', 'آپریٹنگ وولٹیج')
            ->call('saveAndClose');

        $product->refresh();
        $this->assertSame('220V', $product->attributes()->firstOrFail()->pivot->value);
        $this->assertTrue(collect($product->label_qr['en']['lines'])->contains(
            fn (array $line): bool => $line['label'] === 'Operating Voltage' && $line['value'] === '220V',
        ));
        $this->assertTrue(collect($product->label_qr['ur']['lines'])->contains(
            fn (array $line): bool => $line['label'] === 'آپریٹنگ وولٹیج' && $line['value'] === '220V',
        ));
    }

    public function test_ai_parser_rejects_non_associative_invalid_empty_and_oversized_values(): void
    {
        $service = app(AttributeEnrichmentService::class);

        $this->assertNull($service->parseResponse('[]'));
        $this->assertNull($service->parseResponse('{}'));
        $this->assertNull($service->parseResponse('{"name_en":["invalid"]}'));
        $this->assertNull($service->parseResponse('{"english_name":"Voltage"}'));
        $this->assertNull($service->parseResponse('{"name_en":"'.str_repeat('x', 129).'"}'));
        $this->assertSame('Voltage', $service->parseResponse('{"name_en":"Voltage"}')['name_en']);
    }

    public function test_empty_ai_attempt_does_not_hold_the_distributed_lock(): void
    {
        Livewire::test(Form::class)
            ->call('openForm')
            ->call('enrichWithAi')
            ->assertSet('statusMessage', __('agricart.attributes.ai.name_required'))
            ->set('name_en', 'Voltage')
            ->call('enrichWithAi')
            ->assertSet('statusMessage', __('agricart.attributes.ai.no_configuration'));
    }

    public function test_inactive_attribute_is_hidden_and_rejected_for_new_assignment(): void
    {
        $inactive = $this->attribute();
        $inactive->update(['status' => 'inactive']);
        $product = $this->product();

        Livewire::test(\App\Livewire\Products\Form::class)
            ->call('openForm')
            ->set('attributeSearches.0', 'Voltage')
            ->assertDontSee('Voltage — وولٹیج')
            ->call('selectAttribute', 0, $inactive->id)
            ->assertSet('attributeRows.0.attribute_id', null);

        try {
            app(ProductService::class)->syncAttributes($product, [
                ['attribute_id' => $inactive->id, 'value' => '220V'],
            ]);
            $this->fail('Inactive forged assignment was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('product_attribute', [
                'product_id' => $product->id,
                'attribute_id' => $inactive->id,
            ]);
        }
    }

    public function test_existing_inactive_assignment_remains_editable_and_preserved(): void
    {
        $inactive = $this->attribute();
        $inactive->update(['status' => 'inactive']);
        $product = $this->product();
        $product->attributes()->attach($inactive->id, ['value' => '220V']);

        app(ProductService::class)->syncAttributes($product, [
            ['attribute_id' => $inactive->id, 'value' => '240V'],
        ]);

        $this->assertDatabaseHas('product_attribute', [
            'product_id' => $product->id,
            'attribute_id' => $inactive->id,
            'value' => '240V',
        ]);

        Livewire::test(\App\Livewire\Products\Form::class)
            ->call('openForm', $product->id, 'edit')
            ->assertSet('attributeRows.0.attribute_id', $inactive->id)
            ->assertSet('attributeRows.0.value', '240V')
            ->assertSee('Voltage');
    }

    private function attribute(): CatalogAttribute
    {
        return CatalogAttribute::create([
            'code' => 'ATR-00001',
            'slug' => 'voltage',
            'status' => 'active',
            'name_en' => 'Voltage',
            'name_ur' => 'وولٹیج',
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'slug' => 'attribute-product-'.str()->random(6),
            'code' => 'PRD-'.str()->upper(str()->random(8)),
            'status' => 'active',
            'name_en' => 'Attribute Product',
            'name_ur' => 'خصوصیت پراڈکٹ',
            'label_code' => 'R1-P'.random_int(1, 99999),
            'category_visual_map_code' => 'R1',
            'label_sequence' => random_int(1, 99999),
        ]);
    }
}
