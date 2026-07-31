<?php

namespace App\Livewire\Concerns;

use App\Services\Catalog\CatalogPortableArchiveService;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Scoped catalog .ags export/import for Brands, Categories, Units, etc.
 * Products keeps full-catalog export via CatalogPortableArchiveService::exportAll().
 */
trait HandlesCatalogPortableArchive
{
    use ImportsPortableArchive;

    public bool $showImportPanel = false;

    /** @var mixed */
    public $importArchive = null;

    public ?string $portableStatus = null;

    public string $portableStatusTone = 'success';

    /**
     * One of CatalogPortableArchiveService::SCOPE_* (not SCOPE_FULL).
     */
    abstract protected function catalogPortableScope(): string;

    abstract protected function catalogPortableCan(string $action): bool;

    public function openImportPanel(): void
    {
        if (! $this->catalogPortableCan('create')) {
            $this->portableStatus = __('agricart.catalog.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return;
        }

        $this->showImportPanel = true;
        $this->importArchive = null;
        $this->portableStatus = null;
    }

    public function closeImportPanel(): void
    {
        $this->showImportPanel = false;
        $this->importArchive = null;
    }

    /**
     * After the .ags finishes uploading, import automatically so the list refreshes
     * without a separate “load” step (upload alone never writes to the database).
     */
    public function updatedImportArchive(): void
    {
        if ($this->importArchive === null) {
            return;
        }

        $this->importUploadedArchive();
    }

    public function exportArchive(): ?BinaryFileResponse
    {
        if (! $this->catalogPortableCan('view')) {
            $this->portableStatus = __('agricart.catalog.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return null;
        }

        try {
            $scope = $this->catalogPortableScope();
            $archive = app(CatalogPortableArchiveService::class)->export($scope);
            $this->portableStatus = __('agricart.catalog.portable.export_ok', [
                'count' => $archive['count'],
                'entity' => __('agricart.catalog.portable.entities.'.$scope),
            ]);
            $this->portableStatusTone = 'success';

            return response()->download($archive['path'], $archive['filename'])->deleteFileAfterSend(true);
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';

            return null;
        }
    }

    public function importUploadedArchive(): void
    {
        if (! $this->catalogPortableCan('create')) {
            $this->portableStatus = __('agricart.catalog.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return;
        }

        $scope = $this->catalogPortableScope();

        $this->validate([
            'importArchive' => ['required', 'file', 'max:1024000'],
        ], [], [
            'importArchive' => __('agricart.products.actions.import_archive'),
        ]);

        $materialized = $this->materializePortableArchiveUpload($this->importArchive, $scope.'-import');
        $absolute = $materialized['path'];

        try {
            $result = app(CatalogPortableArchiveService::class)->importFromPath($absolute, $scope);
            $this->portableStatus = __('agricart.catalog.portable.import_ok', [
                ...$result,
                'entity' => __('agricart.catalog.portable.entities.'.$scope),
            ]);
            $this->portableStatusTone = 'success';
            $this->showImportPanel = false;
            $this->importArchive = null;
            $this->afterSuccessfulPortableImport();

            Notification::make()
                ->title($this->portableStatus)
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';

            Notification::make()
                ->title((string) $this->portableStatus)
                ->danger()
                ->send();
        } catch (Throwable $exception) {
            report($exception);
            $this->portableStatus = __('agricart.errors.generic');
            $this->portableStatusTone = 'danger';

            Notification::make()
                ->title($this->portableStatus)
                ->danger()
                ->send();
        } finally {
            if ($materialized['delete'] && is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    /**
     * Clear list filters and refresh pagination after a successful import.
     */
    protected function afterSuccessfulPortableImport(): void
    {
        if (property_exists($this, 'search') && is_string($this->search)) {
            $this->search = '';
        }

        if (property_exists($this, 'statusFilter') && is_string($this->statusFilter)) {
            $this->statusFilter = '';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * @return mixed
     */
    protected function handleCatalogPortableAction(string $action)
    {
        return match ($action) {
            'export_archive' => $this->exportArchive(),
            'import_archive' => $this->openImportPanel(),
            default => null,
        };
    }
}
