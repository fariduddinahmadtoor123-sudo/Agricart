@extends('store.layouts.app')

@section('title', __('agricart.storefront.search.title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))

@section('content')
    <section
        class="ag-store-search"
        data-store-search-page
        data-store-search-title-for="{{ e(__('agricart.storefront.search.for_query', ['query' => '__QUERY__'])) }}"
        data-store-search-title-default="{{ e(__('agricart.storefront.search.heading')) }}"
        data-store-search-empty-query="{{ e(__('agricart.storefront.search.empty_query')) }}"
    >
        <h1 class="ag-store-section-title" data-store-search-title>
            @if (filled($query))
                {{ __('agricart.storefront.search.for_query', ['query' => $query]) }}
            @else
                {{ __('agricart.storefront.search.heading') }}
            @endif
        </h1>

        <div data-store-search-results>
            @include('store.components.search-results-panel', [
                'query' => $query,
                'products' => $products,
                'categories' => $categories,
                'pages' => $pages,
            ])
        </div>
    </section>
@endsection
