@php
    $packageName = $packageService->localizedName($package);
    $packageImage = $packageService->imageUrl($package);
    $discountPercent = (float) $package->discount_percent;
@endphp

@extends('store.layouts.app')

@section('title', $packageName . ' — ' . ($settings->localized('site_name') ?? config('app.name')))
@section('canonical', route('store.package', $package->slug))
@if ($packageImage) @section('seo_image', $packageImage) @endif

@section('content')
    @include('store.components.breadcrumbs', [
        'crumbs' => [
            ['name' => __('agricart.storefront.category.home'), 'url' => route('store.home')],
            ['name' => $packageName, 'url' => null, 'current' => true],
        ],
    ])

    <div class="ag-store-package">
        <header class="ag-store-package__head">
            <div class="ag-store-package__media">
                @if ($packageImage)
                    <img src="{{ $packageImage }}" alt="{{ $packageName }}" class="ag-store-package__image" width="480" height="480" />
                @else
                    <div class="ag-store-package__placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($packageName, 0, 1)) }}</div>
                @endif
            </div>
            <div class="ag-store-package__intro">
                <h1 class="ag-store-page-title">{{ $packageName }}</h1>
                @if ($discountPercent > 0)
                    <p class="ag-store-package__badge">{{ __('agricart.storefront.packages.discount_badge', ['percent' => rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.')]) }}</p>
                @endif
                @if (filled($package->notes))
                    <p class="ag-store-package__notes">{{ $package->notes }}</p>
                @endif
            </div>
        </header>

        <form method="post" action="{{ route('store.package.add', $package->slug) }}" class="ag-store-package__bill">
            @csrf

            <div class="ag-store-package__package-qty">
                <label for="package-qty">{{ __('agricart.storefront.packages.package_qty') }}</label>
                <input id="package-qty" type="number" name="package_qty" min="1" step="1" value="{{ old('package_qty', 1) }}" class="ag-store-input" />
            </div>

            <div class="ag-store-package__table-wrap">
                <table class="ag-store-package__table">
                    <thead>
                        <tr>
                            <th>{{ __('agricart.storefront.packages.col_product') }}</th>
                            <th>{{ __('agricart.storefront.packages.col_qty') }}</th>
                            <th>{{ __('agricart.storefront.packages.col_price') }}</th>
                            <th>{{ __('agricart.storefront.packages.col_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bill['lines'] as $line)
                            @php $product = $line['product']; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('store.product', $product->slug) }}">{{ $productService->localizedName($product) }}</a>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        name="quantities[{{ $product->id }}]"
                                        min="0"
                                        step="1"
                                        value="{{ old('quantities.'.$product->id, rtrim(rtrim(number_format($line['quantity'], 3, '.', ''), '0'), '.')) }}"
                                        class="ag-store-input ag-store-input--qty"
                                    />
                                </td>
                                <td>{{ $line['unit_price_formatted'] }}</td>
                                <td>{{ $line['line_total_formatted'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ag-store-package__totals">
                <div><span>{{ __('agricart.storefront.packages.subtotal') }}</span><strong>{{ $bill['subtotal_formatted'] }}</strong></div>
                @if ($discountPercent > 0)
                    <div><span>{{ __('agricart.storefront.packages.discount') }}</span><strong>− {{ $bill['discount_formatted'] }}</strong></div>
                @endif
                <div class="ag-store-package__grand"><span>{{ __('agricart.storefront.packages.total') }}</span><strong>{{ $bill['total_formatted'] }}</strong></div>
            </div>

            <p class="ag-store-package__hint">{{ __('agricart.storefront.packages.qty_hint') }}</p>

            <button type="submit" class="ag-store-btn ag-store-btn--primary">
                {{ __('agricart.storefront.packages.add_to_cart') }}
            </button>
        </form>
    </div>
@endsection
