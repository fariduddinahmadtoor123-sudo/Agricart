@extends('store.layouts.app')

@section('title', __('agricart.storefront.reviews.write_title', ['product' => $productService->localizedName($product)]))

@section('content')
    <nav class="ag-store-breadcrumb ag-store-breadcrumb--compact" aria-label="{{ __('agricart.storefront.category.breadcrumb') }}">
        <a href="{{ route('store.home') }}">{{ __('agricart.storefront.category.home') }}</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('store.product', $product->slug) }}">{{ $productService->localizedName($product) }}</a>
        <span aria-hidden="true">/</span>
        <span>{{ __('agricart.storefront.reviews.write_short') }}</span>
    </nav>

    <section class="ag-store-review-form">
        <h1 class="ag-store-review-form__title">{{ __('agricart.storefront.reviews.write_heading') }}</h1>
        <p class="ag-store-review-form__product">{{ $productService->localizedName($product) }}</p>

        @if ($requiresProof)
            <p class="ag-store-review-form__hint">{{ __('agricart.storefront.reviews.write_hint_proof') }}</p>
        @else
            <p class="ag-store-review-form__hint">{{ __('agricart.storefront.reviews.write_hint_verified') }}</p>
        @endif

        <p class="ag-store-review-form__policy">{{ __('agricart.storefront.reviews.moderation_policy') }}</p>

        @if ($errors->any())
            <div class="ag-store-alert ag-store-alert--error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('store.customer.reviews.store', $product->slug) }}" class="ag-store-review-form__form" enctype="multipart/form-data">
            @csrf
            <fieldset class="ag-store-review-form__stars">
                <legend>{{ __('agricart.storefront.reviews.rating_label') }}</legend>
                <div class="ag-store-review-form__star-options">
                    @for ($star = 5; $star >= 1; $star--)
                        <label class="ag-store-review-form__star-option">
                            <input
                                type="radio"
                                name="rating"
                                value="{{ $star }}"
                                @checked((int) old('rating', $existing?->rating ?? 5) === $star)
                                required
                            />
                            <span>{{ $star }} ★</span>
                        </label>
                    @endfor
                </div>
            </fieldset>

            <div class="ag-store-review-form__field">
                <label for="review-body">{{ __('agricart.storefront.reviews.body_label') }}</label>
                <textarea
                    id="review-body"
                    name="body"
                    rows="5"
                    maxlength="2000"
                    required
                >{{ old('body', $existing?->body) }}</textarea>
            </div>

            @if ($requiresProof)
                <div class="ag-store-review-form__proof-panel">
                    <h2 class="ag-store-review-form__proof-title">{{ __('agricart.storefront.reviews.proofs_section_title') }}</h2>
                    <p class="ag-store-review-form__proof-lead">{{ __('agricart.storefront.reviews.proofs_section_lead') }}</p>
                    <ul class="ag-store-review-form__proof-points">
                        <li>{{ __('agricart.storefront.reviews.proofs_accepts') }}</li>
                        <li>{{ __('agricart.storefront.reviews.proofs_not_identity') }}</li>
                    </ul>

                    @if ($existing?->proofPhotos?->isNotEmpty())
                        <ul class="ag-store-review-form__existing-photos">
                            @foreach ($existing->proofPhotos as $photo)
                                <li>
                                    @if ($photo->isPdf())
                                        <a href="{{ $photo->url() }}" target="_blank" rel="noopener">{{ __('agricart.storefront.reviews.proof_pdf') }}</a>
                                    @else
                                        <img src="{{ $photo->url() }}" alt="" width="72" height="72" loading="lazy" />
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <p class="ag-store-review-form__hint ag-store-review-form__hint--tight">{{ __('agricart.storefront.reviews.proofs_replace_hint') }}</p>
                    @endif

                    <label class="ag-store-review-form__dropzone" for="review-proofs">
                        <span class="ag-store-review-form__dropzone-icon" aria-hidden="true"></span>
                        <span class="ag-store-review-form__dropzone-title">{{ __('agricart.storefront.reviews.proofs_drop_title') }}</span>
                        <span class="ag-store-review-form__dropzone-cta">{{ __('agricart.storefront.reviews.proofs_drop_cta') }}</span>
                        <span class="ag-store-review-form__dropzone-meta">{{ __('agricart.storefront.reviews.proofs_hint') }}</span>
                        <input
                            id="review-proofs"
                            class="ag-store-review-form__dropzone-input"
                            type="file"
                            name="proofs[]"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            multiple
                            @if ($requiresProof && ($existing?->proofPhotos?->isEmpty() ?? true)) required @endif
                        />
                    </label>
                    <p id="review-proofs-names" class="ag-store-review-form__file-names" hidden></p>

                    <div class="ag-store-review-form__field ag-store-review-form__field--nested">
                        <label for="review-proof-note">{{ __('agricart.storefront.reviews.proof_note_label') }}</label>
                        <p class="ag-store-review-form__hint ag-store-review-form__hint--tight">{{ __('agricart.storefront.reviews.proof_note_hint') }}</p>
                        <textarea
                            id="review-proof-note"
                            name="proof_note"
                            rows="2"
                            maxlength="500"
                        >{{ old('proof_note', $existing?->proof_note) }}</textarea>
                    </div>
                </div>
            @endif

            <div class="ag-store-review-form__field">
                <label class="ag-store-review-form__field-label" for="review-photos">{{ __('agricart.storefront.reviews.photos_label') }}</label>
                <p class="ag-store-review-form__hint ag-store-review-form__hint--tight">{{ __('agricart.storefront.reviews.photos_hint') }}</p>
                @if ($existing?->galleryPhotos?->isNotEmpty())
                    <ul class="ag-store-review-form__existing-photos">
                        @foreach ($existing->galleryPhotos as $photo)
                            <li><img src="{{ $photo->url() }}" alt="" width="72" height="72" loading="lazy" /></li>
                        @endforeach
                    </ul>
                    <p class="ag-store-review-form__hint ag-store-review-form__hint--tight">{{ __('agricart.storefront.reviews.photos_replace_hint') }}</p>
                @endif
                <label class="ag-store-review-form__dropzone ag-store-review-form__dropzone--optional" for="review-photos">
                    <span class="ag-store-review-form__dropzone-icon ag-store-review-form__dropzone-icon--photos" aria-hidden="true"></span>
                    <span class="ag-store-review-form__dropzone-title">{{ __('agricart.storefront.reviews.photos_drop_title') }}</span>
                    <span class="ag-store-review-form__dropzone-cta">{{ __('agricart.storefront.reviews.photos_drop_cta') }}</span>
                    <input
                        id="review-photos"
                        class="ag-store-review-form__dropzone-input"
                        type="file"
                        name="photos[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                    />
                </label>
                <p id="review-photos-names" class="ag-store-review-form__file-names" hidden></p>
            </div>

            <div class="ag-store-review-form__actions">
                <button type="submit" class="ag-store-review-form__submit">
                    {{ __('agricart.storefront.reviews.submit') }}
                </button>
                <a href="{{ route('store.product', $product->slug) }}" class="ag-store-review-form__cancel">
                    {{ __('agricart.storefront.reviews.cancel') }}
                </a>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            function bindFileNames(inputId, namesId) {
                var input = document.getElementById(inputId);
                var names = document.getElementById(namesId);
                if (!input || !names) return;

                input.addEventListener('change', function () {
                    var files = Array.prototype.slice.call(input.files || []);
                    if (files.length === 0) {
                        names.hidden = true;
                        names.textContent = '';
                        return;
                    }
                    names.hidden = false;
                    names.textContent = files.map(function (file) { return file.name; }).join(', ');
                });
            }

            bindFileNames('review-proofs', 'review-proofs-names');
            bindFileNames('review-photos', 'review-photos-names');
        })();
    </script>
@endpush
