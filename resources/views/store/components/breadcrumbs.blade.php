@php
    $crumbs = $crumbs ?? [];
@endphp

@if ($crumbs !== [])
    <nav class="ag-store-breadcrumb ag-store-breadcrumb--compact" aria-label="{{ __('agricart.storefront.category.breadcrumb') }}">
        @foreach ($crumbs as $index => $crumb)
            @if ($index > 0)
                <span class="ag-store-breadcrumb__sep" aria-hidden="true">›</span>
            @endif
            @if (($crumb['current'] ?? false) || blank($crumb['url'] ?? null))
                <span class="ag-store-breadcrumb__current" aria-current="page">{{ $crumb['name'] }}</span>
            @else
                <a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a>
            @endif
        @endforeach
    </nav>
@endif
