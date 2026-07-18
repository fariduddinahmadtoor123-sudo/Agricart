@php
    use Filament\Support\Icons\Heroicon;

    $localeIsUr = app()->getLocale() === 'ur';

    $localized = static function (array $row, string $enKey, string $urKey) use ($localeIsUr): ?string {
        if ($localeIsUr) {
            return filled($row[$urKey] ?? null) ? $row[$urKey] : ($row[$enKey] ?? null);
        }

        return filled($row[$enKey] ?? null) ? $row[$enKey] : ($row[$urKey] ?? null);
    };

    $designationLabel = static function (?string $key) use ($localeIsUr): string {
        $labels = config('company_contact_designations.' . ($key ?: 'general'), []);

        if (! is_array($labels)) {
            return $key ?: '—';
        }

        return $localeIsUr
            ? ($labels['ur'] ?? $labels['en'] ?? $key)
            : ($labels['en'] ?? $key);
    };

    $formatAddress = static function (array $address) use ($localeIsUr): string {
        $line = $localeIsUr
            ? ($address['address_ur'] ?? $address['address_en'] ?? null)
            : ($address['address_en'] ?? $address['address_ur'] ?? null);

        $parts = array_filter([$line, $address['postal_code'] ?? null]);

        return implode(' — ', $parts);
    };
@endphp

<div class="agricart-company-profile" wire:key="company-profile-summary-{{ $profileVersion }}">
    @if (! $isConfigured)
        <section class="agricart-company-profile__empty-state agricart-backups__panel">
            <x-filament::icon :icon="Heroicon::OutlinedBuildingStorefront" class="agricart-company-profile__empty-icon" />
            <h2>{{ __('company_profile.summary.empty_title') }}</h2>
            <p>{{ __('company_profile.summary.empty_lead') }}</p>
            <p class="agricart-company-profile__empty-hint">{{ __('company_profile.summary.empty_hint') }}</p>
        </section>
    @else
        <section class="agricart-backups__panel agricart-company-profile__panel">
            <header class="agricart-backups__panel-head">
                <h3>{{ __('company_profile.summary.details') }}</h3>
            </header>

            <div class="agricart-categories__table-wrap">
                <table class="agricart-categories__table agricart-company-profile__table">
                    <tbody>
                        <tr>
                            <th scope="row">{{ __('company_profile.fields.business_name_en') }}</th>
                            <td>{{ $profile->business_name_en ?: '—' }}</td>
                            <th scope="row">{{ __('company_profile.fields.business_name_ur') }}</th>
                            <td dir="rtl">{{ $profile->business_name_ur ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('company_profile.fields.email') }}</th>
                            <td>{{ $profile->email ?: '—' }}</td>
                            <th scope="row">{{ __('company_profile.fields.website') }}</th>
                            <td>{{ $profile->website ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('company_profile.fields.whatsapp_number') }}</th>
                            <td colspan="3">{{ $profile->whatsapp_number ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('company_profile.fields.ntn') }}</th>
                            <td>{{ $profile->ntn ?: '—' }}</td>
                            <th scope="row">{{ __('company_profile.fields.strn') }}</th>
                            <td>{{ $profile->strn ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('company_profile.fields.currency') }}</th>
                            <td>{{ $profile->currency_code }}</td>
                            <th scope="row">{{ __('company_profile.fields.price_use_decimals') }}</th>
                            <td>
                                {{ $profile->price_use_decimals ? __('company_profile.options.decimals_yes') : __('company_profile.options.decimals_no') }}
                                @if ($profile->price_use_decimals)
                                    <span class="agricart-company-profile__muted">({{ $profile->price_decimal_places }})</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="agricart-backups__panel agricart-company-profile__panel">
            <header class="agricart-backups__panel-head">
                <h3>{{ __('company_profile.summary.phones') }}</h3>
            </header>

            @if ($phones === [])
                <p class="agricart-company-profile__empty">{{ __('company_profile.summary.empty_phones') }}</p>
            @else
                <div class="agricart-categories__table-wrap">
                    <table class="agricart-categories__table agricart-company-profile__table">
                        <thead>
                            <tr>
                                <th>{{ __('company_profile.fields.designation') }}</th>
                                <th>{{ __('company_profile.fields.contact_person_en') }}</th>
                                <th>{{ __('company_profile.fields.phone_number') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($phones as $phone)
                                <tr wire:key="summary-phone-{{ $loop->index }}">
                                    <td>{{ $designationLabel($phone['designation'] ?? 'general') }}</td>
                                    <td>
                                        <span>{{ $localized($phone, 'contact_person_en', 'contact_person_ur') ?: '—' }}</span>
                                        @if (filled($phone['contact_person_ur'] ?? null) && filled($phone['contact_person_en'] ?? null))
                                            <span class="agricart-company-profile__subline" dir="rtl">{{ $phone['contact_person_ur'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $phone['phone_number'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="agricart-backups__panel agricart-company-profile__panel">
            <header class="agricart-backups__panel-head">
                <h3>{{ __('company_profile.summary.addresses') }}</h3>
            </header>

            @if ($addresses === [])
                <p class="agricart-company-profile__empty">{{ __('company_profile.summary.empty_addresses') }}</p>
            @else
                <div class="agricart-categories__table-wrap">
                    <table class="agricart-categories__table agricart-company-profile__table">
                        <thead>
                            <tr>
                                <th>{{ __('company_profile.summary.address_line') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($addresses as $address)
                                <tr wire:key="summary-address-{{ $loop->index }}">
                                    <td>{{ $formatAddress($address) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="agricart-backups__panel agricart-company-profile__panel agricart-company-profile__brand-panel">
            <header class="agricart-backups__panel-head">
                <h3>{{ __('company_profile.summary.store_message') }}</h3>
            </header>

            <div class="agricart-company-profile__brand-strip">
                <div class="agricart-company-profile__brand-logo" style="width:100px;height:100px;min-width:100px;max-width:100px;min-height:100px;max-height:100px;overflow:hidden;flex-shrink:0;">
                    @if ($profile->displayLogoUrl())
                        <img
                            src="{{ $profile->displayLogoUrl() }}"
                            alt="{{ $profile->localized('business_name') }}"
                            width="84"
                            height="84"
                            style="display:block;max-width:84px;max-height:84px;width:auto;height:auto;object-fit:contain;"
                        />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedBuildingStorefront" class="agricart-company-profile__brand-logo-placeholder" />
                        <span class="agricart-company-profile__brand-logo-empty">{{ __('company_profile.hints.no_logo') }}</span>
                    @endif
                </div>

                <div class="agricart-company-profile__brand-message agricart-company-profile__brand-message--en">
                    <span class="agricart-company-profile__brand-message-label">{{ __('company_profile.fields.tagline_en') }}</span>
                    <p>{{ filled($profile->tagline_en) ? $profile->tagline_en : '—' }}</p>
                </div>

                <div class="agricart-company-profile__brand-message agricart-company-profile__brand-message--ur" dir="rtl">
                    <span class="agricart-company-profile__brand-message-label">{{ __('company_profile.fields.tagline_ur') }}</span>
                    <p>{{ filled($profile->tagline_ur) ? $profile->tagline_ur : '—' }}</p>
                </div>
            </div>
        </section>
    @endif
</div>
