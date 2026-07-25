<?php

namespace Tests\Feature;

use App\Filament\Layout\SettingsPageToolbar;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserService;
use App\Services\Catalog\ProductService;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseCBatch2RemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_service_update_deletes_replaced_cnic_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $role = Role::query()->firstOrFail();
        $actor = User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]);

        $oldPath = 'users/cnic/old-front.jpg';
        Storage::disk('local')->put($oldPath, 'old');
        $newPath = 'users/cnic/new-front.jpg';
        Storage::disk('local')->put($newPath, 'new');

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'cnic_front_path' => $oldPath,
        ]);

        $this->actingAs($actor);

        app(UserService::class)->update($user, [
            'name' => $user->name,
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'email' => $user->email,
            'cnic_front_path' => $newPath,
        ]);

        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }

    public function test_user_service_rejects_unknown_bank_name_on_admin_validate(): void
    {
        $role = Role::query()->firstOrFail();
        $actor = User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]);
        $this->actingAs($actor);

        $this->expectException(ValidationException::class);

        app(UserService::class)->create([
            'name' => 'Bank Test',
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'email' => 'bank-test@example.test',
            'password' => 'ValidPass1!',
            'bank_accounts' => [[
                'bank_name' => 'Not A Real Pakistani Bank',
                'account_title' => 'Test',
                'account_number' => '123',
            ]],
        ]);
    }

    public function test_product_service_rejects_non_numeric_retail_price(): void
    {
        $product = $this->makeSaleProduct();

        $this->expectException(ValidationException::class);

        app(ProductService::class)->update($product, [
            'name_en' => $product->name_en,
            'name_ur' => $product->name_ur,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'unit_id' => $product->unit_id,
            'pack_unit_id' => $product->pack_unit_id,
            'unit_qty' => 1,
            'retail_price' => 'not-a-price',
        ]);
    }

    public function test_sale_service_overrides_submitted_payment_status_from_amount_paid(): void
    {
        $product = $this->makeSaleProduct();

        $sale = app(SaleService::class)->complete([
            'sale_date' => today()->toDateString(),
            'locale' => 'en',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'price_display' => 'plain',
            'amount_paid' => 100,
            'rows' => [[
                'product_id' => $product->id,
                'product_name_en' => $product->name_en,
                'quantity' => '1',
                'unit_price' => '100',
            ]],
        ]);

        $this->assertSame('paid', $sale->payment_status);
    }

    public function test_payment_proof_reupload_deletes_previous_receipt_file(): void
    {
        Storage::fake('local');

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.random_int(10000, 99999),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Pay',
            'last_name' => 'Proof',
            'email' => 'proof-'.random_int(1000, 9999).'@test.test',
            'phone' => '03001234567',
            'password' => 'password',
            'address_line1' => '1 Test',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-'.random_int(10000, 99999),
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_AWAITING,
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 0,
        ]);

        $oldPath = 'online-orders/customer-receipts/'.$order->id.'/old.pdf';
        Storage::disk('local')->put($oldPath, 'old receipt');
        $order->update(['customer_bank_receipt_path' => $oldPath]);

        $upload = UploadedFile::fake()->create('new.pdf', 100, 'application/pdf');

        app(OnlineSaleOrderService::class)->submitCustomerPaymentProof($order, $customer, [
            'customer_payment_reference' => 'REF-123',
            'bank_receipt' => $upload,
        ]);

        Storage::disk('local')->assertMissing($oldPath);
        $order->refresh();
        $this->assertNotSame($oldPath, $order->customer_bank_receipt_path);
        $this->assertTrue(Storage::disk('local')->exists((string) $order->customer_bank_receipt_path));
    }

    public function test_settings_users_toolbar_includes_pending_and_rejected_filters(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin);

        $this->get('/admin/settings/users');

        $config = SettingsPageToolbar::config();

        $this->assertNotNull($config);
        $values = array_column($config['filters'] ?? [], 'value');

        $this->assertContains('status:pending', $values);
        $this->assertContains('status:rejected', $values);
    }

    public function test_user_service_rejects_password_shorter_than_default_rule(): void
    {
        $role = Role::query()->firstOrFail();
        $actor = User::factory()->create(['role_id' => $role->id, 'status' => User::STATUS_ACTIVE]);
        $this->actingAs($actor);

        $this->expectException(ValidationException::class);

        app(UserService::class)->create([
            'name' => 'Short Pass',
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'email' => 'short-pass@example.test',
            'password' => 'short',
        ]);
    }

    private function makeSaleProduct(): Product
    {
        $category = Category::query()->create([
            'slug' => 'batch2-cat',
            'status' => 'active',
            'name_en' => 'Batch 2',
            'name_ur' => 'Batch 2',
        ]);

        return Product::query()->create([
            'code' => 'B2-1',
            'slug' => 'b2-1',
            'status' => 'active',
            'name_en' => 'Batch 2 product',
            'name_ur' => 'Batch 2 product',
            'category_id' => $category->id,
            'sku' => 'B2-1',
            'stock_qty' => 10,
            'retail_price' => 100,
        ]);
    }
}
