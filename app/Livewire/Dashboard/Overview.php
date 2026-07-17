<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Overview extends Component
{
    /** @var list<array<string, mixed>> */
    public array $cards = [];

    /** @var list<array<string, mixed>> */
    public array $attention = [];

    public function mount(DashboardService $dashboard): void
    {
        $this->cards = $dashboard->overviewCards();
        $this->attention = $dashboard->attentionItems();
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview');
    }
}
