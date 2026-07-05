<?php

namespace App\Livewire\AiLogs;

use App\Enums\AiLogStatus;
use App\Enums\AiLogType;
use App\Models\AiLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    #[On('ai-logs-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('ai-logs-filter')]
    public function applyFilter(string $field, string $value): void
    {
        if ($field === 'status') {
            $this->statusFilter = $value;
        }

        if ($field === 'type') {
            $this->typeFilter = $value;
        }

        $this->resetPage();
    }

    public function openLog(int $logId): void
    {
        $log = AiLog::query()->find($logId);

        if ($log === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.ai.logs.detail_title', ['id' => $log->id]),
            resource: $log->type->label(),
            formKey: 'ai-log-detail',
            logId: $logId,
        );
    }

    public function render(): View
    {
        $logs = AiLog::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('prompt', 'like', $needle)
                        ->orWhere('response', 'like', $needle)
                        ->orWhere('error_message', 'like', $needle)
                        ->orWhere('model_id', 'like', $needle)
                        ->orWhere('model_name', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->typeFilter), fn ($query) => $query->where('type', $this->typeFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.ai-logs.list', [
            'logs' => $logs,
            'statuses' => AiLogStatus::cases(),
            'types' => AiLogType::cases(),
        ]);
    }
}
