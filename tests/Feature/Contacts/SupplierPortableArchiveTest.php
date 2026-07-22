<?php

namespace Tests\Feature\Contacts;

use App\Models\Supplier;
use App\Services\Contacts\SupplierPortableArchiveService;
use App\Services\PortableArchive\PortableArchiveCodec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierPortableArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_import_round_trip_restores_supplier_fields_and_media(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('suppliers/profile-photos/sample.webp', 'profile-bytes');
        Storage::disk('public')->put('suppliers/cards/front.webp', 'front-bytes');
        Storage::disk('public')->put('suppliers/cards/back.webp', 'back-bytes');

        Supplier::query()->create([
            'supplier_code' => 'SUP-00099',
            'supplier_type' => null,
            'status' => 'active',
            'business_name_en' => 'Green Parts Co',
            'business_name_ur' => 'گرین پارٹس',
            'country_code' => 'PK',
            'city_key' => 'lahore',
            'city_en' => 'Lahore',
            'city_ur' => 'لاہور',
            'address_en' => 'Shop 1, Test Road',
            'address_ur' => 'دکان 1',
            'credit_limit' => 1000,
            'opening_balance' => 50,
            'contacts' => [
                [
                    'contact_name_en' => 'Ali',
                    'contact_name_ur' => 'علی',
                    'mobile_number' => '03001234567',
                ],
            ],
            'bank_accounts' => [
                [
                    'bank_key' => 'hbl',
                    'bank_name_en' => 'HBL',
                    'bank_name_ur' => 'ایچ بی ایل',
                    'account_title' => 'Green Parts',
                    'account_title_ur' => '',
                    'account_number' => 'PK00HBL0000001',
                ],
            ],
            'portal_enabled' => false,
            'email' => null,
            'profile_photo_path' => 'suppliers/profile-photos/sample.webp',
            'card_front_path' => 'suppliers/cards/front.webp',
            'card_back_path' => 'suppliers/cards/back.webp',
            'ai_status' => 'none',
        ]);

        $service = app(SupplierPortableArchiveService::class);
        $export = $service->exportAll();

        $this->assertFileExists($export['path']);
        $this->assertSame(1, $export['count']);
        $this->assertStringEndsWith('.ags', $export['filename']);
        $this->assertStringStartsWith(
            PortableArchiveCodec::MAGIC,
            (string) file_get_contents($export['path']),
        );

        Supplier::query()->where('supplier_code', 'SUP-00099')->delete();
        $this->assertDatabaseMissing('suppliers', ['supplier_code' => 'SUP-00099']);

        $result = $service->importFromPath($export['path']);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertGreaterThanOrEqual(3, $result['media']);

        $restored = Supplier::query()->where('supplier_code', 'SUP-00099')->first();
        $this->assertNotNull($restored);
        $this->assertSame('Green Parts Co', $restored->business_name_en);
        $this->assertSame('03001234567', $restored->primaryMobile());
        $this->assertSame('PK00HBL0000001', $restored->bank_accounts[0]['account_number'] ?? null);
        $this->assertTrue(Storage::disk('public')->exists((string) $restored->profile_photo_path));
        $this->assertTrue(Storage::disk('public')->exists((string) $restored->card_front_path));
        $this->assertTrue(Storage::disk('public')->exists((string) $restored->card_back_path));
        $this->assertSame('profile-bytes', Storage::disk('public')->get((string) $restored->profile_photo_path));

        @unlink($export['path']);
    }

    public function test_import_updates_existing_supplier_by_code(): void
    {
        Storage::fake('public');

        Supplier::query()->create([
            'supplier_code' => 'SUP-00077',
            'status' => 'active',
            'business_name_en' => 'Old Name',
            'country_code' => 'PK',
            'city_key' => 'lahore',
            'city_en' => 'Lahore',
            'address_en' => 'Old address',
            'contacts' => [
                ['contact_name_en' => 'Old', 'contact_name_ur' => '', 'mobile_number' => '03001111111'],
            ],
            'bank_accounts' => [],
            'portal_enabled' => false,
            'opening_balance' => 0,
            'ai_status' => 'none',
        ]);

        $service = app(SupplierPortableArchiveService::class);
        $export = $service->exportAll();

        Supplier::query()->where('supplier_code', 'SUP-00077')->update([
            'business_name_en' => 'Changed Locally',
        ]);

        // Re-export would pick changed name; instead craft by re-importing original archive
        // after mutating, import should restore archive values.
        $result = $service->importFromPath($export['path']);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('Old Name', Supplier::query()->where('supplier_code', 'SUP-00077')->value('business_name_en'));

        @unlink($export['path']);
    }
}
