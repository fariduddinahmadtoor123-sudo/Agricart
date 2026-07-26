@php
    $carouselItems = $footerTestimonials ?? collect();
@endphp

@if ($carouselItems->isNotEmpty() && ! request()->routeIs('store.testimonials'))
    <section
        class="ag-store-testimonials-carousel"
        aria-label="{{ __('agricart.storefront.testimonials.carousel_label') }}"
        data-store-testimonials-carousel
    >
        <div class="ag-store__container ag-store-testimonials-carousel__bar">
            <h2 class="ag-store-testimonials-carousel__title">
                {{ __('agricart.storefront.testimonials.carousel_title') }}
            </h2>
            <a href="{{ route('store.testimonials') }}" class="ag-store-testimonials-carousel__link">
                {{ __('agricart.storefront.testimonials.view_all') }}
            </a>
        </div>

        <div class="ag-store-testimonials-carousel__viewport">
            <div class="ag-store-testimonials-carousel__track" data-store-testimonials-track>
                @foreach ([1, 2] as $loopCopy)
                    <div class="ag-store-testimonials-carousel__group" @if ($loopCopy === 2) aria-hidden="true" @endif>
                        @foreach ($carouselItems as $item)
                            @php
                                $isLong = \Illuminate\Support\Str::length($item->body) > 120;
                                $preview = \Illuminate\Support\Str::limit($item->body, 120);
                            @endphp
                            <article class="ag-store-testimonials-carousel__card">
                                <button
                                    type="button"
                                    class="ag-store-testimonials-carousel__open"
                                    data-store-testimonial-open
                                    @if ($loopCopy === 2) tabindex="-1" @endif
                                    aria-haspopup="dialog"
                                    aria-label="{{ __('agricart.storefront.testimonials.read_full', ['name' => $item->publicName()]) }}"
                                >
                                    <div class="ag-store-testimonials__stars" aria-hidden="true">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <span class="{{ $star <= $item->rating ? 'is-on' : '' }}">★</span>
                                        @endfor
                                    </div>
                                    @if (filled($item->title))
                                        <strong class="ag-store-testimonials-carousel__card-title">{{ $item->title }}</strong>
                                    @endif
                                    <p class="ag-store-testimonials-carousel__body">{{ $preview }}</p>
                                    @if ($isLong)
                                        <span class="ag-store-testimonials-carousel__more">
                                            {{ __('agricart.storefront.testimonials.read_more') }}
                                        </span>
                                    @endif
                                    <span class="ag-store-testimonials__author ag-store-testimonials-carousel__name">
                                        @if ($item->publicPhotoUrl())
                                            <img src="{{ $item->publicPhotoUrl() }}" alt="" class="ag-store-testimonials__avatar" width="28" height="28" loading="lazy" />
                                        @endif
                                        <strong>{{ $item->publicName() }}</strong>
                                    </span>
                                </button>
                                <div hidden data-store-testimonial-payload>
                                    <div data-payload-rating>{{ $item->rating }}</div>
                                    <div data-payload-name>{{ $item->publicName() }}</div>
                                    @if ($item->publicPhotoUrl())
                                        <div data-payload-photo>{{ $item->publicPhotoUrl() }}</div>
                                    @endif
                                    @if (filled($item->title))
                                        <div data-payload-title>{{ $item->title }}</div>
                                    @endif
                                    <div data-payload-body>{{ $item->body }}</div>
                                    @if (filled($item->admin_reply))
                                        <div data-payload-reply>{{ $item->admin_reply }}</div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <dialog
            class="ag-store-testimonial-dialog"
            data-store-testimonial-dialog
            aria-labelledby="ag-store-testimonial-dialog-title"
        >
            <div class="ag-store-testimonial-dialog__panel">
                <div class="ag-store-testimonial-dialog__header">
                    <div class="ag-store-testimonial-dialog__identity">
                        <span class="ag-store-testimonial-dialog__avatar" data-dialog-avatar hidden></span>
                        <div>
                            <p class="ag-store-testimonial-dialog__name" id="ag-store-testimonial-dialog-title" data-dialog-name></p>
                            <div class="ag-store-testimonials__stars ag-store-testimonial-dialog__stars" data-dialog-stars aria-hidden="true"></div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ag-store-testimonial-dialog__close"
                        data-store-testimonial-close
                        aria-label="{{ __('agricart.storefront.testimonials.close_review') }}"
                    >×</button>
                </div>
                <h3 class="ag-store-testimonial-dialog__title" data-dialog-title hidden></h3>
                <p class="ag-store-testimonial-dialog__body" data-dialog-body></p>
                <div class="ag-store-testimonial-dialog__reply" data-dialog-reply-wrap hidden>
                    <strong>{{ __('agricart.storefront.testimonials.agricart_reply') }}</strong>
                    <p data-dialog-reply></p>
                </div>
                <div class="ag-store-testimonial-dialog__footer">
                    <a href="{{ route('store.testimonials') }}" class="ag-store-btn ag-store-btn--secondary">
                        {{ __('agricart.storefront.testimonials.view_all') }}
                    </a>
                    <button type="button" class="ag-store-btn ag-store-btn--primary" data-store-testimonial-close>
                        {{ __('agricart.storefront.testimonials.close_review') }}
                    </button>
                </div>
            </div>
        </dialog>
    </section>
@endif
