<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\Access\UserService;
use App\Services\Reports\ReportCatalog;
use App\Services\Sales\SaleReturnService;
use App\Services\Sales\SaleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseBBatch4RemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_report_catalog_excludes_custom_period_filter(): void
    {
        $periodValues = array_column(ReportCatalog::commonFilterGroups()[0]['filters'], 'value');

        $this->assertNotContains('period:custom', $periodValues);
        $this->assertSame(
            ['period:today', 'period:week', 'period:month', 'period:quarter', 'period:year'],
            $periodValues,
        );
    }

    public function test_sale_service_requires_bank_account_for_bank_transfer(): void
    {
        $product = $this->makeProduct();

        $this->expectException(ValidationException::class);

        app(SaleService::class)->complete([
            'sale_date' => today()->toDateString(),
            'locale' => 'en',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'price_display' => 'plain',
            'amount_paid' => 100,
            'rows' => [[
                'product_id' => $product->id,
                'product_name_en' => $product->name_en,
                'quantity' => '1',
                'unit_price' => '100',
            ]],
        ]);
    }

    public function test_sale_return_service_requires_bank_account_for_bank_transfer(): void
    {
        [$sale, $item] = $this->completedSaleWithItem();

        $this->expectException(ValidationException::class);

        app(SaleReturnService::class)->create([
            'sale_id' => $sale->id,
            'return_date' => today()->toDateString(),
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'amount_paid' => 50,
            'rows' => [[
                'sale_item_id' => $item->id,
                'return_quantity' => 1,
            ]],
        ]);
    }

    public function test_user_archive_soft_deletes_and_frees_email(): void
    {
        $this->makeSuperAdmin('owner-batch4@test.test');
        $manager = $this->userWithManagePermission('manager-batch4@test.test');
        $target = User::factory()->create([
            'email' => 'archive-me@test.test',
            'status' => User::STATUS_ACTIVE,
            'role_id' => $this->nonSuperAdminRoleId(),
        ]);

        app(UserService::class)->archive($target, $manager);

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'status' => User::STATUS_INACTIVE,
        ]);
        $this->assertDatabaseMissing('users', [
            'id' => $target->id,
            'email' => 'archive-me@test.test',
            'deleted_at' => null,
        ]);

        $this->assertNull(User::query()->where('email', 'archive-me@test.test')->first());

        app(UserService::class)->registerPublic($this->minimalRegistrationPayload('archive-me@test.test'));

        $this->assertDatabaseHas('users', [
            'email' => 'archive-me@test.test',
            'status' => User::STATUS_PENDING,
            'deleted_at' => null,
        ]);
    }

    public function test_user_archive_requires_manage_permission(): void
    {
        $target = User::factory()->create([
            'email' => 'protected@test.test',
            'status' => User::STATUS_ACTIVE,
            'role_id' => $this->nonSuperAdminRoleId(),
        ]);
        $viewer = User::factory()->create([
            'email' => 'viewer@test.test',
            'status' => User::STATUS_ACTIVE,
            'role_id' => $this->nonSuperAdminRoleId(),
        ]);

        $this->expectException(ValidationException::class);

        app(UserService::class)->archive($target, $viewer);
    }

    public function test_user_archive_forbids_system_owner(): void
    {
        $owner = $this->makeSuperAdmin('owner-only@test.test');
        $manager = $this->userWithManagePermission('mgr-owner@test.test');

        $this->expectException(ValidationException::class);

        app(UserService::class)->archive($owner, $manager);
    }

    /**
     * @return array{Sale, SaleItem}
     */
    private function completedSaleWithItem(): array
    {
        $product = $this->makeProduct();
        $sale = Sale::query()->create([
            'sale_number' => 'SALE-B4-RET',
            'sale_date' => today(),
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 50,
            'total_amount' => 50,
            'amount_paid' => 50,
            'status' => Sale::STATUS_COMPLETED,
        ]);
        $item = SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name_en' => $product->name_en,
            'quantity' => '1',
            'unit_price' => '50',
            'line_total' => '50',
        ]);

        return [$sale, $item];
    }

    private function makeProduct(): Product
    {
        $category = Category::query()->create([
            'slug' => 'batch4',
            'status' => 'active',
            'name_en' => 'Batch 4',
            'name_ur' => 'Batch 4',
        ]);

        return Product::query()->create([
            'code' => 'B4-1',
            'slug' => 'b4-1',
            'status' => 'active',
            'name_en' => 'Batch product',
            'name_ur' => 'Batch product',
            'category_id' => $category->id,
            'sku' => 'B4-1',
            'stock_qty' => 10,
            'retail_price' => 100,
        ]);
    }

    private function makeSuperAdmin(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
            'role_id' => (int) Role::query()->where('slug', 'super-admin')->value('id'),
        ]);
    }

    private function nonSuperAdminRoleId(): int
    {
        $role = Role::query()->where('slug', '!=', 'super-admin')->orderBy('id')->first()
            ?? Role::query()->create([
                'name' => 'Staff',
                'slug' => 'staff-batch4',
                'is_system' => false,
            ]);

        return (int) $role->id;
    }

    private function userWithManagePermission(string $email): User
    {
        $role = Role::query()->create([
            'name' => 'User managers',
            'slug' => 'user-managers-batch4',
        ]);
        $permissionId = Permission::query()
            ->where('key', 'settings.users.edit')
            ->value('id');
        if ($permissionId === null) {
            $permissionId = Permission::query()->create([
                'key' => 'settings.users.edit',
                'group_key' => 'settings',
                'label_en' => 'Edit users',
                'label_ur' => 'یوزرز ترمیم',
                'sort_order' => 1,
            ])->id;
            Permission::query()->firstOrCreate(
                ['key' => 'settings.users.view'],
                [
                    'group_key' => 'settings',
                    'label_en' => 'View users',
                    'label_ur' => 'یوزرز دیکھیں',
                    'sort_order' => 0,
                ],
            );
            Permission::query()->firstOrCreate(
                ['key' => 'settings.users.create'],
                [
                    'group_key' => 'settings',
                    'label_en' => 'Create users',
                    'label_ur' => 'یوزرز بنائیں',
                    'sort_order' => 2,
                ],
            );
        }
        $ids = Permission::query()
            ->whereIn('key', [
                'settings.users.view',
                'settings.users.create',
                'settings.users.edit',
                'settings.users.delete',
            ])
            ->pluck('id')
            ->all();
        $role->permissions()->sync($ids);

        return User::factory()->create([
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
            'role_id' => $role->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalRegistrationPayload(string $email): array
    {
        return [
            'name' => 'New User',
            'name_ur' => 'New User',
            'email' => $email,
            'address' => 'Test address',
            'address_ur' => 'Test address',
            'phone_numbers' => [[
                'contact_person' => 'Self',
                'contact_person_ur' => 'Self',
                'phone_number' => '03001234567',
            ]],
            'bank_accounts' => [[
                'bank_name' => 'National Bank of Pakistan (NBP)',
                'account_title' => 'Self',
                'account_title_ur' => 'Self',
                'account_number' => 'PK00NBPA0000000000000000',
            ]],
            'profile_photo_path' => 'staff/profile/test.jpg',
            'cnic_front_path' => 'staff/cnic/front.jpg',
            'cnic_back_path' => 'staff/cnic/back.jpg',
            'password' => 'password123',
        ];
    }
}
