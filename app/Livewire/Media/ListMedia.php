<?php

namespace App\Livewire\Media;

use App\Models\MediaAsset;
use App\Models\User;
use App\Services\Media\MediaLibraryService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class ListMedia extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sourceFilter = '';

    public string $usageFilter = '';

    public string $typeFilter = '';

    public string $dateFilter = '';

    public string $statusFilter = 'active';

    public bool $canManage = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $user = $this->authorizeView();
        $this->canManage = $user->isSuperAdmin() || $user->hasPermission('settings.media.manage');
    }

    #[On('media-library-search')]
    public function updateSearch(string $query): void
    {
        $this->authorizeView();
        $this->search = mb_substr(trim($query), 0, 200);
        $this->resetPage();
    }

    #[On('media-library-filter')]
    public function applyFilter(string $filter): void
    {
        $this->authorizeView();

        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'source' && in_array($value, ['', 'products', 'categories', 'brands', 'store', 'profiles', 'library'], true)) {
            $this->sourceFilter = $value;
        }

        if ($field === 'usage' && in_array($value, ['', 'used', 'unused'], true)) {
            $this->usageFilter = $value;
        }

        if ($field === 'type' && in_array($value, ['', 'webp', 'jpeg', 'png', 'gif'], true)) {
            $this->typeFilter = $value;
        }

        if ($field === 'date' && in_array($value, ['', 'today', '7days', '30days'], true)) {
            $this->dateFilter = $value;
        }

        if ($field === 'status' && in_array($value, ['active', 'trash'], true)) {
            $this->statusFilter = $value;
        }

        $this->resetPage();
    }

    #[On('media-library-clear-filters')]
    public function clearFilters(): void
    {
        $this->authorizeView();
        $this->sourceFilter = '';
        $this->usageFilter = '';
        $this->typeFilter = '';
        $this->dateFilter = '';
        $this->statusFilter = 'active';
        $this->resetPage();
    }

    public function openAsset(int $assetId): void
    {
        $this->authorizeView();

        if (! MediaAsset::query()->whereKey($assetId)->exists()) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.media.detail_title'),
            resource: __('agricart.media.title'),
            formKey: 'media-library',
            assetId: $assetId,
            mode: 'edit',
        );
    }

    public function selectAsset(int $assetId): void
    {
        $this->authorizeView();
        $asset = MediaAsset::query()->whereNull('trashed_at')->find($assetId);

        if (! $asset instanceof MediaAsset) {
            return;
        }

        $this->dispatch(
            'media-library-selected',
            assetId: $asset->id,
            path: $asset->path,
            url: $asset->url(),
            name: $asset->display_name,
        );
        $this->statusMessage = __('agricart.media.selected', ['name' => $asset->display_name]);
        $this->statusType = 'success';
    }

    public function trashAsset(int $assetId, MediaLibraryService $library): void
    {
        $this->authorizeManage();
        $asset = MediaAsset::query()->findOrFail($assetId);

        try {
            $library->trash($asset);
            $this->statusMessage = __('agricart.media.messages.trashed');
            $this->statusType = 'success';
        } catch (RuntimeException $exception) {
            $this->statusMessage = $exception->getMessage();
            $this->statusType = 'danger';
        }
    }

    public function restoreAsset(int $assetId, MediaLibraryService $library): void
    {
        $this->authorizeManage();
        $asset = MediaAsset::query()->findOrFail($assetId);
        $library->restore($asset);
        $this->statusMessage = __('agricart.media.messages.restored');
        $this->statusType = 'success';
    }

    public function trashAllUnused(MediaLibraryService $library): void
    {
        $this->authorizeManage();

        $result = $library->trashUnused();
        $this->statusMessage = __('agricart.media.messages.trashed_bulk', [
            'count' => $result['trashed'],
            'skipped' => $result['skipped'],
        ]);
        $this->statusType = $result['trashed'] > 0 ? 'success' : 'danger';
        $this->resetPage();
    }

    #[On('media-library-updated')]
    public function refreshList(): void
    {
        $this->authorizeView();
    }

    public function render(): View
    {
        $this->authorizeView();
        $assets = MediaAsset::query()
            ->withCount('usages')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('display_name', 'like', $needle)
                        ->orWhere('original_name', 'like', $needle)
                        ->orWhere('path', 'like', $needle);
                });
            })
            ->when($this->sourceFilter === 'profiles', fn ($query) => $query->whereIn('source_module', ['customers', 'suppliers', 'users']))
            ->when($this->sourceFilter === 'store', fn ($query) => $query->whereIn('source_module', ['store', 'company']))
            ->when(filled($this->sourceFilter) && ! in_array($this->sourceFilter, ['profiles', 'store'], true), fn ($query) => $query->where('source_module', $this->sourceFilter))
            ->when($this->usageFilter === 'used', fn ($query) => $query->has('usages'))
            ->when($this->usageFilter === 'unused', function ($query): void {
                $query->doesntHave('usages')
                    ->where(function ($inner): void {
                        $inner->where('visibility', '!=', 'private')
                            ->where('disk', '!=', 'local')
                            ->where('path', 'not like', 'users/cnic/%')
                            ->where('path', 'not like', 'online-orders/customer-receipts/%');
                    });
            })
            ->when(filled($this->typeFilter), fn ($query) => $query->where('mime_type', $this->typeFilter === 'jpeg' ? 'image/jpeg' : 'image/'.$this->typeFilter))
            ->when($this->dateFilter === 'today', fn ($query) => $query->whereDate('created_at', today()))
            ->when($this->dateFilter === '7days', fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
            ->when($this->dateFilter === '30days', fn ($query) => $query->where('created_at', '>=', now()->subDays(30)))
            ->when($this->statusFilter === 'trash', fn ($query) => $query->whereNotNull('trashed_at'))
            ->when($this->statusFilter !== 'trash', fn ($query) => $query->whereNull('trashed_at'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(24);

        return view('livewire.media.list-media', [
            'assets' => $assets,
            'stats' => [
                'all' => MediaAsset::query()->whereNull('trashed_at')->count(),
                'used' => MediaAsset::query()->whereNull('trashed_at')->has('usages')->count(),
                'unused' => MediaAsset::query()
                    ->whereNull('trashed_at')
                    ->doesntHave('usages')
                    ->where(function ($inner): void {
                        $inner->where('visibility', '!=', 'private')
                            ->where('disk', '!=', 'local')
                            ->where('path', 'not like', 'users/cnic/%')
                            ->where('path', 'not like', 'online-orders/customer-receipts/%');
                    })
                    ->count(),
                'trash' => MediaAsset::query()->whereNotNull('trashed_at')->count(),
            ],
        ]);
    }

    private function authorizeView(): User
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('settings.media.view')),
            403,
        );

        return $user;
    }

    private function authorizeManage(): User
    {
        $user = $this->authorizeView();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('settings.media.manage'), 403);

        return $user;
    }
}
