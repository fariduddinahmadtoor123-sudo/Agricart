<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\ToolsBillPrintFolder;
use App\Services\Access\ModulePageAccess;
use App\Services\Media\OptimizedImageStorage;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class PrintingPage extends BaseModulePage
{
    use WithFileUploads;

    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Bill Printing';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'printing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPrinter;

    protected string $view = 'filament.pages.tools.bill-printing';

    public string $screen = 'list';

    public string $formMode = 'edit';

    public string $search = '';

    public ?int $editingFolderId = null;

    public string $folderName = '';

    public string $folderDate = '';

    /**
     * @var list<array{path: string, name: string, disk: string}>
     */
    public array $images = [];

    /**
     * @var list<TemporaryUploadedFile>|TemporaryUploadedFile|null
     */
    public $uploadBatch = [];

    public const MAX_IMAGES = 40;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'uploadBatch' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'uploadBatch.*' => ['image', 'max:15360'],
        ];
    }

    #[On('tools-bill-printing-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('tools-bill-printing-action')]
    public function handleToolbarAction(string $action): void
    {
        if ($action === 'new-batch') {
            $this->startNewBatch();

            return;
        }

        if ($this->screen !== 'form') {
            return;
        }

        match ($action) {
            'print' => $this->js('window.dispatchEvent(new CustomEvent("tools-bill-printing-print"))'),
            'clear-all' => $this->clearAllImages(),
            'close-form' => $this->closeForm(),
            default => null,
        };
    }

    public function isViewMode(): bool
    {
        return $this->formMode === 'view';
    }

    public function startNewBatch(): void
    {
        $this->authorizeCreate();

        $draft = ToolsBillPrintFolder::query()
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->latest('updated_at')
            ->first();

        if ($draft && is_array($draft->images) && count($draft->images) > 0) {
            $this->loadFolderIntoForm($draft, 'edit');
            Notification::make()->title(__('agricart.pages.tools.bp_draft_recovered'))->success()->send();

            return;
        }

        if ($draft) {
            $this->deleteFolderFiles($draft);
            $draft->delete();
        }

        $this->resetFreshBatch();
        $folder = ToolsBillPrintFolder::query()->create([
            'user_id' => (int) auth()->id(),
            'folder_name' => $this->makeFolderName(),
            'folder_date' => $this->folderDate,
            'images' => [],
            'image_count' => 0,
            'status' => 'draft',
        ]);
        $this->editingFolderId = (int) $folder->id;
        $this->folderName = (string) $folder->folder_name;
        $this->screen = 'form';
        $this->formMode = 'edit';
        $this->emitScreen('form');
    }

    public function openFolder(int $id): void
    {
        $folder = ToolsBillPrintFolder::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->where('status', 'saved')
            ->firstOrFail();
        $this->loadFolderIntoForm($folder, 'view');
    }

    public function closeForm(): void
    {
        if ($this->screen === 'form' && ! $this->isViewMode()) {
            if (count($this->images) > 0) {
                $this->saveAndClose();

                return;
            }
            $this->discardDraft();
        }

        $this->screen = 'list';
        $this->formMode = 'edit';
        $this->editingFolderId = null;
        $this->images = [];
        $this->uploadBatch = [];
        $this->emitScreen('list');
    }

    public function saveAndClose(): void
    {
        if ($this->isViewMode()) {
            $this->screen = 'list';
            $this->emitScreen('list');

            return;
        }

        $this->authorizeCreate();

        if (count($this->images) === 0) {
            $this->discardDraft();
            $this->screen = 'list';
            $this->emitScreen('list');
            Notification::make()->title(__('agricart.pages.tools.bp_need_images'))->warning()->send();

            return;
        }

        $name = $this->uniqueSavedFolderName($this->makeFolderName());
        $folder = $this->currentFolder();
        if (! $folder) {
            $folder = ToolsBillPrintFolder::query()->create([
                'user_id' => (int) auth()->id(),
                'folder_name' => $name,
                'folder_date' => now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y'),
                'images' => $this->images,
                'image_count' => count($this->images),
                'status' => 'saved',
            ]);
        } else {
            $folder->update([
                'folder_name' => $name,
                'folder_date' => now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y'),
                'images' => $this->images,
                'image_count' => count($this->images),
                'status' => 'saved',
            ]);
        }

        $this->editingFolderId = null;
        $this->images = [];
        $this->uploadBatch = [];
        $this->screen = 'list';
        $this->formMode = 'edit';
        $this->emitScreen('list');
        Notification::make()
            ->title(__('agricart.pages.tools.bp_folder_saved', ['name' => $name]))
            ->success()
            ->send();
    }

    public function clearAllImages(): void
    {
        if ($this->isViewMode()) {
            return;
        }
        $this->authorizeCreate();
        foreach ($this->images as $image) {
            $this->deleteImageFile($image);
        }
        $this->images = [];
        $this->persistDraftImages();
        Notification::make()->title(__('agricart.pages.tools.bp_cleared'))->success()->send();
    }

    public function removeImage(int $index): void
    {
        if ($this->isViewMode() || ! isset($this->images[$index])) {
            return;
        }
        $this->authorizeCreate();
        $this->deleteImageFile($this->images[$index]);
        array_splice($this->images, $index, 1);
        $this->images = array_values($this->images);
        $this->persistDraftImages();
    }

    public function updatedUploadBatch(): void
    {
        if ($this->isViewMode()) {
            $this->uploadBatch = [];

            return;
        }

        $this->authorizeCreate();
        $this->ensureDraftFolder();

        $files = $this->normalizeUploadBatch($this->uploadBatch);
        $this->uploadBatch = [];

        if ($files === []) {
            return;
        }

        $room = self::MAX_IMAGES - count($this->images);
        if ($room <= 0) {
            Notification::make()->title(__('agricart.pages.tools.bp_too_many'))->warning()->send();

            return;
        }

        if (count($files) > $room) {
            Notification::make()->title(__('agricart.pages.tools.bp_too_many'))->warning()->send();
            $files = array_slice($files, 0, $room);
        }

        $disk = $this->diskName();
        $dir = $this->folderStorageDir();
        $optimizer = app(OptimizedImageStorage::class);

        foreach ($files as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            $originalName = (string) ($file->getClientOriginalName() ?: 'bill.jpg');
            try {
                $path = $optimizer->store($file, $dir, 'bill_print', $disk, $originalName);
            } catch (\Throwable) {
                $path = $file->store($dir, $disk);
            }
            if (! is_string($path) || $path === '') {
                continue;
            }
            $this->images[] = [
                'path' => $path,
                'name' => $originalName,
                'disk' => $disk,
            ];
        }

        $this->persistDraftImages();
    }

    /**
     * @return \Illuminate\Support\Collection<int, ToolsBillPrintFolder>
     */
    public function listedFolders()
    {
        $query = ToolsBillPrintFolder::query()
            ->where('user_id', auth()->id())
            ->where('status', 'saved')
            ->latest('updated_at');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('folder_name', 'like', $like)
                    ->orWhere('folder_date', 'like', $like);
            });
        }

        return $query->limit(200)->get();
    }

    /**
     * @return list<array{path: string, name: string, disk: string, url: string}>
     */
    public function imageRows(): array
    {
        $rows = [];
        foreach ($this->images as $image) {
            if (! is_array($image) || empty($image['path'])) {
                continue;
            }
            $disk = (string) ($image['disk'] ?? $this->diskName());
            $path = (string) $image['path'];
            $rows[] = [
                'path' => $path,
                'name' => (string) ($image['name'] ?? basename($path)),
                'disk' => $disk,
                'url' => Storage::disk($disk)->url($path),
            ];
        }

        return $rows;
    }

    protected function loadFolderIntoForm(ToolsBillPrintFolder $folder, string $mode): void
    {
        $this->editingFolderId = (int) $folder->id;
        $this->folderName = (string) $folder->folder_name;
        $this->folderDate = (string) ($folder->folder_date ?? '');
        $this->images = $this->normalizeStoredImages(is_array($folder->images) ? $folder->images : []);
        $this->uploadBatch = [];
        $this->formMode = $mode === 'view' ? 'view' : 'edit';
        $this->screen = 'form';
        $this->emitScreen('form');
    }

    protected function resetFreshBatch(): void
    {
        $this->editingFolderId = null;
        $this->folderDate = now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y');
        $this->folderName = $this->makeFolderName();
        $this->images = [];
        $this->uploadBatch = [];
    }

    protected function ensureDraftFolder(): void
    {
        if ($this->editingFolderId) {
            return;
        }
        $folder = ToolsBillPrintFolder::query()->create([
            'user_id' => (int) auth()->id(),
            'folder_name' => $this->makeFolderName(),
            'folder_date' => $this->folderDate !== ''
                ? $this->folderDate
                : now(config('app.display_timezone', config('app.timezone')))->format('d-m-Y'),
            'images' => [],
            'image_count' => 0,
            'status' => 'draft',
        ]);
        $this->editingFolderId = (int) $folder->id;
        $this->folderName = (string) $folder->folder_name;
        $this->folderDate = (string) $folder->folder_date;
    }

    protected function persistDraftImages(): void
    {
        $folder = $this->currentFolder();
        if (! $folder || $folder->status !== 'draft') {
            return;
        }
        $folder->update([
            'images' => $this->images,
            'image_count' => count($this->images),
        ]);
    }

    protected function discardDraft(): void
    {
        $folder = $this->currentFolder();
        if (! $folder || $folder->status !== 'draft') {
            return;
        }
        $this->deleteFolderFiles($folder);
        $folder->delete();
        $this->editingFolderId = null;
        $this->images = [];
    }

    protected function currentFolder(): ?ToolsBillPrintFolder
    {
        if (! $this->editingFolderId) {
            return null;
        }

        return ToolsBillPrintFolder::query()
            ->where('user_id', auth()->id())
            ->whereKey($this->editingFolderId)
            ->first();
    }

    protected function makeFolderName(): string
    {
        $tz = config('app.display_timezone', config('app.timezone'));

        return now($tz)->format('d-m-Y H:i');
    }

    protected function uniqueSavedFolderName(string $base): string
    {
        $name = $base;
        $i = 2;
        while (
            ToolsBillPrintFolder::query()
                ->where('user_id', auth()->id())
                ->where('status', 'saved')
                ->where('folder_name', $name)
                ->when($this->editingFolderId, fn ($q) => $q->whereKeyNot($this->editingFolderId))
                ->exists()
        ) {
            $name = $base.' ('.$i.')';
            $i++;
        }

        return $name;
    }

    protected function folderStorageDir(): string
    {
        $userId = (int) auth()->id();
        $folderId = (int) ($this->editingFolderId ?? 0);

        return "tools/bill-printing/{$userId}/{$folderId}";
    }

    protected function diskName(): string
    {
        return (string) config('media.disk', config('filesystems.media_disk', 'public'));
    }

    /**
     * @param  mixed  $batch
     * @return list<TemporaryUploadedFile>
     */
    protected function normalizeUploadBatch(mixed $batch): array
    {
        if ($batch instanceof TemporaryUploadedFile) {
            return [$batch];
        }
        if (! is_array($batch)) {
            return [];
        }
        $out = [];
        foreach ($batch as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $out[] = $file;
            }
        }

        return $out;
    }

    /**
     * @param  list<mixed>  $images
     * @return list<array{path: string, name: string, disk: string}>
     */
    protected function normalizeStoredImages(array $images): array
    {
        $out = [];
        foreach ($images as $image) {
            if (! is_array($image) || empty($image['path'])) {
                continue;
            }
            $out[] = [
                'path' => (string) $image['path'],
                'name' => (string) ($image['name'] ?? basename((string) $image['path'])),
                'disk' => (string) ($image['disk'] ?? $this->diskName()),
            ];
        }

        return $out;
    }

    /**
     * @param  array{path?: string, disk?: string}|mixed  $image
     */
    protected function deleteImageFile(mixed $image): void
    {
        if (! is_array($image) || empty($image['path'])) {
            return;
        }
        $disk = (string) ($image['disk'] ?? $this->diskName());
        try {
            Storage::disk($disk)->delete((string) $image['path']);
        } catch (\Throwable) {
        }
    }

    protected function deleteFolderFiles(ToolsBillPrintFolder $folder): void
    {
        $images = is_array($folder->images) ? $folder->images : [];
        foreach ($images as $image) {
            $this->deleteImageFile($image);
        }
        $dir = 'tools/bill-printing/'.(int) $folder->user_id.'/'.(int) $folder->id;
        try {
            Storage::disk($this->diskName())->deleteDirectory($dir);
        } catch (\Throwable) {
        }
    }

    protected function emitScreen(string $screen): void
    {
        $mode = $this->isViewMode() ? 'view' : 'edit';
        $this->dispatch('tools-bill-printing-screen', screen: $screen, formMode: $mode);
        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-bill-printing-screen",{detail:{screen:'.json_encode($screen).',formMode:'.json_encode($mode).'}}))'
        );
    }

    protected function authorizeCreate(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || ModulePageAccess::can($user, 'tools', 'printing', 'create')),
            403
        );
    }

    public function mount(): void
    {
        $this->emitScreen($this->screen);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return ToolsCluster::unshiftClusterBreadcrumbs([
            static::getUrl() => static::getNavigationLabel(),
        ]);
    }
}
