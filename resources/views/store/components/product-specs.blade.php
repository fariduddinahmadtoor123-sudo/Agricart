@php
    $facts = $identityFacts ?? [];
    $attributes = $product->attributes ?? collect();
    $controls = $product->controls ?? collect();
    $hasBrand = $product->brand !== null;
    $brandName = $hasBrand ? $presenter->localizedBrandName($product) : null;
    $brandLogoUrl = $hasBrand ? $presenter->brandImageUrl($product) : null;
    $hasPrimarySpecs = $hasBrand || $facts !== [] || $attributes->isNotEmpty();
    $hasControls = $controls->isNotEmpty();
    $hasSpecs = $hasPrimarySpecs || $hasControls;
@endphp

@if ($hasSpecs)
  @if ($hasPrimarySpecs)
    <dl class="ag-store-pdp__spec-list">
      @if ($hasBrand && filled($brandName))
        <div class="ag-store-pdp__spec-row ag-store-pdp__spec-row--brand">
          <dt>{{ __('agricart.storefront.product.facts.brand') }}</dt>
          <dd>
            <span class="ag-store-pdp__spec-brand">
              @if ($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="" class="ag-store-pdp__spec-brand-logo" loading="lazy" width="28" height="28" />
              @endif
              <span class="ag-store-pdp__spec-brand-name">{{ $brandName }}</span>
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
  @endif

  @if ($hasControls)
    <div @class(['ag-store-pdp__spec-controls', 'ag-store-pdp__spec-controls--solo' => ! $hasPrimarySpecs])>
      @foreach ($controls as $control)
        @php
          $controlValue = $presenter->localizedControlValue($control) ?: '—';
          $isLongControl = mb_strlen($controlValue) > 72;
        @endphp
        @if ($isLongControl)
          <dl class="ag-store-pdp__spec-notice">
            <dt>{{ $presenter->localizedControlName($control) }}</dt>
            <dd>{{ $controlValue }}</dd>
          </dl>
        @else
          <dl class="ag-store-pdp__spec-list ag-store-pdp__spec-list--control">
            <div class="ag-store-pdp__spec-row">
              <dt>{{ $presenter->localizedControlName($control) }}</dt>
              <dd>{{ $controlValue }}</dd>
            </div>
          </dl>
        @endif
      @endforeach
    </div>
  @endif
@else
  <p class="ag-store-pdp__spec-empty">{{ __('agricart.storefront.product.specs_empty') }}</p>
@endif
