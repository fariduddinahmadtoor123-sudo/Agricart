<?php

namespace App\Livewire\Concerns;

use App\Services\Catalog\CatalogPortableArchiveService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            'importArchive' => ['required', 'file', 'max:512000'],
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
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';
        } finally {
            if ($materialized['delete'] && is_file($absolute)) {
                @unlink($absolute);
            }
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
