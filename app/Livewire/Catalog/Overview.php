<?php

namespace App\Livewire\Catalog;

use App\Services\Catalog\CatalogOverviewService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Overview extends Component
{
    /** @var list<array{key: string, label: string, count: int, active: int|null, url: string}> */
    public array $stats = [];

    public int $totalRecords = 0;

    public function mount(CatalogOverviewService $overview): void
    {
        $this->stats = $overview->statCards();
        $this->totalRecords = $overview->totalRecords();
    }

    public function render(): View
    {
        return view('livewire.catalog.overview');
    }
}
