<?php

namespace App\Livewire\ActivityLogs;

use App\Enums\ActivityEvent;
use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\ActivityLog;
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
        'created_at',
        'event',
        'actor',
        'description',
        'ip_address',
    ];

    public string $search = '';

    public string $eventFilter = '';

    public string $periodFilter = '';

    public function mount(): void
    {
        if (! $this->canAccessAny('settings.activity.view')) {
            return;
        }

        $this->syncColumnCheckboxes();
    }

    #[On('activity-logs-search')]
    public function updateSearch(string $query): void
    {
        $this->authorizeAny('settings.activity.view');
        $this->search = $query;
        $this->resetPage();
    }

    #[On('activity-logs-filter')]
    public function applyFilter(string $filter): void
    {
        $this->authorizeAny('settings.activity.view');

        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'event') {
            $this->eventFilter = $value;
        }

        if ($field === 'period') {
            $this->periodFilter = $value;
        }

        $this->resetPage();
    }

    #[On('activity-logs-clear-filters')]
    public function clearFilters(): void
    {
        $this->authorizeAny('settings.activity.view');
        $this->eventFilter = '';
        $this->periodFilter = '';
        $this->resetPage();
    }

    #[On('activity-logs-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        $this->authorizeAny('settings.activity.view');

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
            $this->visibleColumns = ['created_at'];
        }

        $this->syncColumnCheckboxes();
    }

    public function openLog(int $logId): void
    {
        $this->authorizeAny('settings.activity.view');
        $log = ActivityLog::query()->find($logId);

        if ($log === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.activity.detail_title', ['id' => $log->id]),
            resource: $log->event->label(),
            formKey: 'activity-log-detail',
            logId: $logId,
        );
    }

    public function render(): View
    {
        if (! $this->canAccessAny('settings.activity.view')) {
            return view('livewire.activity-logs.list', [
                'logs' => new LengthAwarePaginator([], 0, 15),
            ]);
        }

        $logs = ActivityLog::query()
            ->with('user')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('description', 'like', $needle)
                        ->orWhere('subject_label', 'like', $needle)
                        ->orWhere('ip_address', 'like', $needle)
                        ->orWhereHas('user', function ($userQuery) use ($needle): void {
                            $userQuery->where('name', 'like', $needle)
                                ->orWhere('email', 'like', $needle);
                        });
                });
            })
            ->when(filled($this->eventFilter), function ($query): void {
                $event = ActivityEvent::tryFrom($this->eventFilter);

                if ($event !== null) {
                    $query->where('event', $event->value);
                }
            })
            ->when(filled($this->periodFilter), function ($query): void {
                match ($this->periodFilter) {
                    'today' => $query->where('created_at', '>=', now()->startOfDay()),
                    '24h' => $query->where('created_at', '>=', now()->subDay()),
                    '7d' => $query->where('created_at', '>=', now()->subDays(7)),
                    '30d' => $query->where('created_at', '>=', now()->subDays(30)),
                    default => null,
                };
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.activity-logs.list', [
            'logs' => $logs,
        ]);
    }

    private function syncColumnCheckboxes(): void
    {
        $this->dispatch('activity-logs-columns-sync', columns: $this->visibleColumns);
    }
}
