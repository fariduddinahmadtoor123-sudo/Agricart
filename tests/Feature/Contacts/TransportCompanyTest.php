<?php

namespace Tests\Feature\Contacts;

use App\Models\TransportCompany;
use App\Services\Contacts\TransportCompanyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransportCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_creates_company_with_auto_code_routes_contacts_and_banks(): void
    {
        $service = app(TransportCompanyService::class);

        $company = $service->create([
            'transport_type' => 'goods_company',
            'status' => 'active',
            'business_name_en' => 'Lahore Goods Adda',
            'business_name_ur' => 'لاہور گڈز اڈہ',
            'country_code' => 'PK',
            'city_key' => 'lahore',
            'routes' => [
                ['from_key' => 'lahore', 'to_key' => 'multan'],
                ['from_key' => 'lahore', 'to_key' => 'karachi'],
            ],
            'branches' => [
                [
                    'address_en' => 'Nageena Chowk, Pakpattan',
                    'address_ur' => 'نگینہ چوک، پاک پتن',
                    'contacts' => [
                        ['contact_name_en' => 'Imran', 'contact_name_ur' => 'عمران', 'mobile_number' => '03001112233'],
                        ['contact_name_en' => 'Asif', 'contact_name_ur' => 'آصف', 'mobile_number' => '03004445566'],
                    ],
                ],
            ],
            'contacts' => [
                ['contact_name_en' => 'Ali', 'contact_name_ur' => 'علی', 'mobile_number' => '03001234567'],
            ],
            'bank_accounts' => [
                ['bank_key' => '', 'account_title' => 'Lahore Goods', 'account_number' => 'PK00TEST0001'],
            ],
            'opening_balance' => 500,
        ]);

        $this->assertStringStartsWith('TRP-', (string) $company->transport_code);
        $this->assertSame('lahore', $company->city_key);
        $this->assertSame('Lahore', $company->city_en);
        $this->assertCount(2, $company->routes);
        $this->assertSame('Lahore', $company->routes[0]['from_en']);
        $this->assertSame('Multan', $company->routes[0]['to_en']);
        $this->assertSame('Lahore → Multan, Lahore → Karachi', $company->routesSummary());
        $this->assertSame('Ali', $company->primaryContactName());
        $this->assertCount(1, $company->branches);
        $this->assertCount(2, $company->branches[0]['contacts']);
        $this->assertSame('Nageena Chowk, Pakpattan', $company->branches[0]['address_en']);
        $this->assertCount(1, $company->bank_accounts);
        $this->assertSame('goods_company', $company->transport_type);
    }

    public function test_destination_business_and_branches_are_saved(): void
    {
        $company = app(TransportCompanyService::class)->create([
            'status' => 'active',
            'business_name_en' => 'Muzammil Cargo',
            'country_code' => 'PK',
            'city_key' => 'lahore',
            'destination_business_name_en' => 'Pakpattan Delivery Office',
            'destination_business_name_ur' => 'پاک پتن ڈلیوری آفس',
            'destination_country_code' => 'PK',
            'destination_city_key' => 'pakpattan',
            'destination_address_en' => 'Main Adda Road',
            'destination_branches' => [
                [
                    'address_en' => 'Nageena Chowk',
                    'address_ur' => 'نگینہ چوک',
                    'contacts' => [
                        ['contact_name_en' => 'Imran', 'contact_name_ur' => 'عمران', 'mobile_number' => '03001112233'],
                    ],
                ],
            ],
        ]);

        $this->assertSame('Pakpattan Delivery Office', $company->destination_business_name_en);
        $this->assertSame('pakpattan', $company->destination_city_key);
        $this->assertSame('Pakpattan', $company->destination_city_en);
        $this->assertCount(1, $company->destination_branches);
        $this->assertSame('Nageena Chowk', $company->destination_branches[0]['address_en']);
    }

    public function test_blank_branches_are_dropped(): void
    {
        $company = app(TransportCompanyService::class)->create([
            'status' => 'active',
            'business_name_en' => 'Branch Filter Co',
            'country_code' => 'PK',
            'branches' => [
                ['address_en' => '', 'address_ur' => '', 'contacts' => []],
                [
                    'address_en' => 'Fawara Chowk',
                    'address_ur' => '',
                    'contacts' => [
                        ['contact_name_en' => 'Bilal', 'contact_name_ur' => '', 'mobile_number' => '03007778899'],
                    ],
                ],
            ],
        ]);

        $this->assertCount(1, $company->branches);
        $this->assertSame('Fawara Chowk', $company->branches[0]['address_en']);
    }

    public function test_codes_are_sequential(): void
    {
        $service = app(TransportCompanyService::class);

        $first = $service->create(['status' => 'active', 'business_name_en' => 'A', 'country_code' => 'PK']);
        $second = $service->create(['status' => 'active', 'business_name_en' => 'B', 'country_code' => 'PK']);

        $this->assertNotSame($first->transport_code, $second->transport_code);
    }

    public function test_business_name_is_required(): void
    {
        $this->expectException(ValidationException::class);

        app(TransportCompanyService::class)->create([
            'status' => 'active',
            'country_code' => 'PK',
        ]);
    }

    public function test_blank_routes_and_contacts_are_dropped(): void
    {
        $company = app(TransportCompanyService::class)->create([
            'status' => 'active',
            'business_name_en' => 'Empty Rows Co',
            'country_code' => 'PK',
            'routes' => [
                ['from_key' => '', 'to_key' => ''],
                ['from_key' => 'lahore', 'to_key' => 'peshawar'],
            ],
            'contacts' => [
                ['contact_name_en' => '', 'contact_name_ur' => '', 'mobile_number' => ''],
            ],
        ]);

        $this->assertCount(1, $company->routes);
        $this->assertSame([], $company->contacts);
    }

    public function test_update_changes_fields(): void
    {
        $service = app(TransportCompanyService::class);
        $company = $service->create(['status' => 'active', 'business_name_en' => 'Old', 'country_code' => 'PK']);

        $updated = $service->update($company, [
            'status' => 'inactive',
            'business_name_en' => 'New Name',
            'country_code' => 'PK',
            'transport_type' => 'courier',
        ]);

        $this->assertSame('New Name', $updated->business_name_en);
        $this->assertSame('inactive', $updated->status);
        $this->assertSame('courier', $updated->transport_type);
        $this->assertDatabaseHas('transport_companies', ['id' => $company->id, 'business_name_en' => 'New Name']);
    }
}
