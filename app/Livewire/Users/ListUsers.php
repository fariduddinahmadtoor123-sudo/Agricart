<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListUsers extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['user_number', 'name', 'email', 'role', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('users-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('users-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('users-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status') {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('users-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('users-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
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
            $this->visibleColumns = ['name'];
        }

        $this->dispatch('users-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('agricart.users.actions.add'),
            resource: __('agricart.pages.settings.users'),
            formKey: 'users',
            mode: 'create',
        );
    }

    public function openEdit(int $userId): void
    {
        $user = User::query()->find($userId);

        if ($user === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.users.actions.edit', ['name' => $user->name]),
            resource: __('agricart.pages.settings.users'),
            formKey: 'users',
            userId: $userId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $users = User::query()
            ->with(['role'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.users.list', [
            'users' => $users,
        ]);
    }
}
