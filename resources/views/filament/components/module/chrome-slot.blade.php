@php
    $activeModule = \App\Filament\Layout\ModuleChrome::active();
    $pathPrefixes = \App\Filament\Layout\ModuleChrome::pathPrefixes();
@endphp

<div
    id="agricart-module-chrome-slot"
    @class([
        'agricart-catalog-chrome-ctn',
        'hidden' => $activeModule === null,
    ])
    x-data="{
        pathPrefixes: @js($pathPrefixes),
        syncModuleChrome() {
            const slot = document.getElementById('agricart-module-chrome-slot');

            if (! slot) {
                return;
            }

            const path = window.location.pathname;
            const isModulePath = this.pathPrefixes.some((prefix) => path.includes('/' + prefix));

            if (! isModulePath) {
                slot.innerHTML = '';
                slot.classList.add('hidden');

                return;
            }

            slot.classList.remove('hidden');

            const source = document.getElementById('agricart-module-chrome-source');

            if (source && source.innerHTML.trim() !== '') {
                slot.innerHTML = source.innerHTML;

                if (window.Alpine) {
                    window.Alpine.initTree(slot);
                }
            }
        },
    }"
    x-init="$nextTick(() => syncModuleChrome())"
    x-on:livewire:navigated.window="$nextTick(() => syncModuleChrome())"
>
    {{-- Chrome HTML lives only in #agricart-module-chrome-source (breadcrumbs-source).
         Copying here once avoids rendering Tools/Catalog chrome twice per request. --}}
</div>
