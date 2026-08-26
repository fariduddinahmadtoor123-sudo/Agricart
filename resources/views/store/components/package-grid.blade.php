@php
    $packages = $packages ?? collect();
    $sectionTitle = $sectionTitle ?? __('agricart.storefront.packages.section_title');
    $columns = min(6, max(3, (int) ($settings->category_grid_columns ?? 4)));
@endphp

@if ($packages->isNotEmpty())
    <section class="ag-store-category-section ag-store-package-section">
        <h2 class="ag-store-section-title">{{ $sectionTitle }}</h2>
        <div @class(['ag-store-category-grid', 'ag-store-category-grid--cols-' . $columns])>
            @foreach ($packages as $package)
                @php
                    $packageName = $packageService->localizedName($package);
                    $imageUrl = $packageService->imageUrl($package);
                    $letter = mb_strtoupper(mb_substr($packageName, 0, 1));
                    $itemCount = $package->items->count();
                    $discount = (float) $package->discount_percent;
                @endphp
                <a href="{{ route('store.package', $package->slug) }}" class="ag-store-category-card ag-store-package-card">
                    <div class="ag-store-category-card__image-wrap">
                        <span class="ag-store-category-card__placeholder" aria-hidden="true">{{ $letter }}</span>
                        @if ($imageUrl)
                            <img
                                src="{{ $imageUrl }}"
                                alt="{{ $packageName }}"
                                class="ag-store-category-card__image"
                                width="320"
                                height="320"
                                loading="lazy"
                                decoding="async"
                                onerror="this.classList.add('is-failed')"
                            />
                        @endif
                        <span class="ag-store-package-card__badge">{{ __('agricart.storefront.packages.badge') }}</span>
                    </div>
                    <div class="ag-store-category-card__body">
                        <span class="ag-store-category-card__name">{{ $packageName }}</span>
                        <span class="ag-store-category-card__meta">
                            <span class="ag-store-category-card__meta-item">
                                {{ trans_choice('agricart.storefront.packages.item_count', $itemCount, ['count' => number_format($itemCount)]) }}
                            </span>
                            @if ($discount > 0)
                                <span class="ag-store-category-card__meta-sep" aria-hidden="true">&middot;</span>
                                <span class="ag-store-category-card__meta-item">
                                    {{ __('agricart.storefront.packages.discount_badge', ['percent' => rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.')]) }}
                                </span>
                            @endif
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
