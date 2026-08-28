<div
    class="agricart-company-profile-form agricart-control-form"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('company-profile-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('company-profile-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:company-profile-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:company-profile-form-open.window="$wire.openForm()"
    x-on:close-form-panel.window="$wire.handlePanelClose()"
>
    @if ($statusMessage)
        <div @class([
            'agricart-category-form__alert',
            'agricart-category-form__alert--prominent' => $statusType === 'success' && $ai_status === 'enriched',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-company-profile-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('company_profile.sections.identity') }}</h3>

        <div class="agricart-company-profile-form__logo-row agricart-company-profile-form__logo-row--store">
            <div class="agricart-company-profile-form__logo-preview">
                @if ($storeLogoPreviewUrl)
                    <img src="{{ $storeLogoPreviewUrl }}" alt="{{ __('company_profile.fields.logo') }}" />
                @else
                    <span>{{ __('company_profile.hints.no_logo') }}</span>
                @endif
            </div>
            <div class="agricart-company-profile-form__logo-actions">
                <a href="{{ $storeFooterSettingsUrl }}" class="agricart-categories__action is-primary">
                    {{ __('company_profile.actions.manage_store_logo') }}
                </a>
            </div>
        </div>

        <div class="agricart-packing-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-business-name-en">
                    {{ __('company_profile.fields.business_name_en') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="cp-business-name-en" type="text" wire:model.live.debounce.300ms="business_name_en" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-business-name-ur">{{ __('company_profile.fields.business_name_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-business-name-ur" type="text" wire:model.live.debounce.300ms="business_name_ur" dir="rtl" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-tagline-en">{{ __('company_profile.fields.tagline_en') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-tagline-en" type="text" wire:model.live.debounce.300ms="tagline_en" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-tagline-ur">{{ __('company_profile.fields.tagline_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-tagline-ur" type="text" wire:model.live.debounce.300ms="tagline_ur" dir="rtl" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-company-profile-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('company_profile.sections.contacts') }}</h3>

        @foreach ($phoneRows as $index => $phone)
            <div class="agricart-company-profile-form__contact-row" wire:key="company-phone-{{ $index }}">
                <x-filament::input.wrapper class="agricart-company-profile-form__designation-wrap">
                    <select wire:model.live="phoneRows.{{ $index }}.designation" class="fi-select-input" title="{{ __('company_profile.fields.designation') }}">
                        @foreach ($designations as $option)
                            <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="phoneRows.{{ $index }}.contact_person_en"
                        class="fi-input"
                        placeholder="{{ __('company_profile.fields.contact_person_en') }}"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="phoneRows.{{ $index }}.contact_person_ur"
                        dir="rtl"
                        class="fi-input"
                        placeholder="{{ __('company_profile.fields.contact_person_ur') }}"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="phoneRows.{{ $index }}.phone_number"
                        class="fi-input"
                        placeholder="{{ __('company_profile.fields.phone_number') }}"
                    />
                </x-filament::input.wrapper>
                @if ($index === count($phoneRows) - 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn" wire:click="addPhoneRow" title="{{ __('company_profile.actions.add_phone') }}">+</button>
                @endif
                @if (count($phoneRows) > 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn is-remove" wire:click="removePhoneRow({{ $index }})" title="{{ __('company_profile.actions.remove') }}">×</button>
                @endif
            </div>
        @endforeach
    </section>

    <section class="agricart-company-profile-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('company_profile.sections.addresses') }}</h3>

        @foreach ($addressRows as $index => $address)
            <div class="agricart-company-profile-form__contact-row agricart-company-profile-form__address-row" wire:key="company-address-{{ $index }}">
                <x-filament::input.wrapper class="agricart-company-profile-form__address-field">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="addressRows.{{ $index }}.address_en"
                        class="fi-input"
                        placeholder="{{ __('company_profile.fields.address_en') }}"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper class="agricart-company-profile-form__address-field">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="addressRows.{{ $index }}.address_ur"
                        dir="rtl"
                        class="fi-input"
                        placeholder="{{ __('company_profile.fields.address_ur') }}"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper class="agricart-company-profile-form__postal-wrap">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="addressRows.{{ $index }}.postal_code"
                        class="fi-input"
                        placeholder="{{ __('company_profile.fields.postal_code') }}"
                    />
                </x-filament::input.wrapper>
                @if ($index === count($addressRows) - 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn" wire:click="addAddressRow" title="{{ __('company_profile.actions.add_address') }}">+</button>
                @endif
                @if (count($addressRows) > 1)
                    <button type="button" class="agricart-company-profile-form__mini-btn is-remove" wire:click="removeAddressRow({{ $index }})" title="{{ __('company_profile.actions.remove') }}">×</button>
                @endif
            </div>
        @endforeach
    </section>

    <section class="agricart-company-profile-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('company_profile.sections.contact_tax') }}</h3>

        <div class="agricart-packing-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-email">{{ __('company_profile.fields.email') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-email" type="email" wire:model.live.debounce.300ms="email" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-whatsapp">{{ __('company_profile.fields.whatsapp_number') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-whatsapp" type="text" wire:model.live.debounce.300ms="whatsapp_number" class="fi-input" inputmode="tel" />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('company_profile.hints.whatsapp_number') }}</p>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-website">{{ __('company_profile.fields.website') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-website" type="text" wire:model.live.debounce.300ms="website" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-ntn">{{ __('company_profile.fields.ntn') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-ntn" type="text" wire:model.live.debounce.300ms="ntn" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-strn">{{ __('company_profile.fields.strn') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-strn" type="text" wire:model.live.debounce.300ms="strn" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="agricart-company-profile-form__certificate-row">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('company_profile.fields.tax_certificate') }}</label>
                <p class="agricart-form-panel__hint">{{ __('company_profile.hints.tax_certificate') }}</p>
                <div class="agricart-company-profile-form__certificate-preview">
                    @if ($taxCertificateUpload)
                        <img src="{{ $taxCertificateUpload->temporaryUrl() }}" alt="" />
                    @elseif ($taxCertificatePreviewUrl)
                        <img src="{{ $taxCertificatePreviewUrl }}" alt="" />
                    @else
                        <span>{{ __('company_profile.hints.no_tax_certificate') }}</span>
                    @endif
                </div>
                <div class="agricart-company-profile-form__certificate-actions">
                    <label class="agricart-categories__action is-primary">
                        {{ __('company_profile.actions.upload_tax_certificate') }}
                        <input type="file" wire:model="taxCertificateUpload" accept="image/jpeg,image/png,image/webp" hidden />
                    </label>
                    @if ($taxCertificatePreviewUrl || $taxCertificateUpload)
                        <button type="button" class="agricart-categories__action" wire:click="removeTaxCertificate">
                            {{ __('company_profile.actions.remove_tax_certificate') }}
                        </button>
                    @endif
                </div>
                @error('taxCertificateUpload') <p class="agricart-form-panel__hint is-danger">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="agricart-packing-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-currency">{{ __('company_profile.fields.currency') }}</label>
                <x-filament::input.wrapper>
                    <input id="cp-currency" type="text" wire:model="currency_code" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="cp-decimals">{{ __('company_profile.fields.price_use_decimals') }}</label>
                <x-filament::input.wrapper>
                    <select id="cp-decimals" wire:model="price_use_decimals" class="fi-select-input">
                        <option value="0">{{ __('company_profile.options.decimals_no') }}</option>
                        <option value="1">{{ __('company_profile.options.decimals_yes') }}</option>
                    </select>
                </x-filament::input.wrapper>
            </div>
            @if ($price_use_decimals)
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="cp-decimal-places">{{ __('company_profile.fields.price_decimal_places') }}</label>
                    <x-filament::input.wrapper>
                        <select id="cp-decimal-places" wire:model="price_decimal_places" class="fi-select-input">
                            @foreach ([0, 1, 2, 3, 4] as $place)
                                <option value="{{ $place }}">{{ $place }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                </div>
            @endif
        </div>
    </section>
</div>
