<?php

namespace App\Livewire\PriceTags;

use App\Models\Product;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use App\Services\Purchasing\PriceTagService;
use App\Services\Purchasing\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPriceTags extends Component
{
    use WithPagination;

    /** @var list<array<string, mixed>> */
    public array $lines = [];

    public string $labelProfileKey = '';

    public string $locale = 'en';

    public string $codeType = 'barcode';

    public bool $showStoreName = true;

    public bool $showProductName = true;

    public bool $showCostPrice = false;

    public bool $showSalePrice = true;

    public string $search = '';

    public string $readinessFilter = '';

    public string $productQuery = '';

    public string $invoiceNumber = '';

    public bool $showPrintPanel = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $default = app(PrintProfileService::class)->defaultPriceLabel();
        $this->labelProfileKey = $default?->key ?? 'label_38x50';
    }

    #[On('price-tags-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('price-tags-action')]
    public function handleToolbarAction(string $action): void
    {
        if ($action === 'print') {
            $this->openPrintPanel();
        }
    }

    #[On('price-tags-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'readiness' && in_array($value, ['ready', 'missing_sale_price', 'missing_barcode'], true)) {
            $this->readinessFilter = $value;
            $this->resetPage();
        }
    }

    #[On('price-tags-clear-filters')]
    public function clearFilters(): void
    {
        $this->readinessFilter = '';
        $this->resetPage();
    }

    public function removeReadinessFilter(): void
    {
        $this->readinessFilter = '';
        $this->resetPage();
    }

    public function openPrintPanel(): void
    {
        $this->showPrintPanel = true;
    }

    public function closePrintPanel(): void
    {
        $this->showPrintPanel = false;
    }

    public function updatedProductQuery(): void
    {
        if (mb_strlen(trim($this->productQuery)) < 2) {
            return;
        }
    }

    public function addProduct(int $productId): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        $this->lines = app(PriceTagService::class)
            ->mergeLine(collect($this->lines), app(PriceTagService::class)->makeLine($product))
            ->all();

        $this->productQuery = '';
        $this->showPrintPanel = true;
        $this->setStatus(__('price_tags.messages.product_added'), 'success');
    }

    public function scanProduct(): void
    {
        if (! $this->userCan('create') || trim($this->productQuery) === '') {
            return;
        }

        $product = app(PurchaseService::class)->findProductByScan($this->productQuery);

        if ($product === null) {
            $this->setStatus(__('price_tags.messages.product_not_found'), 'warning');

            return;
        }

        $this->addProduct($product->id);
    }

    public function loadFromInvoice(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        try {
            $invoiceLines = app(PriceTagService::class)->linesFromInvoice($this->invoiceNumber);
            $merged = collect($this->lines);

            foreach ($invoiceLines as $line) {
                $merged = app(PriceTagService::class)->mergeLine($merged, $line);
            }

            $this->lines = $merged->all();
            $this->invoiceNumber = '';
            $this->showPrintPanel = true;
            $this->setStatus(__('price_tags.messages.invoice_loaded', ['count' => count($invoiceLines)]), 'success');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus((string) $message, 'warning');
        }
    }

    public function removeLine(string $lineId): void
    {
        $this->lines = array_values(array_filter(
            $this->lines,
            fn (array $line): bool => (string) ($line['line_id'] ?? '') !== $lineId,
        ));
    }

    public function clearLines(): void
    {
        $this->lines = [];
    }

    public function printBatch(): void
    {
        if ($this->filteredLines()->isEmpty()) {
            $this->setStatus(__('price_tags.messages.empty_queue'), 'warning');

            return;
        }

        $token = Str::random(40);

        Cache::put('price_tag_print:' . $token, $this->printPayload(), now()->addMinutes(15));

        $url = route('admin.price-tags.print', [
            'token' => $token,
            'autoprint' => 1,
        ]);

        $this->dispatch('price-tags-open-print', url: $url);
        $this->showPrintPanel = false;
    }

    public function printLine(string $lineId): void
    {
        $line = collect($this->lines)->first(
            fn (array $row): bool => (string) ($row['line_id'] ?? '') === $lineId,
        );

        if ($line === null) {
            return;
        }

        $token = Str::random(40);

        Cache::put('price_tag_print:' . $token, [
            ...$this->printPayload(),
            'lines' => [[
                'product_id' => (int) ($line['product_id'] ?? 0),
                'qty' => (int) ($line['qty'] ?? 1),
                'cost_price' => (string) ($line['cost_price'] ?? ''),
                'sale_price' => (string) ($line['sale_price'] ?? ''),
            ]],
        ], now()->addMinutes(15));

        $url = route('admin.price-tags.print', [
            'token' => $token,
            'autoprint' => 1,
        ]);

        $this->dispatch('price-tags-open-print', url: $url);
    }

    public function printProduct(int $productId): void
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        $line = app(PriceTagService::class)->makeLine($product);
        $token = Str::random(40);

        Cache::put('price_tag_print:' . $token, [
            ...$this->printPayload(),
            'lines' => [[
                'product_id' => $product->id,
                'qty' => 1,
                'cost_price' => (string) ($line['cost_price'] ?? ''),
                'sale_price' => (string) ($line['sale_price'] ?? ''),
            ]],
        ], now()->addMinutes(15));

        $url = route('admin.price-tags.print', [
            'token' => $token,
            'autoprint' => 1,
        ]);

        $this->dispatch('price-tags-open-print', url: $url);
    }

    /**
     * @return array<string, mixed>
     */
    public function displayOptions(): array
    {
        return [
            'locale' => $this->locale,
            'code_type' => $this->codeType,
            'show_store_name' => $this->showStoreName,
            'show_product_name' => $this->showProductName,
            'show_cost_price' => $this->showCostPrice,
            'show_sale_price' => $this->showSalePrice,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tagPreviewForProduct(Product $product): array
    {
        return app(PriceTagService::class)->tagData(
            $product,
            $this->locale,
            app(PurchaseService::class)->previousPurchasePrice($product->id),
            (string) ($product->retail_price ?? ''),
        );
    }

    public function tagPreview(array $line): array
    {
        $product = $this->lineProduct($line);

        if ($product === null) {
            return [];
        }

        return app(PriceTagService::class)->tagData(
            $product,
            $this->locale,
            (string) ($line['cost_price'] ?? ''),
            (string) ($line['sale_price'] ?? ''),
        );
    }

    public function render(): View
    {
        $productResults = [];

        if (mb_strlen(trim($this->productQuery)) >= 2) {
            $productResults = app(PurchaseService::class)->searchProducts(
                $this->productQuery,
                app()->getLocale(),
                8,
            );
        }

        $products = Product::query()
            ->where('status', 'active')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('label_code', 'like', $needle)
                        ->orWhere('code', 'like', $needle)
                        ->orWhere('sku', 'like', $needle);
                });
            })
            ->when($this->readinessFilter === 'ready', function ($query): void {
                $query->whereNotNull('retail_price')
                    ->where('retail_price', '!=', '')
                    ->where(function ($inner): void {
                        $inner->where(function ($code): void {
                            $code->whereNotNull('label_code')->where('label_code', '!=', '');
                        })->orWhere(function ($sku): void {
                            $sku->whereNotNull('sku')->where('sku', '!=', '');
                        });
                    });
            })
            ->when($this->readinessFilter === 'missing_sale_price', function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNull('retail_price')->orWhere('retail_price', '');
                });
            })
            ->when($this->readinessFilter === 'missing_barcode', function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where(function ($code): void {
                        $code->whereNull('label_code')->orWhere('label_code', '');
                    })->where(function ($sku): void {
                        $sku->whereNull('sku')->orWhere('sku', '');
                    });
                });
            })
            ->orderBy('name_en')
            ->paginate(12);

        return view('livewire.price-tags.list', [
            'products' => $products,
            'filteredLines' => $this->filteredLinesWithIndex(),
            'labelProfiles' => app(PrintProfileService::class)->priceLabels(),
            'productResults' => $productResults,
            'canCreate' => $this->userCan('create'),
            'queueCount' => count($this->lines),
            'readinessFilterLabel' => match ($this->readinessFilter) {
                'ready' => __('price_tags.filters.ready_to_label'),
                'missing_sale_price' => __('price_tags.filters.missing_sale_price'),
                'missing_barcode' => __('price_tags.filters.missing_barcode'),
                default => null,
            },
            'hasListConstraints' => filled($this->search) || filled($this->readinessFilter),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredLinesWithIndex(): Collection
    {
        return collect($this->lines)
            ->values()
            ->map(function (array $line, int $index): array {
                return [
                    ...$line,
                    '_index' => $index,
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredLines(): Collection
    {
        return collect($this->lines)->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function printPayload(): array
    {
        return [
            'profile_key' => $this->labelProfileKey,
            'locale' => $this->locale,
            'code_type' => $this->codeType,
            'show_store_name' => $this->showStoreName,
            'show_product_name' => $this->showProductName,
            'show_cost_price' => $this->showCostPrice,
            'show_sale_price' => $this->showSalePrice,
            'lines' => collect($this->lines)->map(fn (array $line): array => [
                'product_id' => (int) ($line['product_id'] ?? 0),
                'qty' => (int) ($line['qty'] ?? 1),
                'cost_price' => (string) ($line['cost_price'] ?? ''),
                'sale_price' => (string) ($line['sale_price'] ?? ''),
            ])->all(),
        ];
    }

    private function lineProduct(array $line): ?Product
    {
        if (($line['product'] ?? null) instanceof Product) {
            return $line['product'];
        }

        $productId = (int) ($line['product_id'] ?? 0);

        if ($productId <= 0) {
            return null;
        }

        return Product::query()->find($productId);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'price-tags', $action);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
