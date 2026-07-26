@php
    $selected = old('rating', $existingRating ?? null);
    $selected = $selected !== null && $selected !== '' ? (int) $selected : null;
    $name = $name ?? 'rating';
    $legend = $legend ?? __('agricart.storefront.testimonials.rating_label');
    $hint = $hint ?? __('agricart.storefront.testimonials.rating_hint');
    $noneLabel = $noneLabel ?? __('agricart.storefront.testimonials.rating_none');
    $selectedTemplate = $selectedTemplate ?? 'agricart.storefront.testimonials.rating_selected';
    $optionTemplate = $optionTemplate ?? 'agricart.storefront.testimonials.rating_option';
@endphp

<fieldset class="ag-store-star-picker" data-store-star-picker>
    <legend>{{ $legend }}</legend>
    <p class="ag-store-star-picker__hint">{{ $hint }}</p>
    <div class="ag-store-star-picker__row" role="radiogroup" aria-label="{{ $legend }}">
        @for ($star = 1; $star <= 5; $star++)
            <label class="ag-store-star-picker__star">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $star }}"
                    @checked($selected === $star)
                    required
                    data-store-star-value="{{ $star }}"
                />
                <span aria-hidden="true">★</span>
                <span class="ag-store-visually-hidden">{{ __($optionTemplate, ['count' => $star]) }}</span>
            </label>
        @endfor
    </div>
    <p
        class="ag-store-star-picker__selected"
        data-store-star-label
        data-none-label="{{ $noneLabel }}"
        data-selected-template="{{ __($selectedTemplate, ['count' => ':count']) }}"
    >
        @if ($selected)
            {{ __($selectedTemplate, ['count' => $selected]) }}
        @else
            {{ $noneLabel }}
        @endif
    </p>
</fieldset>
