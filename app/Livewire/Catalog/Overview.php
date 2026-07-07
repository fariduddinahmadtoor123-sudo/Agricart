<?php

namespace App\Livewire\Catalog;

use App\Services\Catalog\CatalogOverviewService;
use App\Services\Catalog\CatalogSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Overview extends Component
{
    /** @var list<array{key: string, label: string, count: int, active: int|null, url: string}> */
    public array $stats = [];

    public int $totalRecords = 0;

    public string $search = '';

    /** @var array<string, list<array{label: string, meta: string|null, url: string}>> */
    public array $searchResults = [];

    public function mount(CatalogOverviewService $overview): void
    {
        $this->stats = $overview->statCards();
        $this->totalRecords = $overview->totalRecords();
    }

    #[On('catalog-overview-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;

        if (! filled(trim($query))) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = app(CatalogSearchService::class)->search($query);
    }

    public function render(): View
    {
        return view('livewire.catalog.overview');
    }
}
