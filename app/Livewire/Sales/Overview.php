<?php

namespace App\Livewire\Sales;

use App\Services\Sales\SalesOverviewService;
use App\Services\Sales\SalesSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Overview extends Component
{
    /** @var list<array{key: string, label: string, count: int, active: int|null, url: string}> */
    public array $stats = [];

    public string $search = '';

    /** @var array<string, list<array{label: string, meta: string|null, url: string}>> */
    public array $searchResults = [];

    public function mount(SalesOverviewService $overview): void
    {
        $this->stats = $overview->statCards();
    }

    #[On('sales-overview-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;

        if (! filled(trim($query))) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = app(SalesSearchService::class)->search($query);
    }

    public function render(): View
    {
        return view('livewire.sales.overview');
    }
}
