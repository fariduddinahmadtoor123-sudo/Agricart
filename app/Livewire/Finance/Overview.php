<?php

namespace App\Livewire\Finance;

use App\Services\Finance\FinanceOverviewService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Overview extends Component
{
    /** @var list<array{key: string, label: string, count: int|string, active: int|null, url: string, meta: string|null}> */
    public array $stats = [];

    public function mount(FinanceOverviewService $overview): void
    {
        $this->stats = $overview->statCards();
    }

    public function render(): View
    {
        return view('livewire.finance.overview');
    }
}
