<div
    class="agricart-price-tags"
    x-data="{ panelOpen: @entangle('showPrintPanel').live }"
    x-effect="document.body.classList.toggle('agricart-price-tags-panel-open', panelOpen)"
>
    @if ($statusMessage)
        <div @class([
            'agricart-purchase-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    @if (filled($readinessFilterLabel))
        <div class="agricart-list-filters">
            <button type="button" class="agricart-list-filters__chip" wire:click="removeReadinessFilter">
                <span>{{ $readinessFilterLabel }}</span>
                <span aria-hidden="true">×</span>
            </button>
            <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                {{ __('purchases.list.clear_filters') }}
            </button>
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="agricart-purchase-form__empty">
            <p>{{ ($hasListConstraints ?? false) ? __('price_tags.messages.catalog_no_results') : __('price_tags.messages.catalog_empty') }}</p>
        </div>
    @else
        <div class="agricart-price-tags__catalog">
            @foreach ($products as $product)
                @include('livewire.price-tags.partials.product-card', [
                    'product' => $product,
                    'canCreate' => $canCreate,
                ])
            @endforeach
        </div>

        <div class="agricart-categories__pagination">
            {{ $products->links() }}
        </div>
    @endif

    <template x-teleport="body">
        <div
            x-show="panelOpen"
            x-cloak
            class="agricart-price-tags__modal-overlay"
            x-on:click.self="$wire.closePrintPanel()"
            x-on:keydown.escape.window="panelOpen && $wire.closePrintPanel()"
        >
            <div class="agricart-price-tags__modal agricart-form-panel agricart-form-panel--maximized" wire:click.stop x-on:click.stop>
                @include('livewire.price-tags.partials.print-panel')
            </div>
        </div>
    </template>
</div>

@script
<script>
    Livewire.on('price-tags-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;
        if (url) window.open(url, '_blank');
    });
</script>
@endscript
