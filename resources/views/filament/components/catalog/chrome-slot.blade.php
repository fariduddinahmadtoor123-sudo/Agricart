@php
    $showCatalogChrome = \App\Filament\Layout\CatalogSubNavigation::isCatalogPath();
@endphp

<div
    id="agricart-catalog-chrome-slot"
    @class([
        'agricart-catalog-chrome-ctn',
        'hidden' => ! $showCatalogChrome,
    ])
    x-data="{
        syncCatalogChrome() {
            const slot = document.getElementById('agricart-catalog-chrome-slot');

            if (! slot) {
                return;
            }

            const onCatalog = window.location.pathname.includes('/admin/catalog');

            if (! onCatalog) {
                slot.innerHTML = '';
                slot.classList.add('hidden');

                return;
            }

            slot.classList.remove('hidden');

            const source = document.getElementById('agricart-catalog-chrome-source');

            if (source && source.innerHTML.trim() !== '') {
                slot.innerHTML = source.innerHTML;
            }
        },
    }"
    x-init="$nextTick(() => syncCatalogChrome())"
    x-on:livewire:navigated.window="$nextTick(() => syncCatalogChrome())"
>
    @if ($showCatalogChrome)
        @include('filament.components.catalog.chrome')
    @endif
</div>
