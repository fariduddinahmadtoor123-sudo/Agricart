<?php

namespace App\Services\Contacts;

use App\Filament\Clusters\Contacts\CustomersPage;
use App\Filament\Clusters\Contacts\SuppliersPage;
use App\Models\Customer;
use App\Models\Supplier;
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
            ->map(fn (Supplier $supplier): array => [
                'label' => $supplier->displayBusinessName() ?: (string) __('agricart.contacts.overview.search_untitled'),
                'meta' => filled($supplier->supplier_code) ? (string) $supplier->supplier_code : null,
                'url' => $url,
            ])
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
            ->map(fn (Customer $customer): array => [
                'label' => $customer->displayLabel() ?: (string) __('agricart.contacts.overview.search_untitled'),
                'meta' => filled($customer->customer_number) ? (string) $customer->customer_number : $customer->phone,
                'url' => $url,
            ])
            ->all();
    }
}
