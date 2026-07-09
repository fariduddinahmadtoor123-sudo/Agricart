@php
    $idPrefix = $idPrefix ?? 'addr';
    $inputClass = $inputClass ?? 'ag-store-customer-auth__input';
    $gridClass = $gridClass ?? 'ag-store-customer-auth__grid';
    $provinces = $provinces ?? config('pakistani_provinces', []);
    $customer = $customer ?? null;
@endphp

<div class="{{ $gridClass }}">
    <div class="ag-store-customer-auth__field ag-store-customer-auth__field--full">
        <label for="{{ $idPrefix }}-address1">{{ __('agricart.customer.fields.address_line1') }} *</label>
        <input id="{{ $idPrefix }}-address1" type="text" name="address_line1" value="{{ old('address_line1', $customer?->address_line1) }}" class="{{ $inputClass }}" autocomplete="address-line1" required />
        @error('address_line1') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
    </div>
    <div class="ag-store-customer-auth__field ag-store-customer-auth__field--full">
        <label for="{{ $idPrefix }}-address2">{{ __('agricart.customer.fields.address_line2') }}</label>
        <input id="{{ $idPrefix }}-address2" type="text" name="address_line2" value="{{ old('address_line2', $customer?->address_line2) }}" class="{{ $inputClass }}" autocomplete="address-line2" />
        @error('address_line2') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
    </div>
    <div class="ag-store-customer-auth__field">
        <label for="{{ $idPrefix }}-city">{{ __('agricart.customer.fields.city') }} *</label>
        <input id="{{ $idPrefix }}-city" type="text" name="city" value="{{ old('city', $customer?->city) }}" class="{{ $inputClass }}" autocomplete="address-level2" required />
        @error('city') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
    </div>
    <div class="ag-store-customer-auth__field">
        <label for="{{ $idPrefix }}-state">{{ __('agricart.customer.fields.state') }} *</label>
        <select id="{{ $idPrefix }}-state" name="state" class="{{ $inputClass }}" required>
            <option value="">{{ __('agricart.customer.hints.select_province') }}</option>
            @foreach ($provinces as $province)
                <option value="{{ $province }}" @selected(old('state', $customer?->state) === $province)>{{ $province }}</option>
            @endforeach
        </select>
        @error('state') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
    </div>
    <div class="ag-store-customer-auth__field">
        <label for="{{ $idPrefix }}-postal">{{ __('agricart.customer.fields.postal_code') }}</label>
        <input id="{{ $idPrefix }}-postal" type="text" name="postal_code" value="{{ old('postal_code', $customer?->postal_code) }}" class="{{ $inputClass }}" autocomplete="postal-code" />
        @error('postal_code') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
    </div>
    <div class="ag-store-customer-auth__field">
        <label for="{{ $idPrefix }}-country">{{ __('agricart.customer.fields.country') }}</label>
        <input id="{{ $idPrefix }}-country" type="text" name="country" value="{{ old('country', $customer?->country ?? 'Pakistan') }}" class="{{ $inputClass }}" autocomplete="country-name" />
        @error('country') <span class="ag-store-customer-auth__error">{{ $message }}</span> @enderror
    </div>
</div>
