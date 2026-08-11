<?php

namespace App\Services\Contacts;

use App\Filament\Clusters\Contacts\CustomersPage;
use App\Filament\Clusters\Contacts\SuppliersPage;
use App\Filament\Clusters\Contacts\TransportCompaniesPage;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\TransportCompany;
use App\Support\Search\SearchHit;
use Illuminate\Database\Eloquent\Builder;

class ContactsSearchService
{
    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string}>>
     */
    public function search(string $term, int $limitPerGroup = 8): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%' . $term . '%';

        return array_filter([
            'suppliers' => SuppliersPage::canAccess()
                ? $this->mapSuppliers($needle, $limitPerGroup)
                : [],
            'customers' => CustomersPage::canAccess()
                ? $this->mapCustomers($needle, $limitPerGroup)
                : [],
            'transport_companies' => TransportCompaniesPage::canAccess()
                ? $this->mapTransportCompanies($needle, $limitPerGroup)
                : [],
        ], fn (array $items): bool => $items !== []);
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapSuppliers(string $needle, int $limit): array
    {
        $url = SuppliersPage::getUrl();

        return Supplier::query()
            ->where(function (Builder $query) use ($needle): void {
                $query->where('supplier_code', 'like', $needle)
                    ->orWhere('business_name_en', 'like', $needle)
                    ->orWhere('business_name_ur', 'like', $needle)
                    ->orWhere('city_en', 'like', $needle)
                    ->orWhere('city_ur', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            })
            ->orderBy('business_name_en')
            ->limit($limit)
            ->get()
            ->map(function (Supplier $supplier) use ($url): array {
                $label = $supplier->displayBusinessName() ?: (string) __('agricart.contacts.overview.search_untitled');

                return SearchHit::make(
                    $label,
                    filled($supplier->supplier_code) ? (string) $supplier->supplier_code : null,
                    $url,
                    $supplier->profilePhotoUrl(),
                    [
                        'formKey' => 'suppliers',
                        'supplierId' => $supplier->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.contacts.suppliers'),
                    ],
                );
            })
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapCustomers(string $needle, int $limit): array
    {
        $url = CustomersPage::getUrl();

        return Customer::query()
            ->where(function (Builder $query) use ($needle): void {
                $query->where('customer_number', 'like', $needle)
                    ->orWhere('display_name', 'like', $needle)
                    ->orWhere('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('first_name', 'like', $needle)
                    ->orWhere('last_name', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('city_en', 'like', $needle)
                    ->orWhere('city_ur', 'like', $needle)
                    ->orWhere('city', 'like', $needle);
            })
            ->orderBy('display_name')
            ->orderBy('name_en')
            ->limit($limit)
            ->get()
            ->map(function (Customer $customer) use ($url): array {
                $label = $customer->displayLabel() ?: (string) __('agricart.contacts.overview.search_untitled');

                return SearchHit::make(
                    $label,
                    filled($customer->customer_number) ? (string) $customer->customer_number : $customer->phone,
                    $url,
                    $customer->profilePhotoUrl(),
                    [
                        'formKey' => 'customers',
                        'customerId' => $customer->id,
                        'title' => $label,
                        'resource' => __('agricart.nav.contacts.customers'),
                    ],
                );
            })
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapTransportCompanies(string $needle, int $limit): array
    {
        $url = TransportCompaniesPage::getUrl();

        return TransportCompany::query()
            ->where(function (Builder $query) use ($needle): void {
                $query->where('transport_code', 'like', $needle)
                    ->orWhere('business_name_en', 'like', $needle)
                    ->orWhere('business_name_ur', 'like', $needle)
                    ->orWhere('city_en', 'like', $needle)
                    ->orWhere('city_ur', 'like', $needle)
                    ->orWhere('destination_business_name_en', 'like', $needle)
                    ->orWhere('destination_business_name_ur', 'like', $needle);
            })
            ->orderBy('business_name_en')
            ->limit($limit)
            ->get()
            ->map(function (TransportCompany $company) use ($url): array {
                $label = $company->displayBusinessName() ?: (string) __('agricart.contacts.overview.search_untitled');

                return SearchHit::make(
                    $label,
                    filled($company->transport_code) ? (string) $company->transport_code : null,
                    $url,
                    SearchHit::image($company->profile_photo_path),
                    [
                        'formKey' => 'transport-companies',
                        'transportCompanyId' => $company->id,
                        'title' => $label,
                        'resource' => __('transport_companies.page_title'),
                    ],
                );
            })
            ->all();
    }
}
