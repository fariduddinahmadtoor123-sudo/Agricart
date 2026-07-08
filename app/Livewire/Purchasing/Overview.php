<?php

namespace App\Livewire\Purchasing;

use App\Services\Purchasing\PurchasingOverviewService;
use App\Services\Purchasing\PurchasingSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Overview extends Component
{
    /** @var list<array{key: string, label: string, count: int, active: int|null, url: string}> */
    public array $stats = [];

    /** @var list<array{key: string, label: string, count: int, hint: string|null, url: string}> */
    public array $insights = [];

    public string $search = '';

    /** @var array<string, list<array{label: string, meta: string|null, url: string}>> */
    public array $searchResults = [];

    public function mount(PurchasingOverviewService $overview): void
    {
        $this->stats = $overview->statCards();
        $this->insights = $overview->labelingInsights();
    }

    #[On('purchasing-overview-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;

        if (! filled(trim($query))) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = app(PurchasingSearchService::class)->search($query);
    }

    public function render(): View
    {
        return view('livewire.purchasing.overview');
    }
}
