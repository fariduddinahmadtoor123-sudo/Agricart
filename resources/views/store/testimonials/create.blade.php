@extends('store.layouts.app')

@section('title', ($isEditing ?? false) ? __('agricart.storefront.testimonials.edit_title') : __('agricart.storefront.testimonials.write_title'))
@section('robots', 'noindex,nofollow')

@section('content')
    <nav class="ag-store-breadcrumb ag-store-breadcrumb--compact" aria-label="{{ __('agricart.storefront.category.breadcrumb') }}">
        <a href="{{ route('store.home') }}">{{ __('agricart.storefront.category.home') }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('store.testimonials') }}">{{ __('agricart.storefront.testimonials.nav') }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ ($isEditing ?? false) ? __('agricart.storefront.testimonials.edit_short') : __('agricart.storefront.testimonials.write_short') }}</span>
    </nav>

    <section class="ag-store-review-form">
        <h1 class="ag-store-review-form__title">
            {{ ($isEditing ?? false) ? __('agricart.storefront.testimonials.edit_heading') : __('agricart.storefront.testimonials.write_heading') }}
        </h1>
        <p class="ag-store-review-form__hint">{{ __('agricart.storefront.testimonials.write_hint') }}</p>
        <p class="ag-store-review-form__policy">{{ __('agricart.storefront.testimonials.community_note') }}</p>

        @if ($errors->any())
            <div class="ag-store-alert ag-store-alert--error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('store.customer.testimonials.store') }}" class="ag-store-review-form__form">
            @csrf
            @include('store.components.star-rating-input', [
                'existingRating' => $existing?->rating,
                'legend' => __('agricart.storefront.testimonials.rating_label'),
            ])

            <div class="ag-store-review-form__field">
                <label for="testimonial-title">{{ __('agricart.storefront.testimonials.title_label') }}</label>
                <input
                    id="testimonial-title"
                    type="text"
                    name="title"
                    maxlength="160"
                    value="{{ old('title', $existing?->title) }}"
                />
            </div>

            <div class="ag-store-review-form__field">
                <label for="testimonial-body">{{ __('agricart.storefront.testimonials.body_label') }}</label>
                <textarea
                    id="testimonial-body"
                    name="body"
                    rows="5"
                    maxlength="2000"
                    required
                >{{ old('body', $existing?->body) }}</textarea>
            </div>

            <div class="ag-store-review-form__actions">
                <button type="submit" class="ag-store-btn ag-store-btn--primary">
                    {{ ($isEditing ?? false) ? __('agricart.storefront.testimonials.update') : __('agricart.storefront.testimonials.submit') }}
                </button>
                <a href="{{ route('store.testimonials') }}" class="ag-store-btn ag-store-btn--secondary">
                    {{ __('agricart.storefront.testimonials.cancel') }}
                </a>
            </div>
        </form>
    </section>
@endsection
