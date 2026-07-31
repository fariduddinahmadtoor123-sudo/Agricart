<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\PackingUnit;
use App\Models\Product;
use App\Models\Unit;
use App\Services\PortableArchive\PortableArchiveCodec;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Portable encrypted catalog archive (.ags): units, packing units, attributes,
 * controls, brands, categories, products + media. Upsert by code → slug.
 * stock_qty is not overwritten on update; new products start at 0.
 *
 * Supports full-catalog and single-scope archives (brands, categories, …).
 */
class CatalogPortableArchiveService
{
    public const FORMAT = 'agricart.catalog.portable';

    public const VERSION = 1;

    public const SCOPE_FULL = 'catalog';

    public const SCOPE_BRANDS = 'brands';

    public const SCOPE_CATEGORIES = 'categories';

    public const SCOPE_UNITS = 'units';

    public const SCOPE_PACKING_UNITS = 'packing-units';

    public const SCOPE_ATTRIBUTES = 'attributes';

    public const SCOPE_CONTROLS = 'controls';

    /** @var list<string> */
    public const SCOPES = [
        self::SCOPE_FULL,
        self::SCOPE_BRANDS,
        self::SCOPE_CATEGORIES,
        self::SCOPE_UNITS,
        self::SCOPE_PACKING_UNITS,
        self::SCOPE_ATTRIBUTES,
        self::SCOPE_CONTROLS,
    ];

    public function __construct(
        private readonly PortableArchiveCodec $codec,
    ) {}

    public static function formatForScope(string $scope): string
    {
        return $scope === self::SCOPE_FULL
            ? self::FORMAT
            : 'agricart.catalog.'.$scope.'.portable';
    }

    /**
     * @return array{path: string, filename: string, count: int, scope: string}
     */
    public function exportAll(): array
    {
        return $this->export(self::SCOPE_FULL);
    }

