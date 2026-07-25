<?php

namespace Tests\Feature\Contacts;

use App\Models\Customer;
use App\Services\Contacts\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPhoneSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_phone_uses_canonical_indexed_row(): void
    {
        $service = app(CustomerService::class);

        $customer = $service->createOffline([
            'display_name' => 'Phone Index Customer',
            'phoneRows' => [['mobile_number' => '0300-1112233']],
        ]);

        $found = $service->findByPhone('+92 300 1112233');

        $this->assertNotNull($found);
        $this->assertSame($customer->id, $found->id);
    }

    public function test_search_customers_matches_canonical_phone_prefix_without_leading_wildcard_on_legacy_phone(): void
    {
        $service = app(CustomerService::class);

        $service->createOffline([
            'display_name' => 'Prefix Search Customer',
            'phoneRows' => [['mobile_number' => '0300-5556677']],
        ]);

        $results = $service->searchCustomers('300555');

        $this->assertNotEmpty($results);
        $this->assertSame('Prefix Search Customer', Customer::query()->find($results[0]['id'])?->display_name);
    }
}
