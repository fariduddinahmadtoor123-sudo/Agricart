<?php

namespace App\Services\Media;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\MediaAsset;
use App\Models\MediaSyncFailure;
use App\Models\MediaUsage;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchasePlanningItem;
use App\Models\PurchaseQuotationItem;
use App\Models\SaleItem;
use App\Models\SaleQuotationItem;
use App\Models\SaleReturnItem;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaLibraryService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function registerPath(
        string $path,
        ?string $originalName = null,
        ?string $displayName = null,
        ?string $sourceModule = null,
        string $disk = 'public',
        array $metadata = [],
    ): ?MediaAsset {
        $path = $this->normalizePath($path);
        $allowsPrivateDocument = $disk === 'local' && str_starts_with($path, 'users/cnic/');

        if (! Schema::hasTable('media_assets') || ($disk !== 'public' && ! $allowsPrivateDocument)) {
            return null;
        }

        $storage = Storage::disk($disk);

        if ($path === '' || ! $storage->exists($path)) {
            return null;
        }

        try {
            $asset = MediaAsset::query()->firstOrNew([
                'disk' => $disk,
                'path' => $path,
            ]);
            $facts = $asset->exists
                ? [
                    'mime_type' => $asset->mime_type,
                    'extension' => $asset->extension,
                    'byte_size' => $asset->byte_size,
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'checksum' => $asset->checksum,
                ]
                : $this->fileFacts($disk, $path);
            $asset->uuid ??= (string) Str::uuid();
            $asset->visibility = $allowsPrivateDocument ? 'private' : 'public';
            $asset->original_name = $this->cleanName($originalName) ?: $asset->original_name;
            $asset->display_name = $this->cleanName($displayName)
                ?: $asset->display_name
                ?: $asset->original_name
                ?: pathinfo($path, PATHINFO_FILENAME);
            $asset->mime_type = $facts['mime_type'];
            $asset->extension = $facts['extension'];
            $asset->byte_size = $facts['byte_size'];
            $asset->width = $facts['width'];
            $asset->height = $facts['height'];
            $asset->checksum = $facts['checksum'];
            $asset->source_module = $sourceModule ?: $asset->source_module ?: $this->sourceFromPath($path);
            $asset->optimization_status = $facts['extension'] === 'webp' ? 'optimized' : 'ready';
            $asset->metadata = [...($asset->metadata ?? []), ...$metadata];
            $asset->created_by ??= auth()->id();
            $asset->save();

            return $asset;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function attach(
        MediaAsset|string $asset,
        Model $owner,
        string $collection = 'main',
        int $position = 0,
        ?string $label = null,
        ?string $displayName = null,
        ?string $sourceModule = null,
    ): ?MediaUsage {
        $asset = is_string($asset)
            ? $this->registerPath($asset, null, $displayName, $sourceModule)
            : $asset;

        if (! $asset instanceof MediaAsset) {
            return null;
        }

        return MediaUsage::query()->updateOrCreate(
            [
                'media_asset_id' => $asset->id,
                'usable_type' => $owner->getMorphClass(),
                'usable_id' => $owner->getKey(),
                'collection' => mb_substr($collection, 0, 64),
                'position' => max(0, $position),
            ],
            ['label' => $this->cleanName($label)],
        );
    }

    /**
     * @param  list<string>  $paths
     */
    public function syncOwnerCollection(
        Model $owner,
        string $collection,
        array $paths,
        ?string $displayPrefix = null,
        ?string $sourceModule = null,
    ): void {
        $normalized = collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->map(fn (string $path): string => $this->normalizePath($path))
            ->values();

        DB::transaction(function () use ($owner, $collection, $normalized, $displayPrefix, $sourceModule): void {
            MediaUsage::query()
                ->where('usable_type', $owner->getMorphClass())
                ->where('usable_id', $owner->getKey())
                ->where('collection', $collection)
                ->delete();

            foreach ($normalized as $position => $path) {
                $display = filled($displayPrefix)
                    ? trim($displayPrefix).' — '.match ($collection) {
                        'main' => 'Main image',
                        'gallery' => 'Gallery '.($position + 1),
                        'logo' => 'Logo',
                        'footer-logo' => 'Footer logo',
                        'profile-photo' => 'Profile photo',
                        'snapshot' => 'Historical image',
                        default => Str::headline($collection),
                    }
                : null;

                $usage = $this->attach(
                    $path,
                    $owner,
                    $collection,
                    $position,
                    null,
                    $display,
                    $sourceModule,
                );

                if (! $usage instanceof MediaUsage) {
                    throw new RuntimeException("Unable to synchronize media path [{$path}].");
                }
            }
        });
    }

    public function rename(MediaAsset $asset, string $displayName): MediaAsset
    {
        $name = $this->cleanName($displayName);

        if ($name === null) {
            throw new RuntimeException('A display name is required.');
        }

        $asset->forceFill(['display_name' => $name])->save();

        return $asset;
    }

    /**
     * @param  list<string>|null  $changedFields
     */
    public function syncModel(Model $owner, ?array $changedFields = null): void
    {
        if (! Schema::hasTable('media_assets') || ! Schema::hasTable('media_usages')) {
            return;
        }

        foreach ($this->modelSpecifications() as [$modelClass, $column, $collection, $source]) {
            if (! $owner instanceof $modelClass) {
                continue;
            }

            if ($changedFields !== null && ! in_array($column, $changedFields, true)) {
                continue;
            }

            $path = $owner->getAttribute($column);
            $this->syncOwnerCollection(
                $owner,
                $collection,
                is_string($path) && filled($path) ? [$path] : [],
                $this->ownerLabel($owner),
                $source,
            );
        }

        if ($owner instanceof Product && ($changedFields === null || in_array('gallery_images', $changedFields, true))) {
            $gallery = is_array($owner->gallery_images) ? $owner->gallery_images : [];
            $this->syncOwnerCollection(
                $owner,
                'gallery',
                array_values(array_filter($gallery, fn (mixed $path): bool => is_string($path) && filled($path))),
                $this->ownerLabel($owner),
                'products',
            );
        }
    }

    public function detachModel(Model $owner): void
    {
        if (! Schema::hasTable('media_usages')) {
            return;
        }

        MediaUsage::query()
            ->where('usable_type', $owner->getMorphClass())
            ->where('usable_id', $owner->getKey())
            ->delete();
    }

    public function deleteFileIfUnused(string $path, string $disk = 'public'): bool
    {
        $path = $this->normalizePath($path);

        if ($this->isProtectedDocumentPath($path, $disk)) {
            return false;
        }

        $asset = Schema::hasTable('media_assets')
            ? MediaAsset::query()->where('disk', $disk)->where('path', $path)->first()
            : null;

        if ($asset instanceof MediaAsset && $this->isProtectedDocument($asset)) {
            return false;
        }

        if ($asset?->usages()->exists() || $asset?->source_module === 'media-library') {
            return false;
        }

        if ($this->pathIsReferenced($path)) {
            return false;
        }

        $asset?->delete();

        return Storage::disk($disk)->delete($path);
    }

    public function canSafelyTrash(MediaAsset $asset): bool
    {
        if ($this->isProtectedDocument($asset)) {
            return false;
        }

        return ! $asset->usages()->exists() && ! $this->pathIsReferenced($asset->path);
    }

    /**
     * @return array{trashed: int, skipped: int}
     */
    public function trashUnused(?int $limit = null): array
    {
        $trashed = 0;
        $skipped = 0;

        MediaAsset::query()
            ->whereNull('trashed_at')
            ->whereDoesntHave('usages')
            ->orderBy('id')
            ->chunkById(100, function ($assets) use ($limit, &$trashed, &$skipped): void {
                foreach ($assets as $asset) {
                    if ($limit !== null && $trashed >= $limit) {
                        return;
                    }

                    if (! $this->canSafelyTrash($asset)) {
                        $skipped++;

                        continue;
                    }

                    $this->trash($asset);
                    $trashed++;
                }
            });

        return ['trashed' => $trashed, 'skipped' => $skipped];
    }

    /**
     * @return list<string>
     */
    public function mediaFieldsFor(Model $owner): array
    {
        $fields = [];

        foreach ($this->modelSpecifications() as [$modelClass, $column]) {
            if ($owner instanceof $modelClass) {
                $fields[] = $column;
            }
        }

        if ($owner instanceof Product) {
            $fields[] = 'gallery_images';
        }

        return array_values(array_unique($fields));
    }

    /**
     * @param  list<string|null>  $paths
     */
    public function cleanupUnusedPaths(array $paths): int
    {
        $deleted = 0;

        foreach (array_unique(array_filter($paths, fn (mixed $path): bool => is_string($path) && filled($path))) as $path) {
            if ($this->deleteFileIfUnused($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @param  list<string>  $changedFields
     * @param  list<string|null>  $oldPaths
     */
    public function recordSyncFailure(
        string $ownerType,
        int $ownerId,
        string $operation,
        array $changedFields,
        array $oldPaths,
        Throwable $exception,
    ): void {
        if (! Schema::hasTable('media_sync_failures')) {
            report($exception);

            return;
        }

        MediaSyncFailure::query()->create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'operation' => $operation,
            'changed_fields' => $changedFields,
            'old_paths' => $oldPaths,
            'last_error' => Str::limit($exception->getMessage(), 2000, ''),
        ]);
    }

    public function repairFailures(): int
    {
        if (! Schema::hasTable('media_sync_failures')) {
            return 0;
        }

        $repaired = 0;

        MediaSyncFailure::query()
            ->whereNull('resolved_at')
            ->orderBy('id')
            ->each(function (MediaSyncFailure $failure) use (&$repaired): void {
                try {
                    if ($failure->operation === 'delete') {
                        /** @var Model $ownerPrototype */
                        $ownerPrototype = new $failure->owner_type;
                        MediaUsage::query()
                            ->where('usable_type', $ownerPrototype->getMorphClass())
                            ->where('usable_id', $failure->owner_id)
                            ->delete();
                    } else {
                        $owner = $failure->owner_type::query()->find($failure->owner_id);

                        if ($owner instanceof Model) {
                            $this->syncModel($owner, $failure->changed_fields ?: null);
                        } else {
                            /** @var Model $ownerPrototype */
                            $ownerPrototype = new $failure->owner_type;
                            MediaUsage::query()
                                ->where('usable_type', $ownerPrototype->getMorphClass())
                                ->where('usable_id', $failure->owner_id)
                                ->delete();
                        }
                    }

                    $this->cleanupUnusedPaths($failure->old_paths ?: []);
                    $failure->update(['resolved_at' => now()]);
                    $repaired++;
                } catch (Throwable $exception) {
                    $failure->update([
                        'attempts' => $failure->attempts + 1,
                        'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                    ]);
                }
            });

        return $repaired;
    }

    public function sweepZeroUsageAssets(bool $dryRun = false): int
    {
        $count = 0;

        MediaAsset::query()
            ->where('source_module', '!=', 'media-library')
            ->whereNull('trashed_at')
            ->whereDoesntHave('usages')
            ->orderBy('id')
            ->each(function (MediaAsset $asset) use ($dryRun, &$count): void {
                if ($this->isProtectedDocument($asset) || $this->pathIsReferenced($asset->path)) {
                    return;
                }

                $count++;

                if (! $dryRun) {
                    $this->deleteFileIfUnused($asset->path, $asset->disk);
                }
            });

        return $count;
    }

    public function trash(MediaAsset $asset): MediaAsset
    {
        if ($this->isProtectedDocument($asset) || $asset->usages()->exists() || $this->pathIsReferenced($asset->path)) {
            throw new RuntimeException(__('agricart.media.messages.still_referenced'));
        }

        $asset->forceFill(['trashed_at' => now()])->save();

        return $asset;
    }

    public function restore(MediaAsset $asset): MediaAsset
    {
        $asset->forceFill(['trashed_at' => null])->save();

        return $asset;
    }

    public function permanentlyDelete(MediaAsset $asset): void
    {
        if ($this->isProtectedDocument($asset) || $asset->usages()->exists() || $this->pathIsReferenced($asset->path)) {
            throw new RuntimeException(__('agricart.media.messages.still_referenced'));
        }

        $disk = $asset->disk;
        $path = $asset->path;

        DB::transaction(function () use ($asset, $disk, $path): void {
            $asset->delete();
            DB::afterCommit(fn () => Storage::disk($disk)->delete($path));
        });
    }

    public function isProtectedDocument(MediaAsset $asset): bool
    {
        if ($asset->visibility === 'private') {
            return true;
        }

        return $this->isProtectedDocumentPath($asset->path, $asset->disk);
    }

    public function isProtectedDocumentPath(string $path, ?string $disk = null): bool
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return false;
        }

        if (str_starts_with($path, 'users/cnic/') || str_starts_with($path, 'online-orders/customer-receipts/')) {
            return true;
        }

        return $disk === 'local' && str_starts_with($path, 'users/');
    }

    public function pathIsReferenced(string $path): bool
    {
        $path = $this->normalizePath($path);

        foreach ($this->modelSpecifications() as [$modelClass, $column]) {
            if ($modelClass::query()->where($column, $path)->exists()) {
                return true;
            }
        }

        if (
            str_starts_with($path, 'users/cnic/')
            && User::query()
                ->where(function ($query) use ($path): void {
                    $query->where('cnic_front_path', $path)
                        ->orWhere('cnic_back_path', $path);
                })
                ->exists()
        ) {
            return true;
        }

        return Product::query()
            ->whereNotNull('gallery_images')
            ->cursor()
            ->contains(function (Product $product) use ($path): bool {
                return is_array($product->gallery_images)
                    && in_array($path, $product->gallery_images, true);
            });
    }

    public function pruneTrashed(int $days = 30): int
    {
        $count = 0;

        MediaAsset::query()
            ->whereNotNull('trashed_at')
            ->where('trashed_at', '<', now()->subDays(max(1, $days)))
            ->whereDoesntHave('usages')
            ->orderBy('id')
            ->chunkById(200, function ($assets) use (&$count): void {
                foreach ($assets as $asset) {
                    if ($this->isProtectedDocument($asset)) {
                        $asset->forceFill(['trashed_at' => null])->save();

                        continue;
                    }

                    $this->permanentlyDelete($asset);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array{mime_type: ?string, extension: ?string, byte_size: int, width: ?int, height: ?int, checksum: ?string}
     */
    private function fileFacts(string $disk, string $path): array
    {
        $storage = Storage::disk($disk);
        $mime = rescue(fn () => $storage->mimeType($path), null, report: false);
        $size = rescue(fn () => $storage->size($path), 0, report: false);
        $width = null;
        $height = null;
        $checksum = null;

        try {
            $absolute = $storage->path($path);
            $dimensions = @getimagesize($absolute);

            if (is_array($dimensions)) {
                [$width, $height] = $dimensions;
            }

            $hash = @hash_file('sha256', $absolute);
            $checksum = is_string($hash) ? $hash : null;
        } catch (Throwable) {
            // Remote disks may not provide a local path.
        }

        return [
            'mime_type' => is_string($mime) ? mb_substr($mime, 0, 120) : null,
            'extension' => mb_substr(strtolower(pathinfo($path, PATHINFO_EXTENSION)), 0, 20) ?: null,
            'byte_size' => max(0, (int) $size),
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
        ];
    }

    private function sourceFromPath(string $path): string
    {
        return mb_substr((string) (strtok($path, '/') ?: 'library'), 0, 64);
    }

    private function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', trim($path)), '/');
    }

    private function cleanName(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name !== '' ? mb_substr($name, 0, 255) : null;
    }

    /**
     * @return list<array{class-string<Model>, string, string, string}>
     */
    private function modelSpecifications(): array
    {
        return [
            [Category::class, 'image_path', 'main', 'categories'],
            [Brand::class, 'image_path', 'main', 'brands'],
            [Product::class, 'image_path', 'main', 'products'],
            [CompanyProfile::class, 'logo_path', 'logo', 'company'],
            [StoreSetting::class, 'logo_path', 'logo', 'store'],
            [StoreSetting::class, 'footer_logo_path', 'footer-logo', 'store'],
            [Customer::class, 'profile_photo_path', 'profile-photo', 'customers'],
            [Supplier::class, 'profile_photo_path', 'profile-photo', 'suppliers'],
            [User::class, 'profile_photo_path', 'profile-photo', 'users'],
            [PurchaseItem::class, 'image_path', 'snapshot', 'purchases'],
            [PurchasePlanningItem::class, 'image_path', 'snapshot', 'purchasing'],
            [PurchaseQuotationItem::class, 'image_path', 'snapshot', 'purchases'],
            [SaleItem::class, 'image_path', 'snapshot', 'sales'],
            [SaleQuotationItem::class, 'image_path', 'snapshot', 'sales'],
            [SaleReturnItem::class, 'image_path', 'snapshot', 'sales'],
            [OnlineSaleOrderItem::class, 'image_path', 'snapshot', 'online-orders'],
        ];
    }

    private function ownerLabel(Model $owner): string
    {
        foreach (['name_en', 'name', 'company_name', 'supplier_name', 'customer_name', 'product_name'] as $field) {
            $value = $owner->getAttribute($field);

            if (is_string($value) && filled($value)) {
                return trim($value);
            }
        }

        return Str::headline(class_basename($owner)).' #'.$owner->getKey();
    }
}
