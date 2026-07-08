<?php

namespace App\Livewire\Approvals;

use App\Services\Approvals\ApprovalsOverviewService;
use App\Services\Approvals\ApprovalsSearchService;
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

    public function mount(ApprovalsOverviewService $overview): void
    {
        $this->stats = $overview->statCards();
    }

    #[On('approvals-overview-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;

        if (! filled(trim($query))) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = app(ApprovalsSearchService::class)->search($query);
    }

    public function render(): View
    {
        return view('livewire.approvals.overview');
    }
}
