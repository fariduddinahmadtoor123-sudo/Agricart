<?php

namespace App\Livewire\PriceUpdates;

use App\Filament\Forms\CategoryForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\PriceControlService;
use App\Services\PricingControls\ProductPriceUpdateService;
use App\Services\PricingControls\QuantityControlService;
use App\Services\Purchasing\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Sheet extends Component
{
    private const CATEGORY_LOAD_LIMIT = 500;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $productQuery = '';

    public string $categoryQuery = '';

    public string $printLocale = 'en';

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public bool $saving = false;

    public function mount(): void
    {
        app(PriceControlService::class)->ensureDefaultProfile();
        app(QuantityControlService::class)->ensureDefaultProfile();
    }

    public function openForm(?int $productId = null): void
    {
        $this->rows = [];
        $this->productQuery = '';
        $this->categoryQuery = '';
        $this->statusMessage = null;
        $this->statusType = 'success';

        app(PriceControlService::class)->ensureDefaultProfile();
        app(QuantityControlService::class)->ensureDefaultProfile();

        if ($productId !== null) {
            $this->addProduct($productId);
        }
    }

    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'clear' => $this->clearRows(),
            'print' => $this->printBlankBuySheet(),
            default => null,
        };
    }

    #[On('price-update-action')]
    public function handleToolbarAction(string $action): void
    {
        match ($action) {
            'save' => $this->saveAll(),
            'clear' => $this->clearRows(),
            'print' => $this->printBlankBuySheet(),
            default => null,
        };
    }

    public function printBlankBuySheet(): void
    {
        if (! $this->userCan('view') && ! $this->userCan('edit')) {
            return;
        }

        if ($this->rows === []) {
            $this->setStatus(__('price_updates.print.empty_sheet'), 'warning');

            return;
        }

        $locale = in_array($this->printLocale, ['en', 'ur', 'both'], true)
            ? $this->printLocale
            : 'en';

        $token = Str::random(40);
        Cache::put('price_update_buy_print:'.$token, [
            'locale' => $locale,
            'rows' => collect($this->rows)
                ->values()
                ->map(static fn (array $row): array => [
                    'name' => (string) ($row['name'] ?? ''),
                    'name_ur' => (string) ($row['name_ur'] ?? ''),
                    'code' => (string) ($row['code'] ?? ''),
                    'thumb_url' => $row['thumb_url'] ?? null,
                ])
                ->all(),
        ], now()->addMinutes(30));

        $url = route('admin.price-updates.buy-sheet.print', [
            'token' => $token,
            'autoprint' => 1,
        ]);

        $this->dispatch('price-update-open-print', url: $url);
        $this->setStatus(__('price_updates.print.opened'), 'success');
    }

    public function saveAndClose(): void
    {
        if ($this->saveAll()) {
            $this->dispatch('price-updates-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function addProduct(int $productId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        if ($this->rowIndexForProduct($product->id) !== null) {
            $this->setStatus(__('price_updates.messages.already_on_sheet'), 'warning');

            return;
        }

        $this->rows[] = $this->makeRow($product);
        $this->productQuery = '';
        $this->setStatus(__('price_updates.messages.product_added'), 'success');
    }

    public function addProductsFromCategory(int $categoryId): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $category = Category::query()->find($categoryId);

        if ($category === null) {
            $this->setStatus(__('price_updates.messages.category_not_found'), 'warning');

            return;
        }

        $categoryIds = $this->selfAndDescendantCategoryIds($categoryId);

        $existingIds = collect($this->rows)
            ->map(fn (array $row): int => (int) ($row['product_id'] ?? 0))
            ->filter(fn (int $id): bool => $id > 0)
            ->all();

        $products = Product::query()
            ->whereIn('category_id', $categoryIds)
            ->orderBy('name_en')
            ->limit(self::CATEGORY_LOAD_LIMIT + 1)
            ->get();

        $truncated = $products->count() > self::CATEGORY_LOAD_LIMIT;
        $products = $products->take(self::CATEGORY_LOAD_LIMIT);

        $added = 0;
        $skipped = 0;

        foreach ($products as $product) {
            if (in_array($product->id, $existingIds, true)) {
                $skipped++;

                continue;
            }

            $this->rows[] = $this->makeRow($product);
            $existingIds[] = $product->id;
            $added++;
        }

        $this->categoryQuery = '';

        if ($added === 0 && $skipped === 0) {
            $this->setStatus(__('price_updates.messages.category_empty', [
                'category' => $category->name_en,
            ]), 'warning');

            return;
        }

        $message = __('price_updates.messages.category_loaded', [
            'category' => $category->name_en,
            'added' => $added,
            'skipped' => $skipped,
        ]);

        if ($truncated) {
            $message .= ' '.__('price_updates.messages.category_truncated', [
                'limit' => self::CATEGORY_LOAD_LIMIT,
            ]);
        }

        $this->setStatus($message, $added > 0 ? 'success' : 'warning');
    }

    public function scanOrAddProduct(): void
    {
        if (! $this->userCan('edit') || trim($this->productQuery) === '') {
            return;
        }

        $product = app(PurchaseService::class)->findProductByScan($this->productQuery);

        if ($product === null) {
            $product = Product::query()
                ->where(function ($query): void {
                    $needle = '%'.trim($this->productQuery).'%';
                    $query->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('code', 'like', $needle)
                        ->orWhere('sku', 'like', $needle);
                })
                ->orderBy('name_en')
                ->first();
        }

        if ($product === null) {
            $this->setStatus(__('price_updates.messages.product_not_found'), 'warning');

            return;
        }

        $this->addProduct($product->id);
    }

    public function removeRow(string $rowId): void
    {
        $this->rows = array_values(array_filter(
            $this->rows,
            fn (array $row): bool => (string) ($row['row_id'] ?? '') !== $rowId,
        ));
    }

    public function clearRows(): void
    {
        $this->rows = [];
        $this->setStatus(__('price_updates.messages.cleared'), 'success');
    }

    public function updatedRows(mixed $value, ?string $key): void
    {
        if ($key === null || ! str_contains($key, '.')) {
            return;
        }

        [$index, $field] = explode('.', $key, 2);

        if (! is_numeric($index) || ! isset($this->rows[(int) $index])) {
            return;
        }

        if ($field !== 'last_buy_price') {
            return;
        }

        // Buy price drives all sell prices: recalculate every tier/channel from markups.
        $this->recalculateRow((int) $index, force: true);
    }

    /**
     * Apply Price Channel markups to empty price cells (or all cells when $force).
     */
    public function recalculateRow(int $index, bool $force = false): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $buy = $this->numeric($this->rows[$index]['last_buy_price'] ?? null);

        if ($buy === null || $buy <= 0) {
            return;
        }

        $prices = app(ProductPriceUpdateService::class)->pricesFromBuyPrice($buy);

        foreach (['retail_price', 'wholesale_price', 'super_wholesale_price', 'distributor_price'] as $field) {
            $suggested = $prices[$field] ?? '';

            if ($force || $this->isBlankPrice($this->rows[$index][$field] ?? null)) {
                $this->rows[$index][$field] = $suggested;
            }
        }

        $existingChannel = is_array($this->rows[$index]['channel_prices'] ?? null)
            ? $this->rows[$index]['channel_prices']
            : [];
        $suggestedChannel = is_array($prices['channel_prices'] ?? null) ? $prices['channel_prices'] : [];

        if ($force) {
            // Only overwrite channel prices when the profile actually has channel tiers to
            // recompute from. Otherwise keep any existing channel prices instead of wiping them.
            if ($suggestedChannel !== []) {
                $this->rows[$index]['channel_prices'] = $suggestedChannel;
            }
        } else {
            foreach ($suggestedChannel as $key => $suggested) {
                if ($this->isBlankPrice($existingChannel[$key] ?? null)) {
                    $existingChannel[$key] = $suggested;
                }
            }
            $this->rows[$index]['channel_prices'] = $existingChannel;
        }
    }

    public function applyMarkups(int $index): void
    {
        $this->recalculateRow($index, force: true);
    }

    public function saveAll(): bool
    {
        if (! $this->userCan('edit')) {
            $this->setStatus(__('price_updates.messages.permission_denied'), 'error');
            $this->dispatchBusyState();

            return false;
        }

        if ($this->rows === []) {
            $this->setStatus(__('price_updates.messages.empty_sheet'), 'warning');

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        $saved = 0;
        $skipped = 0;
        $failed = 0;
        $failedNames = [];

        try {
            $service = app(ProductPriceUpdateService::class);

            foreach ($this->rows as $index => $row) {
                $productId = (int) ($row['product_id'] ?? 0);
                $product = Product::query()->find($productId);

                if ($product === null) {
                    continue;
                }

                // Skip products the user left completely blank — saving the whole
                // category should only touch rows that actually have price input.
                if (! $this->rowHasPriceInput($row)) {
                    $skipped++;

                    continue;
                }

                try {
                    $service->updateProductPricing($product, [
                        'last_buy_price' => $row['last_buy_price'] ?? null,
                        'retail_price' => $row['retail_price'] ?? null,
                        'wholesale_price' => $row['wholesale_price'] ?? null,
                        'super_wholesale_price' => $row['super_wholesale_price'] ?? null,
                        'distributor_price' => $row['distributor_price'] ?? null,
                        'channel_prices' => is_array($row['channel_prices'] ?? null) ? $row['channel_prices'] : [],
                        'price_tier_quantities' => is_array($row['price_tier_quantities'] ?? null) ? $row['price_tier_quantities'] : [],
                    ]);

                    $this->rows[$index] = $this->makeRow($product->fresh());
                    $saved++;
                } catch (ValidationException $exception) {
                    // One bad row must never abort the whole batch — record it and continue.
                    $failed++;
                    $failedNames[] = $product->name_en;
                }
            }

            if ($saved === 0 && $failed === 0) {
                $this->setStatus(__('price_updates.messages.nothing_entered'), 'warning');

                return false;
            }

            if ($failed > 0) {
                $this->setStatus(__('price_updates.messages.saved_partial', [
                    'saved' => $saved,
                    'failed' => $failed,
                    'names' => $this->summariseNames($failedNames),
                ]), $saved > 0 ? 'warning' : 'error');

                $this->dispatch('price-updates-saved');

                return false;
            }

            $this->setStatus(__('price_updates.messages.saved', ['count' => $saved]), 'success');
            $this->dispatch('price-updates-saved');

            return true;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowHasPriceInput(array $row): bool
    {
        if (! $this->isBlankPrice($row['last_buy_price'] ?? null)) {
            return true;
        }

        foreach (['retail_price', 'wholesale_price', 'super_wholesale_price', 'distributor_price'] as $field) {
            if (! $this->isBlankPrice($row[$field] ?? null)) {
                return true;
            }
        }

        $channel = is_array($row['channel_prices'] ?? null) ? $row['channel_prices'] : [];

        foreach ($channel as $value) {
            if (! $this->isBlankPrice($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $names
     */
    private function summariseNames(array $names): string
    {
        $names = array_values(array_filter($names, static fn (string $name): bool => trim($name) !== ''));

        if ($names === []) {
            return '';
        }

        $shown = array_slice($names, 0, 5);
        $summary = implode(', ', $shown);

        if (count($names) > count($shown)) {
            $summary .= ' …';
        }

        return $summary;
    }

    public function render(): View
    {
        $engine = app(ChannelPricingEngine::class);

        return view('livewire.price-updates.sheet', [
            'canEdit' => $this->userCan('edit'),
            'canView' => $this->userCan('view'),
            'productResults' => $this->productResults(),
            'categoryResults' => $this->categoryResults(),
            'tierColumns' => $engine->activeTiers()->map(function ($tier) use ($engine) {
                $headerKey = $this->headerKeyForTier($tier);
                $priceKey = 'price_updates.headers.'.$headerKey;
                $qtyKey = 'price_updates.headers.'.$headerKey.'_qty';

                return [
                    'tier_key' => (string) $tier->tier_key,
                    'price_field' => $tier->product_field,
                    'label' => Lang::has($priceKey)
                        ? __($priceKey)
                        : $engine->tierPriceLabel($headerKey),
                    'qty_label' => Lang::has($qtyKey)
                        ? __($qtyKey)
                        : __('price_updates.headers.qty'),
                    'markup' => rtrim(rtrim(number_format((float) $tier->markup_percent, 3, '.', ''), '0'), '.'),
                ];
            })->values()->all(),
        ]);
    }

    private function headerKeyForTier(\App\Models\PriceChannelTier $tier): string
    {
        return match ((string) $tier->product_field) {
            'retail_price' => 'retail',
            'wholesale_price' => 'wholesale',
            'super_wholesale_price' => 'super_wholesale',
            'distributor_price' => 'distributor',
            default => (string) $tier->tier_key,
        };
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function categoryResults(): array
    {
        $labels = CategoryForm::searchCategoryLabels($this->categoryQuery, limit: 12);

        return collect($labels)
            ->map(fn (string $label, int|string $id): array => [
                'id' => (int) $id,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    /**
     * Category + all nested children (any depth).
     *
     * @return list<int>
     */
    private function selfAndDescendantCategoryIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $frontier = [$categoryId];

        while ($frontier !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $new = array_values(array_diff($children, $ids));
            $ids = array_merge($ids, $new);
            $frontier = $new;
        }

        return array_values(array_unique($ids));
    }

    private function rowIndexForProduct(int $productId): ?int
    {
        foreach ($this->rows as $index => $row) {
            if ((int) ($row['product_id'] ?? 0) === $productId) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * @return list<array{id: int, label: string, image_url: ?string}>
     */
    private function productResults(): array
    {
        $needle = trim($this->productQuery);

        if (mb_strlen($needle) < 1) {
            return [];
        }

        return Product::query()
            ->where(function ($query) use ($needle): void {
                $like = '%'.$needle.'%';
                $query->where('name_en', 'like', $like)
                    ->orWhere('name_ur', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('sku', 'like', $like);
            })
            ->orderBy('name_en')
            ->limit(12)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'label' => trim($product->name_en.(filled($product->code) ? ' · '.$product->code : '')),
                'image_url' => filled($product->image_path)
                    ? Storage::disk('public')->url($product->image_path)
                    : null,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function makeRow(Product $product): array
    {
        $service = app(ProductPriceUpdateService::class);
        // Channel defaults + this product's saved quantity overrides.
        $quantities = $service->quantitiesForPriceUpdateSheet($product);

        return [
            'row_id' => 'pu-'.$product->id.'-'.uniqid(),
            'product_id' => $product->id,
            'name' => $product->name_en,
            'name_ur' => (string) ($product->name_ur ?? ''),
            'code' => (string) ($product->code ?? ''),
            'sku' => (string) ($product->sku ?? ''),
            'thumb_url' => filled($product->image_path)
                ? Storage::disk('public')->url($product->image_path)
                : null,
            'last_buy_price' => $this->displayMoney($product->last_buy_price),
            'retail_price' => (string) ($product->retail_price ?? ''),
            'wholesale_price' => (string) ($product->wholesale_price ?? ''),
            'super_wholesale_price' => (string) ($product->super_wholesale_price ?? ''),
            'distributor_price' => (string) ($product->distributor_price ?? ''),
            'channel_prices' => is_array($product->channel_prices) ? $product->channel_prices : [],
            'price_tier_quantities' => $quantities,
        ];
    }

    private function displayMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;

        if (abs($number - round($number)) < 0.00001) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
    }

    private function isBlankPrice(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function numeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '', trim($value));
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('price-update-form-busy', saving: $this->saving);
    }

    private function userCan(string $action): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('purchasing.'.$action);
    }
}
