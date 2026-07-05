<?php

namespace App\Livewire\AiLogs;

use App\Models\AiLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Detail extends Component
{
    public ?AiLog $log = null;

    #[On('ai-log-detail-open')]
    public function loadLog(int $logId): void
    {
        $this->log = AiLog::query()->with(['configuration', 'user'])->find($logId);
    }

    public function render(): View
    {
        return view('livewire.ai-logs.detail');
    }
}
