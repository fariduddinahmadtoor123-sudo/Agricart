<?php

namespace App\Livewire\SystemErrors;

use App\Livewire\SystemErrors\Concerns\AuthorizesSystemErrors;
use App\Models\SystemError;
use App\Services\SystemErrors\SystemErrorReadService;
use App\Services\SystemErrors\SystemLogFileReader;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListErrors extends Component
{
    use AuthorizesSystemErrors;
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'reference',
        'severity',
        'message',
        'occurrences',
        'last_seen_at',
        'status',
    ];

    public string $search = '';

    public string $statusFilter = SystemError::STATUS_OPEN;

    public string $severityFilter = '';

    public string $dateFilter = '';

    public string $sourceFilter = 'inbox';

    public bool $canManage = false;

    public function mount(SystemErrorReadService $reads): void
    {
        $user = $this->authorizeSystemErrorView();
        $this->canManage = $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'system-errors', 'edit');
        $reads->markCurrentCriticalErrorsRead($user);
        $this->dispatch('topbar-notifications-refresh');
        $this->syncColumnCheckboxes();
    }

    #[On('system-errors-search')]
    public function updateSearch(string $query): void
    {
        $this->authorizeSystemErrorView();
        $this->search = mb_substr(trim($query), 0, 200);
        $this->resetPage();
    }

    #[On('system-errors-filter')]
    public function applyFilter(string $filter): void
    {
        $this->authorizeSystemErrorView();

        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, ['', SystemError::STATUS_OPEN, SystemError::STATUS_RESOLVED], true)) {
            $this->statusFilter = $value;
        }

        if ($field === 'severity' && in_array($value, ['', SystemError::SEVERITY_WARNING, SystemError::SEVERITY_ERROR, SystemError::SEVERITY_CRITICAL], true)) {
            $this->severityFilter = $value;
        }

        if ($field === 'date' && in_array($value, ['', 'today', '7days', '30days'], true)) {
            $this->dateFilter = $value;
        }

        if ($field === 'source' && in_array($value, ['inbox', 'file'], true)) {
            $this->sourceFilter = $value;
        }

        $this->resetPage();
    }

    #[On('system-errors-clear-filters')]
    public function clearFilters(): void
    {
        $this->authorizeSystemErrorView();
        $this->statusFilter = SystemError::STATUS_OPEN;
        $this->severityFilter = '';
        $this->dateFilter = '';
        $this->sourceFilter = 'inbox';
        $this->resetPage();
    }

    public function showSource(string $source): void
    {
        $this->authorizeSystemErrorView();

        if (! in_array($source, ['inbox', 'file'], true)) {
            return;
        }

        $this->sourceFilter = $source;
        $this->resetPage();
    }

    #[On('system-errors-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        $this->authorizeSystemErrorView();
        $allowed = ['reference', 'severity', 'message', 'occurrences', 'last_seen_at', 'status'];

        if (! in_array($column, $allowed, true)) {
            return;
        }

        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $item): bool => $item !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['message'];
        }

        $this->syncColumnCheckboxes();
    }

    public function openError(int $errorId): void
    {
        $this->authorizeSystemErrorView();
        $error = SystemError::query()->find($errorId);

        if (! $error instanceof SystemError) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.system_errors.detail.title', ['reference' => $error->reference]),
            resource: __('agricart.system_errors.title'),
            formKey: 'system-error-detail',
            errorId: $error->id,
        );
    }

    public function openFileEntry(string $key): void
    {
        $this->authorizeSystemErrorView();

        if (! preg_match('/^[a-f0-9]{64}$/', $key)) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.system_errors.file_detail_title'),
            resource: __('agricart.system_errors.file_logs'),
            formKey: 'system-error-detail',
            fileKey: $key,
        );
    }

    #[On('system-errors-updated')]
    public function refreshList(): void
    {
        $this->authorizeSystemErrorView();
    }

    public function render(SystemLogFileReader $fileReader): View
    {
        $this->authorizeSystemErrorView();
        $stats = [
            'open' => SystemError::query()->where('status', SystemError::STATUS_OPEN)->count(),
            'critical' => SystemError::query()
                ->where('status', SystemError::STATUS_OPEN)
                ->where('severity', SystemError::SEVERITY_CRITICAL)
                ->count(),
            'resolved' => SystemError::query()->where('status', SystemError::STATUS_RESOLVED)->count(),
        ];

        if ($this->sourceFilter === 'file') {
            $entries = collect($fileReader->recent())
                ->when(filled($this->search), function ($items) {
                    $needle = mb_strtolower($this->search);

                    return $items->filter(fn (array $item): bool => str_contains(
                        mb_strtolower($item['message'].' '.$item['trace'].' '.$item['file']),
                        $needle,
                    ));
                })
                ->when(filled($this->severityFilter), fn ($items) => $items->where('severity', $this->severityFilter))
                ->values()
                ->all();

            return view('livewire.system-errors.list', [
                'errors' => null,
                'fileEntries' => $entries,
                'stats' => $stats,
            ]);
        }

        $errors = SystemError::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('reference', 'like', $needle)
                        ->orWhere('exception_class', 'like', $needle)
                        ->orWhere('message', 'like', $needle)
                        ->orWhere('file', 'like', $needle)
                        ->orWhere('route_name', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->severityFilter), fn ($query) => $query->where('severity', $this->severityFilter))
            ->when($this->dateFilter === 'today', fn ($query) => $query->whereDate('last_seen_at', today()))
            ->when($this->dateFilter === '7days', fn ($query) => $query->where('last_seen_at', '>=', now()->subDays(7)))
            ->when($this->dateFilter === '30days', fn ($query) => $query->where('last_seen_at', '>=', now()->subDays(30)))
            ->latest('last_seen_at')
            ->latest('id')
            ->paginate(20);

        return view('livewire.system-errors.list', [
            'errors' => $errors,
            'fileEntries' => [],
            'stats' => $stats,
        ]);
    }

    private function syncColumnCheckboxes(): void
    {
        $this->dispatch('system-errors-columns-sync', columns: $this->visibleColumns);
    }
}
