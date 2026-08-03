<?php

namespace App\Livewire\PriceControls;

use App\Models\PriceChannelTier;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\PriceControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class ListPriceControls extends Component
{
    /** @var list<string> */
    public array $visibleColumns = ['name', 'markup_percent', 'product_field', 'example_price', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('price-controls-saved')]
    public function refreshList(): void
    {
        // List is rebuilt on each render.
    }

    #[On('price-controls-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('price-controls-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status') {
            $this->statusFilter = $value;
        }
    }

    #[On('price-controls-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
    }

    #[On('price-controls-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $col): bool => $col !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['name'];
        }

        $this->dispatch('price-controls-columns-sync', columns: $this->visibleColumns);
    }

    public function openEdit(int $tierId): void
    {
        $tier = PriceChannelTier::query()->find($tierId);

        if ($tier === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('price_controls.actions.edit', ['name' => app(ChannelPricingEngine::class)->tierPriceLabel($tier->tier_key)]),
            resource: __('agricart.pages.marketplace.price-control'),
            formKey: 'price-controls',
            tierId: $tierId,
            mode: 'edit',
        );
    }

    public function deleteRecord(int $tierId): void
    {
        $tier = PriceChannelTier::query()->find($tierId);

        if ($tier === null) {
            return;
        }

        try {
            app(PriceControlService::class)->deleteTier($tier);
        } catch (ValidationException $exception) {
            session()->flash('price_control_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        $engine = app(ChannelPricingEngine::class);
        $profile = app(PriceControlService::class)->ensureDefaultProfile();
        $exampleBase = $engine->examplePurchasePrice();

        /** @var Collection<int, PriceChannelTier> $tiers */
        $tiers = $engine->listTiers()
            ->when(filled($this->search), function (Collection $collection) use ($engine): Collection {
                $needle = mb_strtolower($this->search);

                return $collection->filter(function (PriceChannelTier $tier) use ($needle): bool {
                    return str_contains(mb_strtolower($tier->name_en), $needle)
                        || str_contains(mb_strtolower((string) $tier->name_ur), $needle)
                        || str_contains(mb_strtolower($tier->tier_key), $needle);
                })->values();
            })
            ->when(filled($this->statusFilter), fn (Collection $collection): Collection => $collection
                ->filter(fn (PriceChannelTier $tier): bool => $tier->status === $this->statusFilter)
                ->values());

        return view('livewire.price-controls.list', [
            'tiers' => $tiers,
            'engine' => $engine,
            'exampleBase' => $exampleBase,
        ]);
    }
}
