<?php

namespace App\Services\Contacts;

use App\Filament\Clusters\Contacts\CustomersPage;
use App\Filament\Clusters\Contacts\SuppliersPage;
use App\Models\Customer;
use App\Models\Supplier;

class ContactsOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        $cards = [
            $this->suppliersCard(),
            $this->customersCard(),
        ];

        $visible = array_filter($cards, function (array $card): bool {
            $pageClass = $card['page'];

            return $pageClass::canAccess();
        });

        return array_values(array_map(function (array $card): array {
            unset($card['page']);

            return $card;
        }, $visible));
    }

    public function totalRecords(): int
    {
        return array_sum(array_column($this->statCards(), 'count'));
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function suppliersCard(): array
    {
        $query = Supplier::query();
        $count = (clone $query)->count();
        $active = (clone $query)->where('status', 'active')->count();

        return $this->card('suppliers', $count, $active, SuppliersPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function customersCard(): array
    {
        $query = Customer::query();
        $count = (clone $query)->count();
        $active = (clone $query)->where('is_active', true)->count();

        return $this->card('customers', $count, $active, CustomersPage::class);
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function card(string $key, int $count, ?int $active, string $pageClass): array
    {
        return [
            'key' => $key,
            'label' => (string) __('agricart.contacts.overview.cards.' . $key),
            'count' => $count,
            'active' => $active,
            'url' => $pageClass::getUrl(),
            'page' => $pageClass,
        ];
    }
}
