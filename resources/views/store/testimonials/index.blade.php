@extends('store.layouts.app')

@section('title', __('agricart.storefront.testimonials.page_title'))
@section('seo_description', __('agricart.storefront.testimonials.page_lead'))
@section('canonical', route('store.testimonials'))
@section('robots', 'index,follow,max-image-preview:large')
@push('structured-data')
    <script type="application/ld+json">{!! app(\App\Services\Store\StorefrontSchemaService::class)->toJson($schemaDocument) !!}</script>
@endpush

@section('content')
    <section class="ag-store-testimonials">
        <header class="ag-store-testimonials__header">
            <p class="ag-store-testimonials__eyebrow">{{ __('agricart.storefront.testimonials.eyebrow') }}</p>
            <h1 class="ag-store-testimonials__title">{{ __('agricart.storefront.testimonials.heading') }}</h1>
            <p class="ag-store-testimonials__lead">{{ __('agricart.storefront.testimonials.page_lead') }}</p>

            @if (($summary['count'] ?? 0) > 0)
                <div class="ag-store-testimonials__score">
                    <strong>{{ number_format((float) $summary['average'], 1) }}</strong>
                    <span>/ 5</span>
                    <span class="ag-store-testimonials__count">
                        {{ trans_choice('agricart.storefront.testimonials.review_count', $summary['count'], ['count' => $summary['count']]) }}
                    </span>
                </div>
            @endif

            <div class="ag-store-testimonials__actions">
                @auth('customer')
                    <a href="{{ route('store.customer.testimonials.create') }}" class="ag-store-btn ag-store-btn--primary">
                        {{ ($myReview ?? null) ? __('agricart.storefront.testimonials.edit_cta') : __('agricart.storefront.testimonials.write_cta') }}
                    </a>
                @else
                    <a href="{{ route('store.customer.login', ['redirect' => route('store.customer.testimonials.create', absolute: false)]) }}" class="ag-store-btn ag-store-btn--primary">
                        {{ __('agricart.storefront.testimonials.write_cta') }}
                    </a>
                @endauth
            </div>
        </header>

        @if (session('customer_auth_status'))
            <div class="ag-store-alert ag-store-alert--success" role="status">{{ session('customer_auth_status') }}</div>
        @endif

        @if ($testimonials->isEmpty())
            <p class="ag-store-testimonials__empty">{{ __('agricart.storefront.testimonials.empty') }}</p>
        @else
            <div class="ag-store-testimonials__grid">
                @foreach ($testimonials as $item)
                    <article class="ag-store-testimonials__card" wire:key="public-testimonial-{{ $item->id }}">
                        <div class="ag-store-testimonials__card-top">
                            <div class="ag-store-testimonials__stars" aria-label="{{ $item->rating }} / 5">
                                @for ($star = 1; $star <= 5; $star++)
                                    <span class="{{ $star <= $item->rating ? 'is-on' : '' }}">★</span>
                                @endfor
                            </div>
                            @if ($item->is_featured)
                                <span class="ag-store-testimonials__badge">{{ __('agricart.storefront.testimonials.featured') }}</span>
                            @endif
                        </div>
                        @if (filled($item->title))
                            <h2 class="ag-store-testimonials__card-title">{{ $item->title }}</h2>
                        @endif
                        <p class="ag-store-testimonials__card-body">{{ $item->body }}</p>
                        <footer class="ag-store-testimonials__card-meta">
                            <span class="ag-store-testimonials__author">
                                @if ($item->publicPhotoUrl())
                                    <img src="{{ $item->publicPhotoUrl() }}" alt="" class="ag-store-testimonials__avatar" width="32" height="32" loading="lazy" />
                                @endif
                                <strong>{{ $item->publicName() }}</strong>
                            </span>
                            <time datetime="{{ $item->created_at?->toDateString() }}">{{ $item->created_at?->format('M Y') }}</time>
                        </footer>
                        @if (filled($item->admin_reply))
                            <div class="ag-store-testimonials__reply">
                                <strong>{{ __('agricart.storefront.testimonials.agricart_reply') }}</strong>
                                <p>{{ $item->admin_reply }}</p>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
