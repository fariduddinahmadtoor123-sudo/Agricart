<?php

namespace App\Livewire\ActivityLogs;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Detail extends Component
{
    use AuthorizesSensitiveActions;

    public ?ActivityLog $log = null;

    #[On('activity-log-detail-open')]
    public function loadLog(int $logId): void
    {
        $this->authorizeAny('settings.activity.view');
        $this->log = ActivityLog::query()->with('user')->find($logId);
    }

    public function render(): View
    {
        return view('livewire.activity-logs.detail');
    }
}
