<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductPackage;
use App\Models\ProductPackageItem;
use App\Models\User;
use App\Services\Identifiers\DocumentCounterService;
use App\Services\Media\OptimizedImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductPackageService
{
    public function __construct(
        private readonly OptimizedImageStorage $images,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{product_id: int|string, quantity: float|string}>  $items
     * @param  list<int>  $categoryIds
     */
    public function create(
        array $data,
        array $items,
        array $categoryIds = [],
        TemporaryUploadedFile|UploadedFile|null $image = null,
        ?User $actor = null,
    ): ProductPackage {
        $validated = $this->validatePackage($data);
        $lineItems = $this->validateItems($items);
        $categories = $this->normalizeCategoryIds($categoryIds);
        $actor ??= auth()->user();

        return DB::transaction(function () use ($validated, $lineItems, $categories, $image, $actor): ProductPackage {
            if (! filled($validated['package_number'] ?? null)) {
                $validated['package_number'] = $this->nextPackageNumber();
            }

            $validated['slug'] = $this->uniqueSlug(
                filled($validated['slug'] ?? null)
                    ? (string) $validated['slug']
                    : (string) $validated['name_en'],
            );
            $validated['created_by'] = $actor instanceof User ? $actor->id : null;

            if ($image !== null) {
                $validated['image_path'] = $this->storeImage($image);
            }

            $package = ProductPackage::query()->create($validated);
            $this->syncItems($package, $lineItems);
            $package->categories()->sync($categories);

            return $package->fresh(['items.product', 'categories']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{product_id: int|string, quantity: float|string}>  $items
     * @param  list<int>  $categoryIds
     */
    public function update(
        ProductPackage $package,
        array $data,
        array $items,
        array $categoryIds = [],
        TemporaryUploadedFile|UploadedFile|null $image = null,
        bool $removeImage = false,
    ): ProductPackage {
        $validated = $this->validatePackage($data, $package->id);
        $lineItems = $this->validateItems($items);
        $categories = $this->normalizeCategoryIds($categoryIds);

        return DB::transaction(function () use ($package, $validated, $lineItems, $categories, $image, $removeImage): ProductPackage {
            if ($removeImage && filled($package->image_path)) {
                $this->deleteImage($package->image_path);
                $validated['image_path'] = null;
            }

            if ($image !== null) {
                if (filled($package->image_path)) {
                    $this->deleteImage($package->image_path);
                }
                $validated['image_path'] = $this->storeImage($image);
            }

            if (array_key_exists('slug', $validated) && filled($validated['slug'])) {
                $validated['slug'] = $this->uniqueSlug((string) $validated['slug'], $package->id);
            } else {
                unset($validated['slug']);
            }

            $package->update($validated);
            $this->syncItems($package, $lineItems);
            $package->categories()->sync($categories);

            return $package->fresh(['items.product', 'categories']);
        });
    }

    public function delete(ProductPackage $package): void
    {
        DB::transaction(function () use ($package): void {
            if (filled($package->image_path)) {
                $this->deleteImage($package->image_path);
            }

            $package->categories()->detach();
            $package->items()->delete();
            $package->delete();
        });
    }

    public function nextPackageNumber(): string
    {
        return app(DocumentCounterService::class)->next(
            'product_packages.package_number',
            'PKG-',
            5,
            fn (): int => DocumentCounterService::maximumSuffix('product_packages', 'package_number', 'PKG-'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatePackage(array $data, ?int $ignoreId = null): array
    {
        $uniqueNumber = Rule::unique('product_packages', 'package_number');
        $uniqueSlug = Rule::unique('product_packages', 'slug');

        if ($ignoreId !== null) {
            $uniqueNumber->ignore($ignoreId);
            $uniqueSlug->ignore($ignoreId);
        }

        return validator($data, [
            'package_number' => ['nullable', 'string', 'max:32', $uniqueNumber],
            'slug' => ['nullable', 'string', 'max:255', $uniqueSlug],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(ProductPackage::STATUSES)],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ])->validate();
    }

    /**
     * @param  list<array{product_id: int|string, quantity: float|string}>  $items
     * @return list<array{product_id: int, quantity: float, sort_order: int}>
     */
    private function validateItems(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => [__('agricart.packages.messages.items_required')],
            ]);
        }

        $normalized = [];
        $seen = [];

        foreach (array_values($items) as $index => $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = round((float) ($row['quantity'] ?? 0), 3);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            if (isset($seen[$productId])) {
                $normalized[$seen[$productId]]['quantity'] = round(
                    $normalized[$seen[$productId]]['quantity'] + $quantity,
                    3,
                );

                continue;
            }

            if (! Product::query()->whereKey($productId)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'items' => [__('agricart.packages.messages.product_inactive')],
                ]);
            }

            $seen[$productId] = count($normalized);
            $normalized[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'sort_order' => $index + 1,
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'items' => [__('agricart.packages.messages.items_required')],
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<int|string>  $categoryIds
     * @return list<int>
     */
    private function normalizeCategoryIds(array $categoryIds): array
    {
        return collect($categoryIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<array{product_id: int, quantity: float, sort_order: int}>  $items
     */
    private function syncItems(ProductPackage $package, array $items): void
    {
        $package->items()->delete();

        foreach ($items as $item) {
            ProductPackageItem::query()->create([
                'product_package_id' => $package->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'sort_order' => $item['sort_order'],
            ]);
        }
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'package';
        $slug = $base;
        $i = 2;

        while (
            ProductPackage::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function storeImage(TemporaryUploadedFile|UploadedFile $file): string
    {
        return $this->images->store(
            $file,
            'packages',
            'category',
            (string) config('media.disk', 'public'),
        );
    }

    private function deleteImage(string $path): void
    {
        Storage::disk((string) config('media.disk', 'public'))->delete($path);
    }
}