    /**
     * @param  self::SCOPE_*  $scope
     * @return array{path: string, filename: string, count: int, scope: string}
     */
    public function export(string $scope = self::SCOPE_FULL): array
    {
        $this->codec->prepareLargeArchiveOperation();

        $scope = $this->assertScope($scope);
        $workDir = storage_path('app/tmp/catalog-export-'.Str::lower(Str::random(8)));
        File::ensureDirectoryExists($workDir.'/media');

        $bundle = [
            'units' => [],
            'packing_units' => [],
            'attributes' => [],
            'controls' => [],
            'brands' => [],
            'categories' => [],
            'products' => [],
        ];

        $includeAll = $scope === self::SCOPE_FULL;

        if ($includeAll || $scope === self::SCOPE_UNITS) {
            $bundle['units'] = Unit::query()
                ->orderBy('id')
                ->get()
                ->map(fn (Unit $row): array => $this->exportSimpleMaster($row))
                ->all();
        }

        if ($includeAll || $scope === self::SCOPE_PACKING_UNITS) {
            $bundle['packing_units'] = PackingUnit::query()
                ->orderBy('id')
                ->get()
                ->map(fn (PackingUnit $row): array => $this->exportSimpleMaster($row))
                ->all();
        }

        if ($includeAll || $scope === self::SCOPE_ATTRIBUTES) {
            $bundle['attributes'] = CatalogAttribute::query()
                ->orderBy('id')
                ->get()
                ->map(fn (CatalogAttribute $row): array => $this->exportSimpleMaster($row))
                ->all();
        }

        if ($includeAll || $scope === self::SCOPE_CONTROLS) {
            $bundle['controls'] = CatalogControl::query()
                ->orderBy('id')
                ->get()
                ->map(function (CatalogControl $row): array {
                    return [
                        ...$this->exportSimpleMaster($row),
                        'value_en' => $row->value_en,
                        'value_ur' => $row->value_ur,
                    ];
                })
                ->all();
        }

        if ($includeAll || $scope === self::SCOPE_BRANDS) {
            $bundle['brands'] = $this->exportBrands($workDir);
        }

        if ($includeAll || $scope === self::SCOPE_CATEGORIES) {
            $bundle['categories'] = $this->exportCategories($workDir);
        }

        if ($includeAll) {
            $bundle['products'] = $this->exportProducts($workDir);
        }

        $productCount = count($bundle['products']);
        $brandCount = count($bundle['brands']);
        $categoryCount = count($bundle['categories']);

        $count = match ($scope) {
            self::SCOPE_BRANDS => $brandCount,
            self::SCOPE_CATEGORIES => $categoryCount,
            self::SCOPE_UNITS => count($bundle['units']),
            self::SCOPE_PACKING_UNITS => count($bundle['packing_units']),
            self::SCOPE_ATTRIBUTES => count($bundle['attributes']),
            self::SCOPE_CONTROLS => count($bundle['controls']),
            default => $productCount,
        };

        $manifest = [
            'format' => self::formatForScope($scope),
            'version' => self::VERSION,
            'entity' => $scope,
            'app' => (string) config('app.name', 'Agricart'),
            'exported_at' => now()->toIso8601String(),
            'product_count' => $productCount,
            'brand_count' => $brandCount,
            'category_count' => $categoryCount,
        ];

        // Compact JSON — pretty-print doubles size/time on large catalogs (10k+ rows).
        File::put($workDir.'/manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE));
        File::put($workDir.'/catalog.json', json_encode($bundle, JSON_UNESCAPED_UNICODE));

        $filenameParts = $scope === self::SCOPE_FULL ? ['catalog'] : [$scope];

        try {
            $sealed = $this->codec->sealWorkDir(
                $workDir,
                $this->codec->datedFilename($filenameParts),
            );
        } finally {
            File::deleteDirectory($workDir);
        }

        return [
            'path' => $sealed['path'],
            'filename' => $sealed['filename'],
            'count' => $count,
            'scope' => $scope,
        ];
    }

    /**
     * @param  self::SCOPE_*  $expectedScope
     * @return array{created: int, updated: int, skipped: int, media: int, products: int}
     */
    public function importFromPath(string $archivePath, string $expectedScope = self::SCOPE_FULL): array
    {
        $this->codec->prepareLargeArchiveOperation();

        $expectedScope = $this->assertScope($expectedScope);
        $expectedFormat = self::formatForScope($expectedScope);
        $pack = $this->codec->unsealToExtracted($archivePath);

        try {
            $manifestPath = $pack['extracted'].'/manifest.json';
            $catalogPath = $pack['extracted'].'/catalog.json';

            if (! is_file($manifestPath) || ! is_file($catalogPath)) {
                throw ValidationException::withMessages([
                    'archive' => [__('agricart.portable.import_invalid')],
                ]);
            }

            /** @var array<string, mixed> $manifest */
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

            if (($manifest['format'] ?? null) !== $expectedFormat) {
                throw ValidationException::withMessages([
                    'archive' => [__('agricart.portable.import_format')],
                ]);
            }

            /** @var array<string, mixed> $bundle */
            $bundle = json_decode((string) file_get_contents($catalogPath), true, 512, JSON_THROW_ON_ERROR);

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $media = 0;
            $productWrites = 0;

            $includeAll = $expectedScope === self::SCOPE_FULL;

            DB::transaction(function () use (
                $bundle,
                $pack,
                $includeAll,
                $expectedScope,
                &$created,
                &$updated,
                &$skipped,
                &$media,
                &$productWrites,
            ): void {
                if ($includeAll || $expectedScope === self::SCOPE_UNITS) {
                    foreach (is_array($bundle['units'] ?? null) ? $bundle['units'] : [] as $row) {
                        $this->upsertSimpleMaster(Unit::class, is_array($row) ? $row : [], $created, $updated, $skipped);
                    }
                }

                if ($includeAll || $expectedScope === self::SCOPE_PACKING_UNITS) {
                    foreach (is_array($bundle['packing_units'] ?? null) ? $bundle['packing_units'] : [] as $row) {
                        $this->upsertSimpleMaster(PackingUnit::class, is_array($row) ? $row : [], $created, $updated, $skipped);
                    }
                }

                if ($includeAll || $expectedScope === self::SCOPE_ATTRIBUTES) {
                    foreach (is_array($bundle['attributes'] ?? null) ? $bundle['attributes'] : [] as $row) {
                        $this->upsertSimpleMaster(CatalogAttribute::class, is_array($row) ? $row : [], $created, $updated, $skipped);
                    }
                }

                if ($includeAll || $expectedScope === self::SCOPE_CONTROLS) {
                    $this->importControls(
                        is_array($bundle['controls'] ?? null) ? $bundle['controls'] : [],
                        $created,
                        $updated,
                        $skipped,
                    );
                }

                if ($includeAll || $expectedScope === self::SCOPE_BRANDS) {
                    $this->importBrands(
                        is_array($bundle['brands'] ?? null) ? $bundle['brands'] : [],
                        $pack['extracted'],
                        $created,
                        $updated,
                        $skipped,
                        $media,
                    );
                }

                if ($includeAll || $expectedScope === self::SCOPE_CATEGORIES) {
                    $this->importCategories(
                        is_array($bundle['categories'] ?? null) ? $bundle['categories'] : [],
                        $pack['extracted'],
                        $created,
                        $updated,
                        $skipped,
                        $media,
                    );
                }

                if ($includeAll) {
                    $this->importProducts(
                        is_array($bundle['products'] ?? null) ? $bundle['products'] : [],
                        $pack['extracted'],
                        $created,
                        $updated,
                        $skipped,
                        $media,
                        $productWrites,
                    );
                }
            });

            CatalogCache::forgetOverviewStats();

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'media' => $media,
                'products' => $productWrites,
            ];
        } finally {
            File::deleteDirectory($pack['work_dir']);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportBrands(string $workDir): array
    {
        $brands = [];

        foreach (Brand::query()->orderBy('id')->get() as $brand) {
            $key = $this->entityKey($brand->code, $brand->slug, $brand->id);
            $brands[] = [
                'code' => $brand->code,
                'slug' => $brand->slug,
                'status' => $brand->status,
                'inactive_reason_en' => $brand->inactive_reason_en,
                'inactive_reason_ur' => $brand->inactive_reason_ur,
                'agricart_rating' => $brand->agricart_rating,
                'agricart_rating_criteria' => $brand->agricart_rating_criteria,
                'agricart_rating_reason_en' => $brand->agricart_rating_reason_en,
                'agricart_rating_reason_ur' => $brand->agricart_rating_reason_ur,
                'agricart_rating_revision_en' => $brand->agricart_rating_revision_en,
                'agricart_rating_revision_ur' => $brand->agricart_rating_revision_ur,
                'name_en' => $brand->name_en,
                'name_ur' => $brand->name_ur,
                'description_en' => $brand->description_en,
                'description_ur' => $brand->description_ur,
                'website_url' => $brand->website_url,
                'ai_status' => $brand->ai_status?->value ?? 'none',
                'media' => [
                    'image' => $this->codec->copyMediaIntoArchive($workDir, 'brand-'.$key, 'image', $brand->image_path),
                ],
            ];
        }

        return $brands;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportCategories(string $workDir): array
    {
        $categories = [];

        Category::query()
            ->with(['displayInCategories:id,code,slug', 'parent:id,code,slug'])
            ->orderBy('id')
            ->chunkById(250, function ($chunk) use (&$categories, $workDir): void {
                foreach ($chunk as $category) {
                    /** @var Category $category */
                    $key = $this->entityKey($category->code, $category->slug, $category->id);
                    $parent = $category->parent;

                    $categories[] = [
                        'code' => $category->code,
                        'slug' => $category->slug,
                        'status' => $category->status,
                        'name_en' => $category->name_en,
                        'name_ur' => $category->name_ur,
                        'parent_code' => $parent?->code,
                        'parent_slug' => $parent?->slug,
                        'short_description_en' => $category->short_description_en,
                        'short_description_ur' => $category->short_description_ur,
                        'full_description_en' => $category->full_description_en,
                        'full_description_ur' => $category->full_description_ur,
                        'usage_guidelines_en' => $category->usage_guidelines_en,
                        'usage_guidelines_ur' => $category->usage_guidelines_ur,
                        'benefits_en' => $category->benefits_en,
                        'benefits_ur' => $category->benefits_ur,
                        'warnings_en' => $category->warnings_en,
                        'warnings_ur' => $category->warnings_ur,
                        'buying_guide_en' => $category->buying_guide_en,
                        'buying_guide_ur' => $category->buying_guide_ur,
                        'seo_title_en' => $category->seo_title_en,
                        'seo_title_ur' => $category->seo_title_ur,
                        'seo_description_en' => $category->seo_description_en,
                        'seo_description_ur' => $category->seo_description_ur,
                        'seo_keywords_en' => $category->seo_keywords_en,
                        'seo_keywords_ur' => $category->seo_keywords_ur,
                        'seo_focus_keyword_en' => $category->seo_focus_keyword_en,
                        'seo_focus_keyword_ur' => $category->seo_focus_keyword_ur,
                        'search_terms' => $category->search_terms ?? [],
                        'hs_code' => $category->hs_code,
                        'customs_notes_en' => $category->customs_notes_en,
                        'customs_notes_ur' => $category->customs_notes_ur,
                        'import_notes_en' => $category->import_notes_en,
                        'import_notes_ur' => $category->import_notes_ur,
                        'export_notes_en' => $category->export_notes_en,
                        'export_notes_ur' => $category->export_notes_ur,
                        'visual_mapping' => $category->visual_mapping ?? [],
                        'visual_map_code' => $category->visual_map_code,
                        'faqs' => $category->faqs ?? [],
                        'ai_status' => $category->ai_status?->value ?? 'none',
                        'display_categories' => $category->displayInCategories
                            ->map(static function (Category $linked): array {
                                return [
                                    'code' => $linked->code,
                                    'slug' => $linked->slug,
                                ];
                            })
                            ->values()
                            ->all(),
                        'media' => [
                            'image' => $this->codec->copyMediaIntoArchive($workDir, 'category-'.$key, 'image', $category->image_path),
                        ],
                    ];
                }
            });

        return $categories;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exportProducts(string $workDir): array
    {
        $products = [];

        foreach (
            Product::query()
                ->with([
                    'category:id,code,slug',
                    'brand:id,code,slug',
                    'unit:id,code,slug',
                    'packUnit:id,code,slug',
                    'displayCategories:id,code,slug',
                    'attributes:id,code,slug',
                    'controls:id,code,slug',
                    'deliveryMethods:id,slug',
                ])
                ->orderBy('id')
                ->get() as $product
        ) {
            $key = $this->entityKey($product->code, $product->slug, $product->id);
            $gallery = [];
            foreach (array_values($product->gallery_images ?? []) as $index => $path) {
                $copied = $this->codec->copyMediaIntoArchive($workDir, 'product-'.$key, 'gallery-'.$index, $path);
                if ($copied !== null) {
                    $gallery[] = $copied;
                }
            }

            $products[] = [
                'code' => $product->code,
                'slug' => $product->slug,
                'status' => $product->status,
                'name_en' => $product->name_en,
                'name_ur' => $product->name_ur,
                'sku' => $product->sku,
                'category_code' => $product->category?->code,
                'category_slug' => $product->category?->slug,
                'brand_code' => $product->brand?->code,
                'brand_slug' => $product->brand?->slug,
                'unit_code' => $product->unit?->code,
                'unit_slug' => $product->unit?->slug,
                'pack_unit_code' => $product->packUnit?->code,
                'pack_unit_slug' => $product->packUnit?->slug,
                'unit_qty' => $product->unit_qty,
                'pack_qty' => $product->pack_qty,
                'required_qty' => $product->required_qty,
                'alert_qty' => $product->alert_qty,
                'retail_price' => $product->retail_price,
                'wholesale_price' => $product->wholesale_price,
                'super_wholesale_price' => $product->super_wholesale_price,
                'distributor_price' => $product->distributor_price,
                'price_tier_quantities' => $product->price_tier_quantities ?? [],
                'channel_prices' => $product->channel_prices ?? [],
                'short_description_en' => $product->short_description_en,
                'short_description_ur' => $product->short_description_ur,
                'full_description_en' => $product->full_description_en,
                'full_description_ur' => $product->full_description_ur,
                'usage_guidelines_en' => $product->usage_guidelines_en,
                'usage_guidelines_ur' => $product->usage_guidelines_ur,
                'benefits_en' => $product->benefits_en,
                'benefits_ur' => $product->benefits_ur,
                'warnings_en' => $product->warnings_en,
                'warnings_ur' => $product->warnings_ur,
                'buying_guide_en' => $product->buying_guide_en,
                'buying_guide_ur' => $product->buying_guide_ur,
                'seo_title_en' => $product->seo_title_en,
                'seo_title_ur' => $product->seo_title_ur,
                'seo_description_en' => $product->seo_description_en,
                'seo_description_ur' => $product->seo_description_ur,
                'seo_keywords_en' => $product->seo_keywords_en,
                'seo_keywords_ur' => $product->seo_keywords_ur,
                'seo_focus_keyword_en' => $product->seo_focus_keyword_en,
                'seo_focus_keyword_ur' => $product->seo_focus_keyword_ur,
                'search_terms' => $product->search_terms ?? [],
                'hs_code' => $product->hs_code,
                'customs_notes_en' => $product->customs_notes_en,
                'customs_notes_ur' => $product->customs_notes_ur,
                'import_notes_en' => $product->import_notes_en,
                'import_notes_ur' => $product->import_notes_ur,
                'export_notes_en' => $product->export_notes_en,
                'export_notes_ur' => $product->export_notes_ur,
                'label_code' => $product->label_code,
                'category_visual_map_code' => $product->category_visual_map_code,
                'label_sequence' => $product->label_sequence,
                'label_qr' => $product->label_qr,
                'label_barcode' => $product->label_barcode,
                'ai_status' => $product->ai_status?->value ?? 'none',
                'display_categories' => $product->displayCategories->map(static fn (Category $c): array => [
                    'code' => $c->code,
                    'slug' => $c->slug,
                ])->values()->all(),
                'attributes' => $product->attributes->map(static fn (CatalogAttribute $a): array => [
                    'code' => $a->code,
                    'slug' => $a->slug,
                    'value' => (string) ($a->pivot->value ?? ''),
                ])->values()->all(),
                'controls' => $product->controls->map(static fn (CatalogControl $c): array => [
                    'code' => $c->code,
                    'slug' => $c->slug,
                ])->values()->all(),
                'delivery_methods' => $product->deliveryMethods->map(static fn (DeliveryMethod $d): array => [
                    'slug' => $d->slug,
                ])->values()->all(),
                'media' => [
                    'image' => $this->codec->copyMediaIntoArchive($workDir, 'product-'.$key, 'image', $product->image_path),
                    'gallery' => $gallery,
                ],
            ];
        }

        return $products;
    }

    /**
     * @param  list<mixed>  $rows
     */
    private function importControls(array $rows, int &$created, int &$updated, int &$skipped): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skipped++;

                continue;
            }

            $data = $this->masterPayload($row, [
                'value_en' => (string) ($row['value_en'] ?? $row['name_en'] ?? 'Control'),
                'value_ur' => (string) ($row['value_ur'] ?? $row['name_ur'] ?? ''),
            ]);

            if ($data === null) {
                $skipped++;

                continue;
            }

            $existing = $this->findByCodeOrSlug(CatalogControl::class, $row);

            if ($existing === null) {
                CatalogControl::query()->create($data);
                $created++;
            } else {
                $existing->update($data);
                $updated++;
            }
        }
    }

    /**
     * @param  list<mixed>  $rows
     */
    private function importBrands(
        array $rows,
        string $extracted,
        int &$created,
        int &$updated,
        int &$skipped,
        int &$media,
    ): void {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skipped++;

                continue;
            }

            $existing = $this->findByCodeOrSlug(Brand::class, $row);
            $mediaMap = is_array($row['media'] ?? null) ? $row['media'] : [];
            $imagePath = $existing?->image_path;
            $restored = $this->codec->restoreMediaFile($extracted, $mediaMap['image'] ?? null, 'brands');
            if ($restored !== null) {
                $imagePath = $restored;
                $media++;
            }

            $rating = $row['agricart_rating'] ?? null;
            $rating = is_numeric($rating) ? max(0, min(10, (int) $rating)) : null;
            $criteria = BrandAgricartReviewCriteria::forStorage(
                is_array($row['agricart_rating_criteria'] ?? null) ? $row['agricart_rating_criteria'] : null,
            );

            if ($criteria !== null) {
                $rating = BrandAgricartReviewCriteria::score($criteria);
                $composed = BrandAgricartReviewCriteria::composeReview($criteria);
            } else {
                $composed = ['en' => null, 'ur' => null];
            }

            $data = $this->masterPayload($row, [
                'description_en' => $row['description_en'] ?? null,
                'description_ur' => $row['description_ur'] ?? null,
                'website_url' => $row['website_url'] ?? null,
                'inactive_reason_en' => $row['inactive_reason_en'] ?? null,
                'inactive_reason_ur' => $row['inactive_reason_ur'] ?? null,
                'agricart_rating' => $rating,
                'agricart_rating_criteria' => $criteria,
                'agricart_rating_reason_en' => $rating === null ? null : ($composed['en'] ?? $row['agricart_rating_reason_en'] ?? null),
                'agricart_rating_reason_ur' => $rating === null ? null : ($composed['ur'] ?? $row['agricart_rating_reason_ur'] ?? null),
                'agricart_rating_revision_en' => $rating === null ? null : ($row['agricart_rating_revision_en'] ?? null),
                'agricart_rating_revision_ur' => $rating === null ? null : ($row['agricart_rating_revision_ur'] ?? null),
                'image_path' => $imagePath ?: 'brands/placeholder.webp',
            ]);

            if ($data === null) {
                $skipped++;

                continue;
            }

            if (($imagePath === null || $imagePath === 'brands/placeholder.webp') && ! Storage::disk('public')->exists('brands/placeholder.webp')) {
                Storage::disk('public')->put('brands/placeholder.webp', '');
            }

            if ($existing === null) {
                Brand::query()->create($data);
                $created++;
            } else {
                $existing->update($data);
                $updated++;
            }
        }
    }

    /**
     * @param  list<mixed>  $rows
     */
    private function importCategories(
        array $rows,
        string $extracted,
        int &$created,
        int &$updated,
        int &$skipped,
        int &$media,
    ): void {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skipped++;

                continue;
            }

            $existing = $this->findByCodeOrSlug(Category::class, $row);
            $mediaMap = is_array($row['media'] ?? null) ? $row['media'] : [];
            $imagePath = $existing?->image_path;
            $restored = $this->codec->restoreMediaFile($extracted, $mediaMap['image'] ?? null, 'categories');
            if ($restored !== null) {
                $imagePath = $restored;
                $media++;
            }

            $data = $this->masterPayload($row, [
                'parent_id' => null,
                'short_description_en' => $row['short_description_en'] ?? null,
                'short_description_ur' => $row['short_description_ur'] ?? null,
                'full_description_en' => $row['full_description_en'] ?? null,
                'full_description_ur' => $row['full_description_ur'] ?? null,
                'usage_guidelines_en' => $row['usage_guidelines_en'] ?? null,
                'usage_guidelines_ur' => $row['usage_guidelines_ur'] ?? null,
                'benefits_en' => $row['benefits_en'] ?? null,
                'benefits_ur' => $row['benefits_ur'] ?? null,
                'warnings_en' => $row['warnings_en'] ?? null,
                'warnings_ur' => $row['warnings_ur'] ?? null,
                'buying_guide_en' => $row['buying_guide_en'] ?? null,
                'buying_guide_ur' => $row['buying_guide_ur'] ?? null,
                'seo_title_en' => $row['seo_title_en'] ?? null,
                'seo_title_ur' => $row['seo_title_ur'] ?? null,
                'seo_description_en' => $row['seo_description_en'] ?? null,
                'seo_description_ur' => $row['seo_description_ur'] ?? null,
                'seo_keywords_en' => $row['seo_keywords_en'] ?? null,
                'seo_keywords_ur' => $row['seo_keywords_ur'] ?? null,
                'seo_focus_keyword_en' => $row['seo_focus_keyword_en'] ?? null,
                'seo_focus_keyword_ur' => $row['seo_focus_keyword_ur'] ?? null,
                'search_terms' => is_array($row['search_terms'] ?? null) ? $row['search_terms'] : [],
                'hs_code' => $row['hs_code'] ?? null,
                'customs_notes_en' => $row['customs_notes_en'] ?? null,
                'customs_notes_ur' => $row['customs_notes_ur'] ?? null,
                'import_notes_en' => $row['import_notes_en'] ?? null,
                'import_notes_ur' => $row['import_notes_ur'] ?? null,
                'export_notes_en' => $row['export_notes_en'] ?? null,
                'export_notes_ur' => $row['export_notes_ur'] ?? null,
                'visual_mapping' => is_array($row['visual_mapping'] ?? null) ? $row['visual_mapping'] : [],
                'visual_map_code' => $row['visual_map_code'] ?? null,
                'faqs' => is_array($row['faqs'] ?? null) ? $row['faqs'] : [],
                'image_path' => $imagePath,
            ]);

            if ($data === null) {
                $skipped++;

                continue;
            }

            if ($existing === null) {
                Category::query()->create($data);
                $created++;
            } else {
                $existing->update($data);
                $updated++;
            }
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $category = $this->findByCodeOrSlug(Category::class, $row);
            if ($category === null) {
                continue;
            }

            $parent = $this->findByCodeOrSlug(Category::class, [
                'code' => $row['parent_code'] ?? null,
                'slug' => $row['parent_slug'] ?? null,
            ]);

            if ($parent !== null && (int) $parent->id !== (int) $category->id) {
                $category->update(['parent_id' => $parent->id]);
            }

            $displayIds = [];
            foreach (is_array($row['display_categories'] ?? null) ? $row['display_categories'] : [] as $ref) {
                if (! is_array($ref)) {
                    continue;
                }
                $display = $this->findByCodeOrSlug(Category::class, $ref);
                if ($display !== null) {
                    $displayIds[] = $display->id;
                }
            }
            $category->displayInCategories()->sync(array_values(array_unique($displayIds)));
        }
    }

    /**
     * @param  list<mixed>  $rows
     */
    private function importProducts(
        array $rows,
        string $extracted,
        int &$created,
        int &$updated,
        int &$skipped,
        int &$media,
        int &$productWrites,
    ): void {
        foreach ($rows as $row) {
            if (! is_array($row) || (! filled($row['code'] ?? null) && ! filled($row['slug'] ?? null))) {
                $skipped++;

                continue;
            }

            $existing = $this->findByCodeOrSlug(Product::class, $row);
            $mediaMap = is_array($row['media'] ?? null) ? $row['media'] : [];
            $imagePath = $existing?->image_path;
            $restored = $this->codec->restoreMediaFile($extracted, $mediaMap['image'] ?? null, 'products');
            if ($restored !== null) {
                $imagePath = $restored;
                $media++;
            }

            $gallery = $existing?->gallery_images ?? [];
            if (is_array($mediaMap['gallery'] ?? null)) {
                $gallery = [];
                foreach ($mediaMap['gallery'] as $relative) {
                    $path = $this->codec->restoreMediaFile($extracted, is_string($relative) ? $relative : null, 'products/gallery');
                    if ($path !== null) {
                        $gallery[] = $path;
                        $media++;
                    }
                }
            }

            $category = $this->findByCodeOrSlug(Category::class, [
                'code' => $row['category_code'] ?? null,
                'slug' => $row['category_slug'] ?? null,
            ]);
            $brand = $this->findByCodeOrSlug(Brand::class, [
                'code' => $row['brand_code'] ?? null,
                'slug' => $row['brand_slug'] ?? null,
            ]);
            $unit = $this->findByCodeOrSlug(Unit::class, [
                'code' => $row['unit_code'] ?? null,
                'slug' => $row['unit_slug'] ?? null,
            ]);
            $packUnit = $this->findByCodeOrSlug(PackingUnit::class, [
                'code' => $row['pack_unit_code'] ?? null,
                'slug' => $row['pack_unit_slug'] ?? null,
            ]);

            $code = filled($row['code'] ?? null)
                ? (string) $row['code']
                : ($existing?->code ?: 'PRD-'.Str::upper(Str::random(6)));
            $slug = filled($row['slug'] ?? null)
                ? (string) $row['slug']
                : ($existing?->slug ?: Str::slug((string) ($row['name_en'] ?? $code)));

            $payload = [
                'code' => $code,
                'slug' => $slug,
                'status' => in_array(($row['status'] ?? 'active'), ['active', 'inactive', 'draft'], true)
                    ? $row['status']
                    : 'active',
                'name_en' => $row['name_en'] ?? $existing?->name_en ?? $code,
                'name_ur' => $row['name_ur'] ?? $existing?->name_ur ?? '',
                'sku' => $row['sku'] ?? null,
                'category_id' => $category?->id,
                'brand_id' => $brand?->id,
                'unit_id' => $unit?->id,
                'pack_unit_id' => $packUnit?->id,
                'unit_qty' => $row['unit_qty'] ?? null,
                'pack_qty' => $row['pack_qty'] ?? null,
                'required_qty' => $row['required_qty'] ?? null,
                'alert_qty' => $row['alert_qty'] ?? null,
                'retail_price' => $row['retail_price'] ?? null,
                'wholesale_price' => $row['wholesale_price'] ?? null,
                'super_wholesale_price' => $row['super_wholesale_price'] ?? null,
                'distributor_price' => $row['distributor_price'] ?? null,
                'price_tier_quantities' => is_array($row['price_tier_quantities'] ?? null) ? $row['price_tier_quantities'] : [],
                'channel_prices' => is_array($row['channel_prices'] ?? null) ? $row['channel_prices'] : [],
                'short_description_en' => $row['short_description_en'] ?? null,
                'short_description_ur' => $row['short_description_ur'] ?? null,
                'full_description_en' => $row['full_description_en'] ?? null,
                'full_description_ur' => $row['full_description_ur'] ?? null,
                'usage_guidelines_en' => $row['usage_guidelines_en'] ?? null,
                'usage_guidelines_ur' => $row['usage_guidelines_ur'] ?? null,
                'benefits_en' => $row['benefits_en'] ?? null,
                'benefits_ur' => $row['benefits_ur'] ?? null,
                'warnings_en' => $row['warnings_en'] ?? null,
                'warnings_ur' => $row['warnings_ur'] ?? null,
                'buying_guide_en' => $row['buying_guide_en'] ?? null,
                'buying_guide_ur' => $row['buying_guide_ur'] ?? null,
                'seo_title_en' => $row['seo_title_en'] ?? null,
                'seo_title_ur' => $row['seo_title_ur'] ?? null,
                'seo_description_en' => $row['seo_description_en'] ?? null,
                'seo_description_ur' => $row['seo_description_ur'] ?? null,
                'seo_keywords_en' => $row['seo_keywords_en'] ?? null,
                'seo_keywords_ur' => $row['seo_keywords_ur'] ?? null,
                'seo_focus_keyword_en' => $row['seo_focus_keyword_en'] ?? null,
                'seo_focus_keyword_ur' => $row['seo_focus_keyword_ur'] ?? null,
                'search_terms' => is_array($row['search_terms'] ?? null) ? $row['search_terms'] : [],
                'hs_code' => $row['hs_code'] ?? null,
                'customs_notes_en' => $row['customs_notes_en'] ?? null,
                'customs_notes_ur' => $row['customs_notes_ur'] ?? null,
                'import_notes_en' => $row['import_notes_en'] ?? null,
                'import_notes_ur' => $row['import_notes_ur'] ?? null,
                'export_notes_en' => $row['export_notes_en'] ?? null,
                'export_notes_ur' => $row['export_notes_ur'] ?? null,
                'label_code' => $row['label_code'] ?? $existing?->label_code,
                'category_visual_map_code' => $row['category_visual_map_code'] ?? $existing?->category_visual_map_code,
                'label_sequence' => $row['label_sequence'] ?? $existing?->label_sequence,
                'label_qr' => is_array($row['label_qr'] ?? null) ? $row['label_qr'] : $existing?->label_qr,
                'label_barcode' => is_array($row['label_barcode'] ?? null) ? $row['label_barcode'] : $existing?->label_barcode,
                'ai_status' => $row['ai_status'] ?? 'none',
                'image_path' => $imagePath,
                'gallery_images' => $gallery,
            ];

            if ($existing === null) {
                $payload['stock_qty'] = 0;
                $product = Product::query()->create($payload);
                $created++;
            } else {
                $existing->update($payload);
                $product = $existing->fresh();
                $updated++;
            }

            $displayIds = [];
            foreach (is_array($row['display_categories'] ?? null) ? $row['display_categories'] : [] as $ref) {
                if (! is_array($ref)) {
                    continue;
                }
                $display = $this->findByCodeOrSlug(Category::class, $ref);
                if ($display !== null && (int) $display->id !== (int) ($product->category_id ?? 0)) {
                    $displayIds[] = $display->id;
                }
            }
            $product->displayCategories()->sync(array_values(array_unique($displayIds)));

            $attributeSync = [];
            foreach (is_array($row['attributes'] ?? null) ? $row['attributes'] : [] as $ref) {
                if (! is_array($ref)) {
                    continue;
                }
                $attribute = $this->findByCodeOrSlug(CatalogAttribute::class, $ref);
                if ($attribute !== null && filled($ref['value'] ?? null)) {
                    $attributeSync[$attribute->id] = ['value' => (string) $ref['value']];
                }
            }
            $product->attributes()->sync($attributeSync);

            $controlIds = [];
            foreach (is_array($row['controls'] ?? null) ? $row['controls'] : [] as $ref) {
                if (! is_array($ref)) {
                    continue;
                }
                $control = $this->findByCodeOrSlug(CatalogControl::class, $ref);
                if ($control !== null) {
                    $controlIds[] = $control->id;
                }
            }
            $product->controls()->sync(array_values(array_unique($controlIds)));

            $deliveryIds = [];
            foreach (is_array($row['delivery_methods'] ?? null) ? $row['delivery_methods'] : [] as $ref) {
                $slug = is_array($ref) ? ($ref['slug'] ?? null) : null;
                if (! filled($slug)) {
                    continue;
                }
                $method = DeliveryMethod::query()->where('slug', $slug)->first();
                if ($method !== null) {
                    $deliveryIds[] = $method->id;
                }
            }
            $product->deliveryMethods()->sync(array_values(array_unique($deliveryIds)));

            $productWrites++;
        }
    }

    /**
     * @param  Unit|PackingUnit|CatalogAttribute|CatalogControl  $row
     * @return array<string, mixed>
     */
    private function exportSimpleMaster(object $row): array
    {
        return [
            'code' => $row->code,
            'slug' => $row->slug,
            'status' => $row->status,
            'name_en' => $row->name_en,
            'name_ur' => $row->name_ur,
            'abbreviation_en' => $row->abbreviation_en ?? null,
            'abbreviation_ur' => $row->abbreviation_ur ?? null,
            'ai_status' => $row->ai_status?->value ?? 'none',
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $row
     */
    private function upsertSimpleMaster(string $modelClass, array $row, int &$created, int &$updated, int &$skipped): void
    {
        $data = $this->masterPayload($row, array_filter([
            'abbreviation_en' => $row['abbreviation_en'] ?? null,
            'abbreviation_ur' => $row['abbreviation_ur'] ?? null,
        ], static fn ($v) => $v !== null));

        if ($data === null) {
            $skipped++;

            return;
        }

        $byCode = filled($row['code'] ?? null)
            ? $modelClass::query()->where('code', $row['code'])->first()
            : null;
        $bySlug = filled($data['slug'] ?? null)
            ? $modelClass::query()->where('slug', $data['slug'])->first()
            : null;

        // Prefer slug identity (same named master) when code was remapped between environments.
        $existing = $bySlug ?? $byCode;

        if ($existing === null) {
            $this->releaseSimpleMasterKeys($modelClass, (string) $data['code'], (string) $data['slug'], null);
            $modelClass::query()->create($data);
            $created++;

            return;
        }

        if ($bySlug !== null && $byCode !== null && (int) $bySlug->id !== (int) $byCode->id) {
            // Code belongs to a different row — free it so this slug-matched row can take the imported code.
            $byCode->update([
                'code' => $this->temporaryUniqueCode($modelClass, (string) $byCode->code, (int) $byCode->id),
            ]);
        }

        $this->releaseSimpleMasterKeys(
            $modelClass,
            (string) $data['code'],
            (string) $data['slug'],
            (int) $existing->id,
        );
        $existing->update($data);
        $updated++;
    }

    /**
     * Free unique code/slug held by other rows before create/update.
     *
     * @param  class-string  $modelClass
     */
    private function releaseSimpleMasterKeys(string $modelClass, string $code, string $slug, ?int $exceptId): void
    {
        $codeConflict = $modelClass::query()
            ->where('code', $code)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->first();

        if ($codeConflict !== null) {
            $codeConflict->update([
                'code' => $this->temporaryUniqueCode($modelClass, $code, (int) $codeConflict->id),
            ]);
        }

        $slugConflict = $modelClass::query()
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->first();

        if ($slugConflict !== null) {
            $slugConflict->update([
                'slug' => $this->temporaryUniqueSlug($modelClass, $slug, (int) $slugConflict->id),
            ]);
        }
    }

    /**
     * @param  class-string  $modelClass
     */
    private function temporaryUniqueCode(string $modelClass, string $code, int $id): string
    {
        $base = mb_substr($code.'-old-'.$id, 0, 32);
        $candidate = $base;
        $n = 1;

        while ($modelClass::query()->where('code', $candidate)->where('id', '!=', $id)->exists()) {
            $candidate = mb_substr($base.'-'.$n, 0, 32);
            $n++;
        }

        return $candidate;
    }

    /**
     * @param  class-string  $modelClass
     */
    private function temporaryUniqueSlug(string $modelClass, string $slug, int $id): string
    {
        $base = Str::slug($slug.'-old-'.$id) ?: 'item-old-'.$id;
        $candidate = $base;
        $n = 1;

        while ($modelClass::query()->where('slug', $candidate)->where('id', '!=', $id)->exists()) {
            $candidate = $base.'-'.$n;
            $n++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>|null
     */
    private function masterPayload(array $row, array $extra = []): ?array
    {
        if (! filled($row['code'] ?? null) && ! filled($row['slug'] ?? null) && ! filled($row['name_en'] ?? null)) {
            return null;
        }

        $nameEn = (string) ($row['name_en'] ?? $row['code'] ?? $row['slug'] ?? 'item');
        $slug = filled($row['slug'] ?? null) ? (string) $row['slug'] : Str::slug($nameEn);
        $code = filled($row['code'] ?? null) ? (string) $row['code'] : $slug;

        return [
            'code' => $code,
            'slug' => $slug !== '' ? $slug : Str::slug($code),
            'status' => in_array(($row['status'] ?? 'active'), ['active', 'inactive', 'draft'], true)
                ? $row['status']
                : 'active',
            'name_en' => $nameEn,
            'name_ur' => $row['name_ur'] ?? '',
            'ai_status' => $row['ai_status'] ?? 'none',
            ...$extra,
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $row
     */
    private function findByCodeOrSlug(string $modelClass, array $row): mixed
    {
        if (filled($row['slug'] ?? null)) {
            $found = $modelClass::query()->where('slug', $row['slug'])->first();
            if ($found !== null) {
                return $found;
            }
        }

        if (filled($row['code'] ?? null)) {
            return $modelClass::query()->where('code', $row['code'])->first();
        }

        return null;
    }

    private function entityKey(?string $code, ?string $slug, int $id): string
    {
        $raw = filled($code) ? $code : (filled($slug) ? $slug : 'id-'.$id);

        return Str::slug($raw, '-') ?: 'id-'.$id;
    }

    /**
     * @return self::SCOPE_*
     */
    private function assertScope(string $scope): string
    {
        if (! in_array($scope, self::SCOPES, true)) {
            throw ValidationException::withMessages([
                'archive' => [__('agricart.portable.import_invalid')],
            ]);
        }

        return $scope;
    }
}
