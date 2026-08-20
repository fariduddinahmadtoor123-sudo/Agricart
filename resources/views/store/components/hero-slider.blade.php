@php
    $interval = app(\App\Services\Store\StoreHeroSliderService::class)->intervalSeconds();
    $slideCount = $slides->count();
@endphp

@if ($slideCount > 0)
    <section
        class="ag-store-hero-slider"
        aria-label="{{ __('agricart.storefront.hero_slider.label') }}"
        data-store-hero-slider
        data-interval="{{ $interval }}"
    >
        <div class="ag-store-hero-slider__viewport">
            <div class="ag-store-hero-slider__track" data-store-hero-track>
                @foreach ($slides as $index => $slide)
                    <div
                        class="ag-store-hero-slider__slide @if ($index === 0) is-active @endif"
                        data-store-hero-slide
                        @if ($index !== 0) hidden @endif
                    >
                        @if (filled($slide->link))
                            <a href="{{ $slide->link }}" class="ag-store-hero-slider__link">
                                <img
                                    src="{{ $slide->imageUrl() }}"
                                    alt="{{ $slide->localizedAlt() }}"
                                    class="ag-store-hero-slider__image"
                                    @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                />
                            </a>
                        @else
                            <img
                                src="{{ $slide->imageUrl() }}"
                                alt="{{ $slide->localizedAlt() }}"
                                class="ag-store-hero-slider__image"
                                @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                            />
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($slideCount > 1)
                <button type="button" class="ag-store-hero-slider__nav ag-store-hero-slider__nav--prev" data-store-hero-prev aria-label="{{ __('agricart.storefront.hero_slider.prev') }}">
                    <span aria-hidden="true">‹</span>
                </button>
                <button type="button" class="ag-store-hero-slider__nav ag-store-hero-slider__nav--next" data-store-hero-next aria-label="{{ __('agricart.storefront.hero_slider.next') }}">
                    <span aria-hidden="true">›</span>
                </button>

                <div class="ag-store-hero-slider__dots" role="tablist" aria-label="{{ __('agricart.storefront.hero_slider.dots') }}">
                    @foreach ($slides as $index => $slide)
                        <button
                            type="button"
                            class="ag-store-hero-slider__dot @if ($index === 0) is-active @endif"
                            data-store-hero-dot="{{ $index }}"
                            role="tab"
                            aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-label="{{ __('agricart.storefront.hero_slider.go_to', ['number' => $index + 1]) }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
