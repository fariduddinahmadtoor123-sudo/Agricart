@extends('store.layouts.app')

@section('title', __('agricart.storefront.brands.page_title') . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('seo_description', __('agricart.storefront.brands.page_lead'))
@section('canonical', route('store.brands'))
@section('robots', 'index,follow,max-image-preview:large')
@push('structured-data')
    <script type="application/ld+json">{!! app(\App\Services\Store\StorefrontSchemaService::class)->toJson($schemaDocument) !!}</script>
@endpush

@section('content')
    <section class="ag-store-brands">
        <header class="ag-store-brands__header">
            <h1 class="ag-store-brands__title">{{ __('agricart.storefront.brands.heading') }}</h1>
            <p class="ag-store-brands__lead">{{ __('agricart.storefront.brands.page_lead') }}</p>
        </header>

        @if ($brands->isEmpty())
            <p class="ag-store-brands__empty">{{ __('agricart.storefront.brands.empty') }}</p>
        @else
            <div class="ag-store-brands__grid">
                @foreach ($brands as $brand)
                    @php
                        $logoUrl = $brandService->imageUrl($brand);
                        $summary = $brandService->ratingSummary($brand);
                        $customerSummary = ($brandCustomerSummaries ?? [])[$brand->id] ?? ['average' => null, 'count' => 0];
                    @endphp
                    <a href="{{ route('store.brand', $brand->slug) }}" class="ag-store-brands__card">
                        <span class="ag-store-brands__card-logo">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="" width="80" height="80" loading="lazy" />
                            @else
                                <span aria-hidden="true">{{ strtoupper(mb_substr($brand->name_en, 0, 1)) }}</span>
                            @endif
                        </span>
                        <span class="ag-store-brands__card-body">
                            <strong class="ag-store-brands__card-name-en">{{ $brand->name_en }}</strong>
                            @if (filled($brand->name_ur))
                                <span class="ag-store-brands__card-name-ur" dir="rtl">{{ $brand->name_ur }}</span>
                            @endif
                            @include('store.components.brand-ratings-pair', [
                                'agricartSummary' => $summary,
                                'customerSummary' => $customerSummary,
                                'variant' => 'compact',
                            ])
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
