@php
    $carouselItems = $footerBrands ?? collect();
    $brandService = app(\App\Services\Store\StoreBrandService::class);
@endphp

@if ($carouselItems->isNotEmpty() && ! request()->routeIs(['store.brands', 'store.brand']))
    <section
        class="ag-store-brands-carousel"
        aria-label="{{ __('agricart.storefront.brands.carousel_label') }}"
        data-store-brands-carousel
    >
        <div class="ag-store__container ag-store-brands-carousel__bar">
            <h2 class="ag-store-brands-carousel__title">
                {{ __('agricart.storefront.brands.carousel_title') }}
            </h2>
            <a href="{{ route('store.brands') }}" class="ag-store-brands-carousel__link">
                {{ __('agricart.storefront.brands.view_all') }}
            </a>
        </div>

        <div class="ag-store-brands-carousel__viewport">
            <div class="ag-store-brands-carousel__track">
                @foreach ([1, 2] as $loopCopy)
                    <div class="ag-store-brands-carousel__group" @if ($loopCopy === 2) aria-hidden="true" @endif>
                        @foreach ($carouselItems as $brand)
                            @php
                                $logoUrl = $brandService->imageUrl($brand);
                                $ratingSummary = $brandService->ratingSummary($brand);
                            @endphp
                            <a
                                href="{{ route('store.brand', $brand->slug) }}"
                                class="ag-store-brands-carousel__card"
                                @if ($loopCopy === 2) tabindex="-1" @endif
                            >
                                <span class="ag-store-brands-carousel__logo">
                                    @if ($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="" width="64" height="64" loading="lazy" />
                                    @else
                                        <span aria-hidden="true">{{ strtoupper(mb_substr($brand->name_en, 0, 1)) }}</span>
                                    @endif
                                </span>
                                <span class="ag-store-brands-carousel__names">
                                    <strong class="ag-store-brands-carousel__name-en">{{ $brand->name_en }}</strong>
                                    @if (filled($brand->name_ur))
                                        <span class="ag-store-brands-carousel__name-ur" dir="rtl">{{ $brand->name_ur }}</span>
                                    @endif
                                </span>
                                @if ($ratingSummary)
                                    @include('store.components.brand-rating', [
                                        'summary' => $ratingSummary,
                                        'variant' => 'compact',
                                    ])
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
