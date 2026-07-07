<?php

namespace App\Livewire\ReOrder;

use App\Models\User;
use App\Services\Purchasing\ReorderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;

class ReOrderCenter extends Component
{
    public string $activeTab = 'reorder';

    public string $reorderFilter = 'available';

    public string $queueFilter = 'pending';

    public string $search = '';

    /** @var list<int> */
    public array $selectedProductIds = [];

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->syncToolbar();
    }

    #[On('reorder-refresh')]
    public function refreshData(): void
    {
        $this->selectedProductIds = array_values(array_filter(
            $this->selectedProductIds,
            fn (int $id): bool => $this->reorderCandidates()->contains(fn (array $row): bool => (int) $row['product_id'] === $id),
        ));

        $this->syncToolbar();
    }

    #[On('reorder-tab')]
    public function onToolbarTab(string $tab): void
    {
        if (in_array($tab, ['reorder', 'queue'], true)) {
            $this->activeTab = $tab;
            $this->syncToolbar();
        }
    }

    #[On('reorder-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->syncToolbar();
    }

    #[On('reorder-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$scope, $value] = explode(':', $filter, 2);

        if ($scope === 'reorder' && in_array($value, ['available', 'in_queue', 'repeated', 'all'], true)) {
            $this->activeTab = 'reorder';
            $this->reorderFilter = $value;
            $this->selectedProductIds = [];
        }

        if ($scope === 'queue' && in_array($value, ['pending', 'stale'], true)) {
            $this->activeTab = 'queue';
            $this->queueFilter = $value;
        }

        $this->syncToolbar();
    }

    #[On('reorder-clear-filters')]
    public function clearFilters(): void
    {
        $this->reorderFilter = 'available';
        $this->queueFilter = 'pending';
        $this->selectedProductIds = [];
        $this->syncToolbar();
    }

    #[On('reorder-action')]
    public function handleToolbarAction(string $action): void
    {
        match ($action) {
            'select_all' => $this->toggleSelectAll(),
            'send_to_queue' => $this->sendToQueue(),
            'print' => $this->activeTab === 'queue' ? $this->printQueue() : $this->printReorder(),
            default => null,
        };
    }

    public function updatedReorderFilter(): void
    {
        $this->selectedProductIds = [];
        $this->syncToolbar();
    }

    public function updatedQueueFilter(): void
    {
        $this->syncToolbar();
    }

    public function toggleProduct(int $productId): void
    {
        if (in_array($productId, $this->selectedProductIds, true)) {
            $this->selectedProductIds = array_values(array_filter(
                $this->selectedProductIds,
                fn (int $id): bool => $id !== $productId,
            ));
        } else {
            $this->selectedProductIds[] = $productId;
        }

        $this->syncToolbar();
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->filteredReorderRows()
            ->filter(fn (array $row): bool => ! $row['in_queue'])
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->selectedProductIds = count($ids) === count($this->selectedProductIds) ? [] : $ids;
        $this->syncToolbar();
    }

    public function sendToQueue(): void
    {
        if (! $this->userCan('create')) {
            return;
        }

        if ($this->selectedProductIds === []) {
            $this->setStatus(__('reorder.messages.select_items'), 'warning');

            return;
        }

        $batch = app(ReorderService::class)->sendToQueue($this->selectedProductIds);

        if ($batch === null) {
            $this->setStatus(__('reorder.messages.nothing_to_send'), 'warning');

            return;
        }

        $count = $batch->items()->count();
        $this->selectedProductIds = [];
        $this->setStatus(__('reorder.messages.sent_to_queue', [
            'count' => $count,
            'number' => $batch->batch_number,
        ]), 'success');
        $this->activeTab = 'queue';
        $this->syncToolbar();
    }

    public function printReorder(): void
    {
        $ids = $this->selectedProductIds !== []
            ? implode(',', $this->selectedProductIds)
            : '';

        $url = route('admin.reorder.print', array_filter([
            'scope' => 'reorder',
            'filter' => $this->reorderFilter,
            'products' => $ids !== '' ? $ids : null,
        ]));

        $this->dispatch('reorder-open-print', url: $url);
    }

    public function printQueue(): void
    {
        $url = route('admin.reorder.print', [
            'scope' => 'queue',
            'filter' => $this->queueFilter,
        ]);

        $this->dispatch('reorder-open-print', url: $url);
    }

    public function imageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function productName(?\App\Models\Product $product): string
    {
        if ($product === null) {
            return '—';
        }

        return app(ReorderService::class)->productDisplayName($product, app()->getLocale());
    }

    public function render(): View
    {
        return view('livewire.re-order.re-order-center', [
            'reorderRows' => $this->filteredReorderRows(),
            'queueRows' => $this->filteredQueueRows(),
            'canCreate' => $this->userCan('create'),
            'pendingQueueCount' => app(ReorderService::class)->queueItems('pending')->count(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function filteredReorderRows(): Collection
    {
        $rows = $this->reorderCandidates();

        if ($this->search === '') {
            return $rows;
        }

        $needle = mb_strtolower(trim($this->search));

        return $rows
            ->filter(fn (array $row): bool => str_contains(mb_strtolower((string) $row['name']), $needle))
            ->values();
    }

    /**
     * @return Collection<int, \App\Models\ReorderQueueItem>
     */
    private function filteredQueueRows(): Collection
    {
        $rows = $this->queueItems();

        if ($this->search === '') {
            return $rows;
        }

        $needle = mb_strtolower(trim($this->search));
        $service = app(ReorderService::class);

        return $rows
            ->filter(function ($item) use ($needle, $service): bool {
                $name = $item->product
                    ? $service->productDisplayName($item->product, app()->getLocale())
                    : '';

                return str_contains(mb_strtolower($name), $needle);
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function reorderCandidates(): Collection
    {
        return app(ReorderService::class)->reorderCandidates(app()->getLocale(), $this->reorderFilter);
    }

    /**
     * @return Collection<int, \App\Models\ReorderQueueItem>
     */
    private function queueItems(): Collection
    {
        return app(ReorderService::class)->queueItems($this->queueFilter);
    }

    private function syncToolbar(): void
    {
        $service = app(ReorderService::class);
        $encoded = $this->orderTotalEncoded($service);

        $this->dispatch('reorder-order-code', encoded: $encoded);

        $this->dispatch(
            'reorder-toolbar-sync',
            activeTab: $this->activeTab,
            queueCount: $service->queueItems('pending')->count(),
        );
    }

    private function orderTotalEncoded(?ReorderService $service = null): string
    {
        $service ??= app(ReorderService::class);
        $summary = $this->currentOrderSummary($service);

        return $summary['encoded'] !== '' ? $summary['encoded'] : '—';
    }

    /**
     * @return array{
     *     line_count: int,
     *     qty_total: float,
     *     total: float,
     *     formatted: string,
     *     encoded: string
     * }
     */
    private function currentOrderSummary(ReorderService $service): array
    {
        if ($this->activeTab === 'queue') {
            return $service->summarizeOrderValue($this->filteredQueueRows());
        }

        $rows = $this->filteredReorderRows();

        if ($this->selectedProductIds !== []) {
            $rows = $rows->filter(
                fn (array $row): bool => in_array((int) $row['product_id'], $this->selectedProductIds, true),
            )->values();
        }

        return $service->summarizeOrderValue($rows);
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

        return $user->hasPermission('purchasing.' . $action);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
