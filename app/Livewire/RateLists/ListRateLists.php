<?php

namespace App\Livewire\RateLists;

use App\Models\User;
use App\Services\Purchasing\ShopRateListService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListRateLists extends Component
{
    use WithPagination;

    public string $search = '';

    public string $locale = 'both';

    #[On('rate-list-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('rate-list-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'locale' && in_array($value, ShopRateListService::LOCALES, true)) {
            $this->locale = $value;
            $this->resetPage();
        }
    }

    #[On('rate-list-clear-filters')]
    public function clearFilters(): void
    {
        $this->locale = 'both';
        $this->resetPage();
    }

    #[On('rate-list-action')]
    public function handleToolbarAction(string $action): void
    {
        if ($action === 'print') {
            $this->openPrint();
        }
    }

    public function openPrint(): void
    {
        if (! $this->userCan('print')) {
            return;
        }

        $url = route('admin.rate-list.print', array_filter([
            'locale' => $this->locale,
            'search' => filled($this->search) ? $this->search : null,
            'autoprint' => 1,
        ]));

        $this->dispatch('rate-list-open-print', url: $url);
    }

    public function render(): View
    {
        $service = app(ShopRateListService::class);
        $products = $service->paginate(
            filled($this->search) ? $this->search : null,
            $this->locale,
            ShopRateListService::ROWS_PER_PAGE,
        );

        $start = (int) ($products->firstItem() ?? 1);
        $rows = [];

        foreach ($products as $index => $product) {
            $rows[] = $service->rowFromProduct($product, $start + $index, $this->locale);
        }

        return view('livewire.rate-lists.list', [
            'products' => $products,
            'rows' => $rows,
            'bothNames' => $this->locale === 'both',
        ]);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'rate-list', $action);
    }
}
