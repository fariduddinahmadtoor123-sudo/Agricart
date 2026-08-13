<?php

namespace App\Livewire\Media;

use App\Models\MediaAsset;
use App\Models\User;
use App\Services\Media\MediaLibraryService;
use App\Services\Media\OptimizedImageStorage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;

class Form extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $assetId = null;

    public ?TemporaryUploadedFile $upload = null;

    public string $displayName = '';

    public string $mode = 'create';

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public bool $canManage = false;

    #[On('media-form-open')]
    public function openForm(?int $assetId = null, string $mode = 'create'): void
    {
        $user = $this->authorizeView();
        $this->canManage = $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'media-library', 'edit') || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'media-library', 'create');
        $this->reset(['upload', 'displayName', 'statusMessage']);
        $this->assetId = $assetId;
        $this->mode = $assetId ? 'edit' : 'create';

        if ($assetId) {
            $asset = MediaAsset::query()->find($assetId);

            if (! $asset instanceof MediaAsset) {
                $this->assetId = null;
                $this->mode = 'create';

                return;
            }

            $this->displayName = $asset->display_name;
        }
    }

    public function save(OptimizedImageStorage $storage, MediaLibraryService $library): void
    {
        $this->authorizeManage();

        if ($this->assetId) {
            $validated = $this->validate([
                'displayName' => ['required', 'string', 'max:255'],
            ]);
            $asset = MediaAsset::query()->findOrFail($this->assetId);
            $library->rename($asset, $validated['displayName']);
            $this->statusMessage = __('agricart.media.messages.renamed');
        } else {
            $validated = $this->validate([
                'upload' => ['required', 'image', 'max:10240'],
                'displayName' => ['nullable', 'string', 'max:255'],
            ]);
            $path = $storage->store($validated['upload'], 'media-library', 'catalog', 'public');
            $asset = MediaAsset::query()
                ->where('disk', 'public')
                ->where('path', $path)
                ->firstOrFail();

            if (filled($validated['displayName'])) {
                $library->rename($asset, $validated['displayName']);
            }

            $this->assetId = $asset->id;
            $this->displayName = $asset->display_name;
            $this->mode = 'edit';
            $this->upload = null;
            $this->statusMessage = __('agricart.media.messages.uploaded');
        }

        $this->statusType = 'success';
        $this->dispatch('media-library-updated');
    }

    public function trash(MediaLibraryService $library): void
    {
        $this->authorizeManage();
        $asset = $this->asset();

        try {
            $library->trash($asset);
            $this->statusMessage = __('agricart.media.messages.trashed');
            $this->statusType = 'success';
            $this->dispatch('media-library-updated');
        } catch (RuntimeException $exception) {
            $this->statusMessage = $exception->getMessage();
            $this->statusType = 'danger';
        }
    }

    public function restore(MediaLibraryService $library): void
    {
        $this->authorizeManage();
        $library->restore($this->asset());
        $this->statusMessage = __('agricart.media.messages.restored');
        $this->statusType = 'success';
        $this->dispatch('media-library-updated');
    }

    public function permanentlyDelete(MediaLibraryService $library): void
    {
        $this->authorizeManage();
        $asset = $this->asset();

        if (! $asset->trashed_at) {
            abort(409);
        }

        $library->permanentlyDelete($asset);
        $this->reset(['assetId', 'upload', 'displayName', 'statusMessage']);
        $this->mode = 'create';
        $this->dispatch('media-library-updated');
        $this->dispatch('close-form-panel');
    }

    public function selectAsset(): void
    {
        $this->authorizeView();
        $asset = $this->asset();

        if ($asset->trashed_at) {
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

    public function render(): View
    {
        $user = auth()->user();
        $canView = $user instanceof User
            && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'media-library', 'view'));
        $asset = $canView && $this->assetId
            ? MediaAsset::query()->with(['usages' => fn ($query) => $query->latest()])->find($this->assetId)
            : null;

        return view('livewire.media.form', [
            'asset' => $asset,
            'pairedOriginal' => $asset instanceof MediaAsset
                ? app(MediaLibraryService::class)->pairedOriginalSummary($asset)
                : null,
        ]);
    }

    private function asset(): MediaAsset
    {
        return MediaAsset::query()->findOrFail($this->assetId);
    }

    private function authorizeView(): User
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User
            && ($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'media-library', 'view')),
            403,
        );

        return $user;
    }

    private function authorizeManage(): User
    {
        $user = $this->authorizeView();
        abort_unless($user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'media-library', 'edit') || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'media-library', 'create'), 403);

        return $user;
    }
}
