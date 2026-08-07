<?php

namespace Tests\Feature\Console;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WipeLocalCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_wipe_keeps_super_admins_and_clears_business_data(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        $staffRole = Role::query()->create([
            'name' => 'Staff',
            'slug' => 'staff',
            'is_system' => false,
        ]);

        $admin = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'keep-admin@example.com',
            'status' => User::STATUS_ACTIVE,
        ]);

        User::factory()->create([
            'role_id' => $staffRole->id,
            'email' => 'remove-me@example.com',
            'status' => User::STATUS_ACTIVE,
        ]);

        Category::query()->create([
            'slug' => 'wipe-cat',
            'code' => 'CAT-WIPE',
            'status' => 'active',
            'name_en' => 'Wipe Category',
            'name_ur' => 'وائپ کیٹگری',
        ]);

        Product::query()->create([
            'slug' => 'wipe-product',
            'sku' => 'SKU-WIPE',
            'code' => 'PRD-WIPE',
            'status' => 'active',
            'name_en' => 'Wipe Product',
            'name_ur' => 'وائپ پروڈکٹ',
            'stock_qty' => 1,
        ]);

        Supplier::query()->create([
            'supplier_code' => 'SUP-WIPE',
            'status' => Supplier::STATUS_ACTIVE,
            'source' => Supplier::SOURCE_ADMIN,
            'business_name_en' => 'Wipe Supplier',
            'country_code' => 'PK',
            'city_en' => 'Lahore',
            'address_en' => 'Test',
            'contacts' => [['contact_name_en' => 'A', 'contact_name_ur' => '', 'mobile_number' => '03001112233']],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('categories/sample.webp', 'img');
        $publicRoot = storage_path('app/public');
        File::ensureDirectoryExists($publicRoot.'/categories');
        File::put($publicRoot.'/categories/sample.webp', 'img');

        $exit = Artisan::call('agricart:wipe-local', [
            '--keep-super-admins' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(0, Product::query()->count());
        $this->assertSame(0, Category::query()->count());
        $this->assertSame(0, Supplier::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertSame('keep-admin@example.com', User::query()->value('email'));
        $this->assertTrue(
            Role::query()->where('slug', 'super-admin')->whereKey(User::query()->value('role_id'))->exists(),
        );
        $this->assertSame($admin->email, 'keep-admin@example.com');
        $this->assertFalse(is_file($publicRoot.'/categories/sample.webp'));
    }

    public function test_wipe_is_blocked_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $exit = Artisan::call('agricart:wipe-local', [
            '--keep-super-admins' => true,
            '--force' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('blocked', Artisan::output());
    }
}
