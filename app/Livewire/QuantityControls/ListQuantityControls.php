<?php

namespace App\Livewire\QuantityControls;

use App\Models\QuantityChannelTier;
use App\Services\PricingControls\QuantityControlService;
use App\Services\PricingControls\QuantityPricingEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class ListQuantityControls extends Component
{
    /** @var list<string> */
    public array $visibleColumns = ['name', 'min_quantity', 'price_field', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('quantity-controls-saved')]
    public function refreshList(): void
    {
        // List is rebuilt on each render.
    }

    #[On('quantity-controls-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    #[On('quantity-controls-filter')]
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

    #[On('quantity-controls-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
    }

    #[On('quantity-controls-column-toggle')]
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

        $this->dispatch('quantity-controls-columns-sync', columns: $this->visibleColumns);
    }

    public function openEdit(int $tierId): void
    {
        $tier = QuantityChannelTier::query()->find($tierId);

        if ($tier === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('quantity_controls.actions.edit', ['name' => $tier->localizedName()]),
            resource: __('agricart.pages.marketplace.quantity-control'),
            formKey: 'quantity-controls',
            tierId: $tierId,
            mode: 'edit',
        );
    }

    public function deleteRecord(int $tierId): void
    {
        $tier = QuantityChannelTier::query()->find($tierId);

        if ($tier === null) {
            return;
        }

        try {
            app(QuantityControlService::class)->deleteTier($tier);
        } catch (ValidationException $exception) {
            session()->flash('quantity_control_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        $engine = app(QuantityPricingEngine::class);
        $profile = app(QuantityControlService::class)->ensureDefaultProfile();

        /** @var Collection<int, QuantityChannelTier> $tiers */
        $tiers = $engine->sortTiers($profile->tiers)
            ->when(filled($this->search), function (Collection $collection): Collection {
                $needle = mb_strtolower($this->search);

                return $collection->filter(function (QuantityChannelTier $tier) use ($needle): bool {
                    return str_contains(mb_strtolower($tier->name_en), $needle)
                        || str_contains(mb_strtolower((string) $tier->name_ur), $needle)
                        || str_contains(mb_strtolower($tier->tier_key), $needle);
                })->values();
            })
            ->when(filled($this->statusFilter), fn (Collection $collection): Collection => $collection
                ->filter(fn (QuantityChannelTier $tier): bool => $tier->status === $this->statusFilter)
                ->values());

        return view('livewire.quantity-controls.list', [
            'tiers' => $tiers,
            'engine' => $engine,
        ]);
    }
}
