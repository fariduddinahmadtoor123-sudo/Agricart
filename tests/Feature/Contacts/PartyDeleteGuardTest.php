<?php

namespace Tests\Feature\Contacts;

use App\Models\Customer;
use App\Models\FundTransfer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\CustomerService;
use App\Services\Contacts\SupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartyDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_with_sale_cannot_be_deleted(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-00001',
            'customer_channel' => Customer::CHANNEL_OFFLINE,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'display_name' => 'Test Customer',
            'email' => 'offline.cus-00001@contacts.agricart.local',
            'phone' => '3001234567',
            'password' => bcrypt('secret'),
            'address_line1' => 'Shop',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        Sale::query()->create([
            'sale_number' => 'SAL-00001',
            'customer_id' => $customer->id,
            'status' => Sale::STATUS_COMPLETED,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'sale_date' => now()->toDateString(),
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
        ]);

        $this->expectException(ValidationException::class);
        app(CustomerService::class)->delete($customer);
    }

    public function test_customer_with_fund_receipt_cannot_be_deleted(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-00002',
            'customer_channel' => Customer::CHANNEL_OFFLINE,
            'first_name' => 'Receipt',
            'last_name' => 'Customer',
            'display_name' => 'Receipt Customer',
            'email' => 'offline.cus-00002@contacts.agricart.local',
            'phone' => '3001234568',
            'password' => bcrypt('secret'),
            'address_line1' => 'Shop',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        FundTransfer::query()->create([
            'transfer_number' => 'FT-00001',
            'transaction_type' => FundTransfer::TYPE_CUSTOMER_RECEIPT,
            'source_wallet' => FundTransfer::WALLET_CASH,
            'destination_customer_id' => $customer->id,
            'amount' => 250,
            'transaction_date' => now()->toDateString(),
            'transaction_time' => now()->format('H:i:s'),
        ]);

        $this->expectException(ValidationException::class);
        app(CustomerService::class)->delete($customer);
    }

    public function test_supplier_with_purchase_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-00001',
            'status' => 'active',
            'business_name_en' => 'Vendor',
            'country_code' => 'PK',
            'city_key' => 'lahore',
            'city_en' => 'Lahore',
            'city_ur' => 'لاہور',
            'address_en' => 'Address',
            'contacts' => [['contact_name_en' => 'Ali', 'contact_name_ur' => '', 'mobile_number' => '3001112233']],
            'opening_balance' => 0,
            'portal_enabled' => false,
            'ai_status' => 'none',
        ]);

        Purchase::query()->create([
            'purchase_number' => 'PUR-00001',
            'supplier_id' => $supplier->id,
            'purchaser_id' => $user->id,
            'created_by' => $user->id,
            'purchase_date' => now()->toDateString(),
            'delivery_status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 50,
            'total_amount' => 50,
            'amount_paid' => 0,
        ]);

        $this->expectException(ValidationException::class);
        app(SupplierService::class)->delete($supplier);
    }
}
