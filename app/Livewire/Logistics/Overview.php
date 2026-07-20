<?php

namespace App\Livewire\Logistics;

use App\Services\Logistics\LogisticsOverviewService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Overview extends Component
{
    /** @var list<array{key: string, label: string, count: int, active: int|null, url: string}> */
    public array $stats = [];

    public function mount(LogisticsOverviewService $overview): void
    {
        $this->stats = $overview->statCards();
    }

    public function render(): View
    {
        return view('livewire.logistics.overview');
    }
}
