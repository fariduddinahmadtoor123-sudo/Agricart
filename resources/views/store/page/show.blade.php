@extends('store.layouts.app')

@section('title', $page->localizedTitle() . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', Illuminate\Support\Str::limit(trim(strip_tags($page->localizedContent() ?? '')), 160))
@section('canonical', route('store.page', $page->slug))
@push('structured-data')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('agricart.storefront.category.home'), 'item' => route('store.home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $page->localizedTitle(), 'item' => route('store.page', $page->slug)],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <article class="ag-store-page">
        <h1 class="ag-store-page-title">{{ $page->localizedTitle() }}</h1>
        <div class="ag-store-page__body">
            {!! nl2br(e($page->localizedContent() ?? '')) !!}
        </div>
    </article>
@endsection
