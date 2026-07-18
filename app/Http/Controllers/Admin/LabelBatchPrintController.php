<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintProfile;
use App\Models\Product;
use App\Models\User;
use App\Services\Printing\LabelRenderService;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LabelBatchPrintController extends Controller
{
    private const MAX_TOTAL_COPIES = 500;

    private const VARIANTS = [
        'qr_en' => ['type' => 'qr', 'locale' => 'en'],
        'barcode_en' => ['type' => 'barcode', 'locale' => 'en'],
        'qr_ur' => ['type' => 'qr', 'locale' => 'ur'],
        'barcode_ur' => ['type' => 'barcode', 'locale' => 'ur'],
    ];

    private const FIELDS = [
        'store_name',
        'product_name',
        'system_code',
        'brand',
        'unit',
        'packing_unit',
        'attributes',
        'controls',
        'category',
        'required_qty',
        'reorder_qty',
        'printed_code',
    ];

    public function store(
        Request $request,
        PrintProfileService $profiles,
        LabelRenderService $labels,
    ): View {
        $this->authorizeCatalogAccess();

        $validated = $request->validate([
            'profile' => ['required', 'string', 'max:100'],
            'language' => ['required', Rule::in(['en', 'ur', 'both'])],
            'fields' => ['required', 'array', 'min:1', 'max:'.count(self::FIELDS)],
            'fields.*' => ['string', 'distinct', Rule::in(self::FIELDS)],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.enabled' => ['required', 'array', 'min:1', 'max:4'],
            'items.*.enabled.*' => ['string', Rule::in(array_keys(self::VARIANTS))],
            'items.*.copies' => ['required', 'array'],
            'items.*.copies.*' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $profile = $profiles->findByKey($validated['profile']);
        abort_unless(
            $profile instanceof PrintProfile
                && $profile->category === PrintProfile::CATEGORY_LABEL,
            404,
        );

        $items = array_values($validated['items']);

        foreach ($items as $item) {
            if (count($item['enabled']) !== count(array_unique($item['enabled']))) {
                throw ValidationException::withMessages([
                    'items' => __('agricart.labels.batch.duplicate_variants'),
                ]);
            }

            $allowedSuffixes = $validated['language'] === 'both'
                ? ['_en', '_ur']
                : ['_'.$validated['language']];

            foreach ($item['enabled'] as $variant) {
                if (! collect($allowedSuffixes)->contains(
                    fn (string $suffix): bool => str_ends_with($variant, $suffix),
                )) {
                    throw ValidationException::withMessages([
                        'items' => __('agricart.labels.batch.invalid_language_variant'),
                    ]);
                }
            }
        }

        $productIds = array_map(
            fn (array $item): int => (int) $item['product_id'],
            $items,
        );
        $products = Product::query()
            ->with(['brand', 'unit', 'packUnit', 'attributes', 'controls', 'category'])
            ->whereIn('id', $productIds)
            ->whereNotNull('label_code')
            ->get()
            ->keyBy('id');

        if ($products->count() !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'items' => __('agricart.labels.batch.invalid_products'),
            ]);
        }

        $totalCopies = collect($items)->sum(function (array $item): int {
            return collect($item['enabled'])->sum(
                fn (string $variant): int => (int) ($item['copies'][$variant] ?? 1),
            );
        });

        if ($totalCopies > self::MAX_TOTAL_COPIES) {
            throw ValidationException::withMessages([
                'items' => __('agricart.labels.batch.too_many_copies', [
                    'max' => self::MAX_TOTAL_COPIES,
                ]),
            ]);
        }

        $fields = array_values(array_unique([...$validated['fields'], 'product_name']));
        $printJobs = $this->buildPrintJobs($items, $products, $fields, $labels);

        return view('prints.label-batch', [
            'profile' => $profile,
            'printJobs' => $printJobs,
            'totalCopies' => $totalCopies,
            'autoprint' => $request->boolean('autoprint', true),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     * @param  list<string>  $fields
     * @return list<array{product: Product, type: string, locale: string, sticker: array<string, mixed>, copies: int}>
     */
    private function buildPrintJobs(
        array $items,
        Collection $products,
        array $fields,
        LabelRenderService $labels,
    ): array {
        $jobs = [];

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);

            foreach ($item['enabled'] as $variantKey) {
                $variant = self::VARIANTS[$variantKey];
                $copies = (int) ($item['copies'][$variantKey] ?? 1);

                $jobs[] = [
                    'product' => $product,
                    'type' => $variant['type'],
                    'locale' => $variant['locale'],
                    'sticker' => $labels->stickerData(
                        $product,
                        $variant['locale'],
                        $variant['type'],
                        $fields,
                    ),
                    'copies' => $copies,
                ];
            }
        }

        return $jobs;
    }

    private function authorizeCatalogAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && ($user->isSuperAdmin() || $user->hasPermission('catalog.view')),
            403,
        );
    }
}
