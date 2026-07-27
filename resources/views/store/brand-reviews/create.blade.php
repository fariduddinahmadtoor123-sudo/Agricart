@extends('store.layouts.app')

@section('title', __('agricart.storefront.brand_reviews.write_title', ['brand' => $brandService->localizedName($brand)]))
@section('canonical', route('store.customer.brand-reviews.create', $brand->slug))
@section('robots', 'noindex,nofollow')

@section('content')
    <nav class="ag-store-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('store.brands') }}">{{ __('agricart.storefront.brands.nav') }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('store.brand', $brand->slug) }}">{{ $brandService->localizedName($brand) }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ __('agricart.storefront.brand_reviews.write_short') }}</span>
    </nav>

    <section class="ag-store-review-form ag-store-account--narrow">
        <h1 class="ag-store-review-form__title">
            {{ __('agricart.storefront.brand_reviews.write_heading', ['brand' => $brand->name_en]) }}
        </h1>
        <p class="ag-store-review-form__hint">{{ __('agricart.storefront.brand_reviews.write_hint') }}</p>

        @if ($existing)
            <p class="ag-store-alert ag-store-alert--warning" role="status">
                {{ __('agricart.storefront.brand_reviews.status_'.$existing->status) }}
            </p>
        @endif

        <form method="post" action="{{ route('store.customer.brand-reviews.store', $brand->slug) }}" class="ag-store-review-form__form">
            @csrf
            @include('store.components.star-rating-input', [
                'name' => 'rating',
                'selected' => (int) old('rating', $existing?->rating),
                'legend' => __('agricart.storefront.brand_reviews.rating_label'),
            ])
            @error('rating') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror

            <div class="ag-store-customer-auth__field">
                <label for="brand-review-body">{{ __('agricart.storefront.brand_reviews.body_label') }}</label>
                <textarea
                    id="brand-review-body"
                    name="body"
                    rows="6"
                    minlength="10"
                    maxlength="2000"
                    required
                    class="ag-store-customer-auth__input"
                >{{ old('body', $existing?->body) }}</textarea>
                @error('body') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
            </div>

            <div class="ag-store-review-form__actions">
                <button type="submit" class="ag-store-btn ag-store-btn--primary">
                    {{ $existing ? __('agricart.storefront.brand_reviews.update') : __('agricart.storefront.brand_reviews.submit') }}
                </button>
                <a href="{{ route('store.brand', $brand->slug) }}" class="ag-store-btn ag-store-btn--secondary">
                    {{ __('agricart.storefront.brand_reviews.cancel') }}
                </a>
            </div>
        </form>
    </section>
@endsection
