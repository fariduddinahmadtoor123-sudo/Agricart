<?php

namespace App\Filament\Clusters\Tools;

use App\Filament\Clusters\ToolsCluster;
use App\Filament\Pages\BaseModulePage;
use App\Models\ToolsBarcodePrintFolder;
use App\Services\Access\ModulePageAccess;
use App\Services\Tools\BarcodePrintSettingsService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class BarcodePrintingPage extends BaseModulePage
{
    protected static ?string $cluster = ToolsCluster::class;

    protected static ?string $navigationLabel = 'Barcode Printing';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'barcode-printing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected string $view = 'filament.pages.tools.barcode-printing';

    public string $search = '';

    /** edit | view */
    public string $formMode = 'edit';

    public bool $showSizeForm = false;

    public bool $showStickerForm = false;

    public string $sizeWidthCm = '';

    public string $sizeHeightCm = '';

    /**
     * @var list<array{width_cm: float, height_cm: float, label: string}>
     */
    public array $savedSizes = [];

    public string $selectedSizeKey = '';

    public ?int $editingFolderId = null;

    public string $stickerName = '';

    /**
     * @var list<array{size_key: string, width_cm: float, height_cm: float, label: string, qty: int, lines: list<string>}>
     */
    public array $stickers = [];

    public const MAX_LINES = 8;

    public const MAX_QTY = 999;

    public function mount(): void
    {
        $this->reloadSavedSizes();
    }

    public function isViewMode(): bool
    {
        return $this->formMode === 'view';
    }

    #[On('tools-barcode-printing-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('tools-barcode-printing-select-size')]
    public function selectSize(?string $key = null): void
    {
        $this->selectedSizeKey = trim((string) $key);
    }

    public function handleChromeRequest(): void
    {
        $this->syncChrome();
    }

    #[On('tools-barcode-printing-action')]
    public function handleToolbarAction(string $action): void
    {
        match ($action) {
            'add-sticker-size' => $this->openSizeForm(),
            'add-sticker' => $this->addStickerFromSelection(),
            default => null,
        };
    }

    public function openSizeForm(): void
    {
        $this->authorizeEdit();
        $this->showSizeForm = true;
        $this->sizeWidthCm = '';
        $this->sizeHeightCm = '';
        $this->reloadSavedSizes();
        $this->syncChrome();
    }

    public function closeSizeForm(): void
    {
        $this->showSizeForm = false;
        $this->sizeWidthCm = '';
        $this->sizeHeightCm = '';
    }

    public function saveStickerSize(): void
    {
        $this->authorizeEdit();

        $width = round((float) str_replace(',', '.', trim($this->sizeWidthCm)), 2);
        $height = round((float) str_replace(',', '.', trim($this->sizeHeightCm)), 2);

        if ($width <= 0 || $height <= 0) {
            Notification::make()
                ->title(__('agricart.pages.tools.bc_size_invalid'))
                ->danger()
                ->send();

            return;
        }

        if ($width > 100 || $height > 100) {
            Notification::make()
                ->title(__('agricart.pages.tools.bc_size_too_large'))
                ->danger()
                ->send();

            return;
        }

        $service = app(BarcodePrintSettingsService::class);
        $sizes = $service->sizesForUser();
        $key = $width.'x'.$height;

        foreach ($sizes as $size) {
            if (($size['width_cm'].'x'.$size['height_cm']) === $key) {
                Notification::make()
                    ->title(__('agricart.pages.tools.bc_size_exists'))
                    ->warning()
                    ->send();

                return;
            }
        }

        $sizes[] = [
            'width_cm' => $width,
            'height_cm' => $height,
        ];

        $this->savedSizes = $service->saveSizes($sizes);
        $this->selectedSizeKey = $key;
        $this->sizeWidthCm = '';
        $this->sizeHeightCm = '';
        $this->syncChrome();

        Notification::make()
            ->title(__('agricart.pages.tools.bc_size_saved'))
            ->success()
            ->send();
    }

    public function removeSavedSize(int $index): void
    {
        $this->authorizeEdit();

        $sizes = $this->savedSizes;
        if (! isset($sizes[$index])) {
            return;
        }

        $removed = $sizes[$index];
        unset($sizes[$index]);
        $this->savedSizes = app(BarcodePrintSettingsService::class)->saveSizes(array_values($sizes));

        $removedKey = ($removed['width_cm'] ?? '').'x'.($removed['height_cm'] ?? '');
        if ($this->selectedSizeKey === $removedKey) {
            $this->selectedSizeKey = '';
        }

        $this->syncChrome();
    }

    public function addStickerFromSelection(): void
    {
        $this->authorizeEdit();

        $size = $this->resolveSelectedSize();

        if ($size === null) {
            Notification::make()
                ->title(__('agricart.pages.tools.bc_pick_size_first'))
                ->warning()
                ->send();

            return;
        }

        $this->editingFolderId = null;
        $this->formMode = 'edit';
        $this->stickerName = '';
        $this->stickers = [
            $this->makeSticker($size),
        ];
        $this->showStickerForm = true;
    }

    public function closeStickerForm(): void
    {
        $this->showStickerForm = false;
        $this->formMode = 'edit';
        $this->editingFolderId = null;
        $this->stickerName = '';
        $this->stickers = [];
    }

    public function updateStickerSize(int $index, string $sizeKey): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $this->authorizeEdit();

        if (! isset($this->stickers[$index])) {
            return;
        }

        $previous = $this->selectedSizeKey;
        $this->selectedSizeKey = trim($sizeKey);
        $size = $this->resolveSelectedSize();
        $this->selectedSizeKey = $previous;

        if ($size === null) {
            return;
        }

        $this->stickers[$index]['size_key'] = $size['width_cm'].'x'.$size['height_cm'];
        $this->stickers[$index]['width_cm'] = $size['width_cm'];
        $this->stickers[$index]['height_cm'] = $size['height_cm'];
        $this->stickers[$index]['label'] = $size['label'];
        $this->selectedSizeKey = $this->stickers[$index]['size_key'];
        $this->syncChrome();
    }

    public function addStickerLine(int $index): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $this->authorizeEdit();

        if (! isset($this->stickers[$index])) {
            return;
        }

        $lines = $this->stickers[$index]['lines'] ?? [''];

        if (count($lines) >= self::MAX_LINES) {
            return;
        }

        $lines[] = '';
        $this->stickers[$index]['lines'] = $lines;
    }

    public function removeStickerLine(int $index): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $this->authorizeEdit();

        if (! isset($this->stickers[$index])) {
            return;
        }

        $lines = $this->stickers[$index]['lines'] ?? [''];

        if (count($lines) <= 1) {
            $this->stickers[$index]['lines'] = [''];

            return;
        }

        array_pop($lines);
        $this->stickers[$index]['lines'] = array_values($lines);
    }

    public function saveAndPrint(): void
    {
        if ($this->isViewMode()) {
            return;
        }

        $this->authorizeEdit();

        if (! $this->persistFolder()) {
            return;
        }

        $this->bumpPrintCount();

        Notification::make()
            ->title(__('agricart.pages.tools.bc_folder_saved', ['name' => $this->stickerName]))
            ->success()
            ->send();

        $this->js(
            '(() => {'
            .'let closed = false;'
            .'const done = () => {'
            .'  if (closed) return;'
            .'  closed = true;'
            .'  window.agricartBcFinishPrint?.();'
            .'  $wire.closeStickerForm();'
            .'};'
            .'window.addEventListener("afterprint", done, { once: true });'
            .'window.dispatchEvent(new CustomEvent("tools-barcode-printing-print"));'
            // Fallback only if afterprint never fires (some browsers).
            .'setTimeout(done, 20000);'
            .'})()'
        );
    }

    public function printStickers(): void
    {
        if (! $this->showStickerForm || $this->stickers === []) {
            return;
        }

        if ($this->editingFolderId) {
            $this->bumpPrintCount();
        }

        $this->js('window.dispatchEvent(new CustomEvent("tools-barcode-printing-print"))');
    }

    public function openFolder(int $id, string $mode = 'view'): void
    {
        $folder = ToolsBarcodePrintFolder::query()
            ->where('user_id', auth()->id())
            ->where('status', 'saved')
            ->findOrFail($id);

        if ($mode === 'edit') {
            $this->authorizeEdit();
        }

        $this->loadFolderIntoForm($folder, $mode === 'edit' ? 'edit' : 'view');
    }

    public function reprintFolder(int $id): void
    {
        $folder = ToolsBarcodePrintFolder::query()
            ->where('user_id', auth()->id())
            ->where('status', 'saved')
            ->findOrFail($id);

        $this->loadFolderIntoForm($folder, 'view');
        $this->bumpPrintCount();
        $this->js('window.dispatchEvent(new CustomEvent("tools-barcode-printing-print"))');
    }

    /**
     * @return Collection<int, ToolsBarcodePrintFolder>
     */
    public function listedFolders(): Collection
    {
        $query = ToolsBarcodePrintFolder::query()
            ->where('user_id', auth()->id())
            ->where('status', 'saved')
            ->latest('updated_at');

        $search = trim($this->search);

        if ($search !== '') {
            $query->where('sticker_name', 'like', '%'.$search.'%');
        }

        return $query->limit(200)->get();
    }

    /**
     * One template sheet per sticker design — qty copies are cloned in JS only when printing.
     *
     * @return list<array{css_w: string, css_h: string, lines: list<string>, line_count: int, qty: int}>
     */
    public function printSheets(): array
    {
        $sheets = [];

        foreach ($this->stickers as $sticker) {
            $qty = max(1, min(self::MAX_QTY, (int) ($sticker['qty'] ?? 1)));
            $lines = array_values(array_filter(
                array_map(static fn ($line) => trim((string) $line), $sticker['lines'] ?? []),
                static fn ($line) => $line !== ''
            ));
            $lineCount = max(1, count($lines));
            $w = (float) ($sticker['width_cm'] ?? 0);
            $h = (float) ($sticker['height_cm'] ?? 0);

            if ($w <= 0 || $h <= 0) {
                continue;
            }

            $sheets[] = [
                'css_w' => $w.'cm',
                'css_h' => $h.'cm',
                'lines' => $lines,
                'line_count' => $lineCount,
                'qty' => (int) $qty,
            ];
        }

        return $sheets;
    }

    protected function persistFolder(): bool
    {
        $name = trim($this->stickerName);

        if ($name === '') {
            Notification::make()
                ->title(__('agricart.pages.tools.bc_name_required'))
                ->danger()
                ->send();

            return false;
        }

        if ($this->stickers === []) {
            Notification::make()
                ->title(__('agricart.pages.tools.bc_need_sticker'))
                ->danger()
                ->send();

            return false;
        }

        $stickers = $this->normalizeStickersForSave($this->stickers);

        $payload = [
            'user_id' => (int) auth()->id(),
            'sticker_name' => mb_substr($name, 0, 160),
            'stickers' => $stickers,
            'sticker_count' => count($stickers),
            'status' => 'saved',
        ];

        if ($this->editingFolderId) {
            $folder = ToolsBarcodePrintFolder::query()
                ->where('user_id', auth()->id())
                ->findOrFail($this->editingFolderId);
            $folder->fill($payload)->save();
            $this->editingFolderId = $folder->id;
        } else {
            $folder = ToolsBarcodePrintFolder::query()->create($payload);
            $this->editingFolderId = $folder->id;
        }

        $this->stickerName = $folder->sticker_name;

        return true;
    }

    protected function bumpPrintCount(): void
    {
        if (! $this->editingFolderId) {
            return;
        }

        ToolsBarcodePrintFolder::query()
            ->where('user_id', auth()->id())
            ->where('id', $this->editingFolderId)
            ->increment('print_count');
    }

    protected function loadFolderIntoForm(ToolsBarcodePrintFolder $folder, string $mode): void
    {
        $this->editingFolderId = $folder->id;
        $this->formMode = $mode;
        $this->stickerName = (string) $folder->sticker_name;
        $this->stickers = $this->normalizeStickersForSave(is_array($folder->stickers) ? $folder->stickers : []);
        $this->showStickerForm = true;

        if (isset($this->stickers[0]['size_key'])) {
            $this->selectedSizeKey = (string) $this->stickers[0]['size_key'];
            $this->syncChrome();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $stickers
     * @return list<array{size_key: string, width_cm: float, height_cm: float, label: string, qty: int, lines: list<string>}>
     */
    protected function normalizeStickersForSave(array $stickers): array
    {
        $out = [];
        $service = app(BarcodePrintSettingsService::class);

        foreach ($stickers as $sticker) {
            if (! is_array($sticker)) {
                continue;
            }

            $w = round((float) ($sticker['width_cm'] ?? 0), 2);
            $h = round((float) ($sticker['height_cm'] ?? 0), 2);

            if ($w <= 0 || $h <= 0) {
                continue;
            }

            $lines = $sticker['lines'] ?? [''];
            if (! is_array($lines) || $lines === []) {
                $lines = [''];
            }

            $lines = array_values(array_map(
                static fn ($line) => mb_substr(trim((string) $line), 0, 200),
                array_slice($lines, 0, self::MAX_LINES)
            ));

            if ($lines === []) {
                $lines = [''];
            }

            $out[] = [
                'size_key' => $w.'x'.$h,
                'width_cm' => $w,
                'height_cm' => $h,
                'label' => (string) ($sticker['label'] ?? $service->formatLabel($w, $h)),
                'qty' => max(1, min(self::MAX_QTY, (int) ($sticker['qty'] ?? 1))),
                'lines' => $lines,
            ];
        }

        return $out;
    }

    /**
     * @param  array{width_cm: float, height_cm: float, label: string}  $size
     * @return array{size_key: string, width_cm: float, height_cm: float, label: string, qty: int, lines: list<string>}
     */
    protected function makeSticker(array $size): array
    {
        return [
            'size_key' => $size['width_cm'].'x'.$size['height_cm'],
            'width_cm' => $size['width_cm'],
            'height_cm' => $size['height_cm'],
            'label' => $size['label'],
            'qty' => 1,
            'lines' => [''],
        ];
    }

    /**
     * @return array{width_cm: float, height_cm: float, label: string}|null
     */
    protected function resolveSelectedSize(): ?array
    {
        $key = trim($this->selectedSizeKey);

        if ($key === '') {
            return null;
        }

        foreach ($this->savedSizes as $size) {
            if (($size['width_cm'].'x'.$size['height_cm']) === $key) {
                return $size;
            }
        }

        if (! str_contains($key, 'x')) {
            return null;
        }

        [$w, $h] = array_pad(explode('x', $key, 2), 2, '0');
        $width = round((float) $w, 2);
        $height = round((float) $h, 2);

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return [
            'width_cm' => $width,
            'height_cm' => $height,
            'label' => app(BarcodePrintSettingsService::class)->formatLabel($width, $height),
        ];
    }

    protected function reloadSavedSizes(): void
    {
        $this->savedSizes = app(BarcodePrintSettingsService::class)->sizesForUser();
    }

    protected function syncChrome(): void
    {
        $sizes = [];

        foreach ($this->savedSizes as $size) {
            $sizes[] = [
                'key' => $size['width_cm'].'x'.$size['height_cm'],
                'label' => $size['label'],
            ];
        }

        $payload = [
            'sizes' => $sizes,
            'selected' => $this->selectedSizeKey,
        ];

        $this->dispatch('tools-barcode-printing-chrome', sizes: $sizes, selected: $this->selectedSizeKey);
        $this->js(
            'window.dispatchEvent(new CustomEvent("tools-barcode-printing-chrome",{detail:'.json_encode($payload).'}))'
        );
    }

    protected function authorizeEdit(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->isSuperAdmin()
                || ModulePageAccess::can($user, 'tools', 'barcode-printing', 'edit')
                || ModulePageAccess::can($user, 'tools', 'barcode-printing', 'create')
            ),
            403
        );
    }
}
