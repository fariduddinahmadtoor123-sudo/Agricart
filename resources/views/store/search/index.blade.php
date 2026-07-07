@extends('store.layouts.app')

@section('title', __('agricart.storefront.search.title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <section class="ag-store-search">
        <h1 class="ag-store-section-title">
            @if (filled($query))
                {{ __('agricart.storefront.search.for_query', ['query' => $query]) }}
            @else
                {{ __('agricart.storefront.search.heading') }}
            @endif
        </h1>

        @if (! filled($query))
            <div class="ag-store-empty">{{ __('agricart.storefront.search.empty_query') }}</div>
        @elseif ($products === null || $products->isEmpty())
            <div class="ag-store-empty">{{ __('agricart.storefront.search.no_results', ['query' => $query]) }}</div>
        @else
            @include('store.components.product-grid', [
                'products' => $products,
                'sectionTitle' => null,
                'emptyMessage' => __('agricart.storefront.search.no_results', ['query' => $query]),
            ])

            @if ($products->hasPages())
                <div class="ag-store-pagination">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
