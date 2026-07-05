@if (filled($breadcrumbs))
    <nav class="agricart-topbar-breadcrumbs" aria-label="{{ __('agricart.breadcrumbs.aria') }}">
        <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" />
    </nav>
@endif
