<?php

namespace App\Livewire\Categories;

use App\Livewire\Concerns\HandlesCatalogPortableArchive;
use App\Models\Category;
use App\Models\User;
use App\Services\Catalog\CatalogPortableArchiveService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ListCategories extends Component
{
    use HandlesCatalogPortableArchive;
    use WithFileUploads;
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['image', 'name_en', 'slug', 'status', 'ai_status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('categories-action')]
    public function handleToolbarAction(string $action): mixed
    {
        return $this->handleCatalogPortableAction($action);
    }

    #[On('categories-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    protected function catalogPortableScope(): string
    {
        return CatalogPortableArchiveService::SCOPE_CATEGORIES;
    }

    protected function catalogPortableCan(string $action): bool
    {
        return $this->userCan($action);
    }

    #[On('categories-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('categories-filter')]
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

    #[On('categories-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('categories-column-toggle')]
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
            $this->visibleColumns = ['name_en'];
        }

        $this->dispatch('categories-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->authorizeAction('create');

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.toolbar.add', ['item' => 'Categories']),
            resource: 'Categories',
            formKey: 'categories',
            mode: 'create',
        );
    }

    public function openView(int $categoryId): void
    {
        $this->authorizeAction('view');

        $category = Category::query()->find($categoryId);

        if ($category === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.categories.actions.view', ['name' => $category->name_en]),
            resource: __('agricart.pages.catalog.categories'),
            formKey: 'categories',
            categoryId: $categoryId,
            mode: 'view',
        );
    }

    public function openEdit(int $categoryId): void
    {
        $this->authorizeAction('edit');

        $category = Category::query()->find($categoryId);

        if ($category === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.categories.actions.edit', ['name' => $category->name_en]),
            resource: __('agricart.pages.catalog.categories'),
            formKey: 'categories',
            categoryId: $categoryId,
            mode: 'edit',
        );
    }

    public function deleteCategory(int $categoryId): void
    {
        $this->authorizeAction('delete');

        $category = Category::query()->find($categoryId);

        if ($category === null) {
            return;
        }

        try {
            app(\App\Services\Catalog\CategoryService::class)->delete($category);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title((string) collect($exception->errors())->flatten()->first())
                ->send();

            return;
        }

        $this->resetPage();
    }

    public function render(): View
    {
        $categories = Category::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.categories.list', [
            'categories' => $categories,
            'canView' => $this->userCan('view'),
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
        ]);
    }

    private function authorizeAction(string $action): void
    {
        abort_unless($this->userCan($action), 403);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'catalog', 'categories', $action);
    }
}
