@php
    $breadcrumbs = \App\Filament\Layout\BreadcrumbResolver::resolve();
@endphp

<div
    id="agricart-topbar-breadcrumbs-slot"
    class="agricart-topbar-breadcrumbs-ctn"
    x-data
    x-on:livewire:navigated.window="
        const source = document.getElementById('agricart-breadcrumbs-source');
        const slot = document.getElementById('agricart-topbar-breadcrumbs-slot');
        if (source && slot) {
            slot.innerHTML = source.innerHTML;
        }
    "
>
    @include('filament.components.breadcrumbs-nav', ['breadcrumbs' => $breadcrumbs])
</div>
