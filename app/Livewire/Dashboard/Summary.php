<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Summary extends Component
{
    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $generatedAt = '';

    public function mount(DashboardService $dashboard): void
    {
        $this->rows = $dashboard->summaryRows();
        $this->generatedAt = now()->format('d M Y, h:i A');
    }

    public function render(): View
    {
        return view('livewire.dashboard.summary');
    }
}
