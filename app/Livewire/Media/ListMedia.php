<?php

namespace App\Livewire\Media;

use App\Models\MediaAsset;
use App\Models\User;
use App\Services\Media\MediaLibraryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Number;
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
            if ($value !== '') {
                $this->statusFilter = 'active';
            }
        }

        if ($field === 'type' && in_array($value, ['', 'webp', 'jpeg', 'png', 'gif'], true)) {
            $this->typeFilter = $value;
        }

        if ($field === 'date' && in_array($value, ['', 'today', '7days', '30days'], true)) {
            $this->dateFilter = $value;
        }

        if ($field === 'status' && in_array($value, ['active', 'trash'], true)) {
            $this->statusFilter = $value;
            if ($value === 'active' || $value === 'trash') {
                $this->usageFilter = '';
            }
        }

        $this->resetPage();
    }

    /**
     * Summary cards: all | used | unused | trash
     */
    public function setSummaryBucket(string $bucket): void
    {
        $this->authorizeView();

        if (! in_array($bucket, ['all', 'used', 'unused', 'trash'], true)) {
            return;
        }

        if ($bucket === 'trash') {
            $this->statusFilter = 'trash';
            $this->usageFilter = '';
        } else {
            $this->statusFilter = 'active';
            $this->usageFilter = $bucket === 'all' ? '' : $bucket;
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

        $stats = $library->dashboardStats();
        $result = $library->trashUnused();
        $this->statusMessage = __('agricart.media.messages.trashed_bulk_sized', [
            'count' => $result['trashed'],
            'skipped' => $result['skipped'],
            'size' => $this->formatBytes((int) $stats['unused_bytes']),
        ]);
        $this->statusType = $result['trashed'] > 0 ? 'success' : 'danger';
        $this->resetPage();
    }

    public function emptyTrash(MediaLibraryService $library): void
    {
        $this->authorizeManage();

        $result = $library->emptyTrash();
        $this->statusMessage = __('agricart.media.messages.empty_trash_done', [
            'count' => $result['deleted'],
            'skipped' => $result['skipped'],
            'size' => $this->formatBytes((int) $result['bytes']),
        ]);
        $this->statusType = $result['deleted'] > 0 ? 'success' : 'danger';
        $this->setSummaryBucket('trash');
    }

    public function repairUsageLinks(): void
    {
        $this->authorizeManage();

        $exit = Artisan::call('media:backfill-library');
        $output = trim(Artisan::output());
        app(MediaLibraryService::class)->forgetStatsCache();

        $this->statusMessage = $exit === 0
            ? ($output !== '' ? $output : __('agricart.media.messages.links_repaired'))
            : __('agricart.media.messages.links_repair_failed');
        $this->statusType = $exit === 0 ? 'success' : 'danger';
        $this->resetPage();
    }

    public function pruneOrphanFiles(): void
    {
        $this->authorizeManage();

        $exit = Artisan::call('media:prune-orphans');
        $output = trim(Artisan::output());
        $this->statusMessage = $exit === 0
            ? ($output !== '' ? $output : __('agricart.media.messages.orphans_cleaned'))
            : __('agricart.media.messages.orphans_failed');
        $this->statusType = $exit === 0 ? 'success' : 'danger';
        app(MediaLibraryService::class)->forgetStatsCache();
        $this->resetPage();
    }

    #[On('media-library-action')]
    public function handleToolbarAction(string $action): void
    {
        match ($action) {
            'prune_orphans' => $this->pruneOrphanFiles(),
            'repair_links' => $this->repairUsageLinks(),
            'empty_trash' => $this->emptyTrash(app(MediaLibraryService::class)),
            default => null,
        };
    }

    #[On('media-library-updated')]
    public function refreshList(): void
    {
        $this->authorizeView();
        app(MediaLibraryService::class)->forgetStatsCache();
    }

    public function render(): View
    {
        $this->authorizeView();
        $library = app(MediaLibraryService::class);
        $stats = $library->dashboardStats();

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
            ->when($this->usageFilter === 'used', function ($query) use ($library): void {
                $library->applyInUseConstraint($query->whereNull('trashed_at'), inUse: true);
            })
            ->when($this->usageFilter === 'unused', fn ($query) => $query->whereIn(
                'id',
                $library->unusedAssetsQuery()->select('id'),
            ))
            ->when(filled($this->typeFilter), fn ($query) => $query->where('mime_type', $this->typeFilter === 'jpeg' ? 'image/jpeg' : 'image/'.$this->typeFilter))
            ->when($this->dateFilter === 'today', fn ($query) => $query->whereDate('created_at', today()))
            ->when($this->dateFilter === '7days', fn ($query) => $query->where('created_at', '>=', now()->subDays(7)))
            ->when($this->dateFilter === '30days', fn ($query) => $query->where('created_at', '>=', now()->subDays(30)))
            ->when($this->statusFilter === 'trash', fn ($query) => $query->whereNotNull('trashed_at'))
            ->when($this->statusFilter !== 'trash', fn ($query) => $query->whereNull('trashed_at'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(24);

        $activeBucket = match (true) {
            $this->statusFilter === 'trash' => 'trash',
            $this->usageFilter === 'used' => 'used',
            $this->usageFilter === 'unused' => 'unused',
            default => 'all',
        };

        return view('livewire.media.list-media', [
            'assets' => $assets,
            'stats' => $stats,
            'activeBucket' => $activeBucket,
            'formattedSizes' => [
                'all' => $this->formatBytes((int) $stats['all_bytes']),
                'used' => $this->formatBytes((int) $stats['used_bytes']),
                'unused' => $this->formatBytes((int) $stats['unused_bytes']),
                'trash' => $this->formatBytes((int) $stats['trash_bytes']),
            ],
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 KB';
        }

        return Number::fileSize($bytes, precision: 1);
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
