<?php

namespace App\Livewire\AiLogs;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\AiLog;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListLogs extends Component
{
    use AuthorizesSensitiveActions, WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'id',
        'type',
        'status',
        'model',
        'duration_ms',
        'created_at',
    ];

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public function mount(): void
    {
        if (! $this->canAccessAny('settings.ai.logs.view')) {
            return;
        }

        $this->syncColumnCheckboxes();
    }

    #[On('ai-logs-search')]
    public function updateSearch(string $query): void
    {
        $this->authorizeAny('settings.ai.logs.view');
        $this->search = $query;
        $this->resetPage();
    }

    #[On('ai-logs-filter')]
    public function applyFilter(string $filter): void
    {
        $this->authorizeAny('settings.ai.logs.view');

        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status') {
            $this->statusFilter = $value;
        }

        if ($field === 'type') {
            $this->typeFilter = $value;
        }

        $this->resetPage();
    }

    #[On('ai-logs-clear-filters')]
    public function clearFilters(): void
    {
        $this->authorizeAny('settings.ai.logs.view');
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->resetPage();
    }

    #[On('ai-logs-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        $this->authorizeAny('settings.ai.logs.view');

        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $col): bool => $col !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['id'];
        }

        $this->syncColumnCheckboxes();
    }

    public function openLog(int $logId): void
    {
        $this->authorizeAny('settings.ai.logs.view');
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
        if (! $this->canAccessAny('settings.ai.logs.view')) {
            return view('livewire.ai-logs.list', [
                'logs' => new LengthAwarePaginator([], 0, 15),
            ]);
        }

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
        ]);
    }

    private function syncColumnCheckboxes(): void
    {
        $this->dispatch('ai-logs-columns-sync', columns: $this->visibleColumns);
    }
}
