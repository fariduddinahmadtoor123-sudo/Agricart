@php
    $facts = $identityFacts ?? [];
    $attributes = $product->attributes ?? collect();
    $hasBrand = $product->brand !== null;
    $brandName = $hasBrand ? $presenter->localizedBrandName($product) : null;
    $brandLogoUrl = $hasBrand ? $presenter->brandImageUrl($product) : null;
    $brandRating = $hasBrand ? $presenter->agricartBrandRatingSummary($product) : null;
    $hasSpecs = $hasBrand || $facts !== [] || $attributes->isNotEmpty();
@endphp

@if ($hasSpecs)
  <dl class="ag-store-pdp__spec-list">
    @if ($hasBrand && filled($brandName))
      <div class="ag-store-pdp__spec-row ag-store-pdp__spec-row--brand">
        <dt>{{ __('agricart.storefront.product.facts.brand') }}</dt>
        <dd>
          <span class="ag-store-pdp__spec-brand">
            @if ($brandLogoUrl)
              <img src="{{ $brandLogoUrl }}" alt="" class="ag-store-pdp__spec-brand-logo" loading="lazy" width="28" height="28" />
            @endif
            <span class="ag-store-pdp__spec-brand-meta">
              <span class="ag-store-pdp__spec-brand-name">{{ $brandName }}</span>
              @include('store.components.brand-rating', [
                  'summary' => $brandRating,
                  'variant' => 'full',
              ])
            </span>
          </span>
        </dd>
      </div>
    @endif
    @foreach ($facts as $fact)
      <div class="ag-store-pdp__spec-row">
        <dt>{{ $fact['label'] }}</dt>
        <dd>{{ $fact['value'] }}</dd>
      </div>
    @endforeach
    @foreach ($attributes as $attribute)
      <div class="ag-store-pdp__spec-row">
        <dt>{{ app()->getLocale() === 'ur' && filled($attribute->name_ur) ? $attribute->name_ur : $attribute->name_en }}</dt>
        <dd>{{ $attribute->pivot->value }}</dd>
      </div>
    @endforeach
  </dl>
@else
  <p class="ag-store-pdp__spec-empty">{{ __('agricart.storefront.product.specs_empty') }}</p>
@endif
