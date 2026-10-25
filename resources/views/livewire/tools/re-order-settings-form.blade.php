@php
    use App\Livewire\Tools\ReOrderSettingsForm;
@endphp

<div
    class="agricart-tools-reorder-settings"
    x-data
    x-on:tools-reorder-settings-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:tools-reorder-settings-form-open.window="$wire.openForm()"
>
    <style>
.agricart-tools-reorder-settings__section{display:flex;flex-direction:column;gap:.85rem}
.agricart-tools-reorder-settings__grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem 1rem}
.agricart-tools-reorder-settings__block{display:flex;flex-direction:column;gap:.5rem;padding:.75rem .85rem;border-radius:.75rem;background:rgba(148,163,184,.12);border:1px solid rgba(148,163,184,.28)}
.agricart-tools-reorder-settings__block-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}
.agricart-tools-reorder-settings__phone-list{display:flex;flex-direction:column;gap:.45rem}
.agricart-tools-reorder-settings__phone-row{display:flex;align-items:center;gap:.4rem}
.agricart-tools-reorder-settings__icon-btn{display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;width:2rem;height:2rem;border-radius:.5rem;color:#9ca3af}
.agricart-tools-reorder-settings__icon-btn:hover{background:rgba(239,68,68,.12);color:#dc2626}
.agricart-tools-reorder-settings__icon-btn:disabled{opacity:.35;pointer-events:none}
.agricart-tools-reorder-settings__add-row{display:flex;justify-content:flex-end}
.agricart-tools-reorder-settings__chips{display:flex;flex-wrap:wrap;gap:.5rem}
.agricart-tools-reorder-settings__chip{display:inline-flex;align-items:flex-start;gap:.35rem;max-width:100%;padding:.45rem .55rem .45rem .7rem;border-radius:.7rem;background:#fff;border:1px solid rgba(148,163,184,.35);box-shadow:0 1px 2px rgba(15,23,42,.04)}
.dark .agricart-tools-reorder-settings__chip{background:rgba(255,255,255,.04)}
.agricart-tools-reorder-settings__chip-names{display:flex;flex-wrap:wrap;align-items:center;gap:.25rem;font-size:.8125rem;font-weight:600;color:#111827}
.dark .agricart-tools-reorder-settings__chip-names{color:#f3f4f6}
.agricart-tools-reorder-settings__chip-phones{margin-top:.15rem;font-size:.75rem;color:#6b7280}
.agricart-tools-reorder-settings__dot{color:#9ca3af}
.agricart-tools-reorder-settings__digit-input{text-align:center;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
@media (max-width:640px){.agricart-tools-reorder-settings__grid-2{grid-template-columns:1fr}.agricart-tools-reorder-settings__block-head{flex-direction:column;align-items:stretch}}
</style>


    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-form-panel__section agricart-tools-reorder-settings__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.pages.tools.re_order_section_shop') }}</h3>

        <div class="agricart-tools-reorder-settings__grid-2">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tools-ro-shop-en">{{ __('agricart.pages.tools.re_order_shop_en') }}</label>
                <x-filament::input.wrapper>
                    <input id="tools-ro-shop-en" type="text" wire:model.live="shopNameEn" class="fi-input" autocomplete="organization" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tools-ro-shop-ur">{{ __('agricart.pages.tools.re_order_shop_ur') }}</label>
                <x-filament::input.wrapper>
                    <input id="tools-ro-shop-ur" type="text" wire:model.live="shopNameUr" class="fi-input" dir="rtl" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="agricart-tools-reorder-settings__block">
            <div class="agricart-tools-reorder-settings__block-head">
                <div>
                    <label class="agricart-form-panel__label">{{ __('agricart.pages.tools.re_order_phones') }}</label>
                    <p class="agricart-form-panel__hint">{{ __('agricart.pages.tools.re_order_phones_hint') }}</p>
                </div>
                <x-filament::button color="gray" size="sm" tag="button" type="button" icon="heroicon-o-plus" wire:click="addPhone">
                    {{ __('agricart.pages.tools.re_order_add_phone') }}
                </x-filament::button>
            </div>
            <div class="agricart-tools-reorder-settings__phone-list">
                @foreach ($phones as $index => $phoneValue)
                    <div class="agricart-tools-reorder-settings__phone-row" wire:key="shop-phone-{{ $index }}">
                        <x-filament::input.wrapper class="w-full">
                            <input
                                type="text"
                                inputmode="tel"
                                wire:model.live="phones.{{ $index }}"
                                class="fi-input"
                                dir="ltr"
                                autocomplete="tel"
                                placeholder="{{ __('agricart.pages.tools.re_order_phone_n', ['n' => $index + 1]) }}"
                            />
                        </x-filament::input.wrapper>
                        <button
                            type="button"
                            class="agricart-tools-reorder-settings__icon-btn"
                            wire:click="removePhone({{ $index }})"
                            @disabled(count($phones) <= 1)
                            aria-label="{{ __('agricart.pages.tools.re_order_remove_phone') }}"
                        >
                            <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="agricart-tools-reorder-settings__grid-2">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tools-ro-address-en">{{ __('agricart.pages.tools.re_order_address_en') }}</label>
                <x-filament::input.wrapper>
                    <textarea id="tools-ro-address-en" rows="2" wire:model.live="addressEn" class="fi-input agricart-form-panel__textarea"></textarea>
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="tools-ro-address-ur">{{ __('agricart.pages.tools.re_order_address_ur') }}</label>
                <x-filament::input.wrapper>
                    <textarea id="tools-ro-address-ur" rows="2" wire:model.live="addressUr" class="fi-input agricart-form-panel__textarea" dir="rtl"></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-tools-reorder-settings__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.pages.tools.re_order_purchasers') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.pages.tools.re_order_purchasers_hint') }}</p>

        <div class="agricart-tools-reorder-settings__grid-2">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.pages.tools.re_order_purchaser_en') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model.live="draftPurchaserEn" wire:keydown.enter.prevent="addPurchaser" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.pages.tools.re_order_purchaser_ur') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model.live="draftPurchaserUr" wire:keydown.enter.prevent="addPurchaser" class="fi-input" dir="rtl" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="agricart-tools-reorder-settings__block">
            <div class="agricart-tools-reorder-settings__block-head">
                <label class="agricart-form-panel__label">{{ __('agricart.pages.tools.re_order_purchaser_phones') }}</label>
                <x-filament::button color="gray" size="sm" tag="button" type="button" icon="heroicon-o-plus" wire:click="addDraftPurchaserPhone">
                    {{ __('agricart.pages.tools.re_order_add_phone') }}
                </x-filament::button>
            </div>
            <div class="agricart-tools-reorder-settings__phone-list">
                @foreach ($draftPurchaserPhones as $index => $phoneValue)
                    <div class="agricart-tools-reorder-settings__phone-row" wire:key="draft-purchaser-phone-{{ $index }}">
                        <x-filament::input.wrapper class="w-full">
                            <input
                                type="text"
                                inputmode="tel"
                                wire:model.live="draftPurchaserPhones.{{ $index }}"
                                wire:keydown.enter.prevent="addPurchaser"
                                class="fi-input"
                                dir="ltr"
                                placeholder="{{ __('agricart.pages.tools.re_order_phone_n', ['n' => $index + 1]) }}"
                            />
                        </x-filament::input.wrapper>
                        <button
                            type="button"
                            class="agricart-tools-reorder-settings__icon-btn"
                            wire:click="removeDraftPurchaserPhone({{ $index }})"
                            aria-label="{{ __('agricart.pages.tools.re_order_remove_phone') }}"
                        >
                            <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="agricart-tools-reorder-settings__add-row">
            <x-filament::button color="primary" size="sm" tag="button" type="button" icon="heroicon-o-plus" wire:click="addPurchaser">
                {{ __('agricart.pages.tools.re_order_add_purchaser') }}
            </x-filament::button>
        </div>

        @if (count($purchasers) > 0)
            <div class="agricart-tools-reorder-settings__chips">
                @foreach ($purchasers as $index => $row)
                    @php
                        $labelEn = trim((string) ($row['nameEn'] ?? ''));
                        $labelUr = trim((string) ($row['nameUr'] ?? ''));
                        $phones = is_array($row['phones'] ?? null) ? $row['phones'] : [];
                        $phoneText = collect($phones)->filter()->implode(', ');
                    @endphp
                    <div class="agricart-tools-reorder-settings__chip" wire:key="purchaser-chip-{{ $index }}">
                        <div class="agricart-tools-reorder-settings__chip-body">
                            <div class="agricart-tools-reorder-settings__chip-names">
                                @if ($labelEn !== '')
                                    <span dir="ltr">{{ $labelEn }}</span>
                                @endif
                                @if ($labelEn !== '' && $labelUr !== '')
                                    <span class="agricart-tools-reorder-settings__dot">·</span>
                                @endif
                                @if ($labelUr !== '')
                                    <span dir="rtl">{{ $labelUr }}</span>
                                @endif
                            </div>
                            @if ($phoneText !== '')
                                <div class="agricart-tools-reorder-settings__chip-phones" dir="ltr">{{ $phoneText }}</div>
                            @endif
                        </div>
                        <button
                            type="button"
                            class="agricart-tools-reorder-settings__icon-btn"
                            wire:click="removePurchaser({{ $index }})"
                            aria-label="{{ __('agricart.pages.tools.re_order_remove_purchaser') }}"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="agricart-form-panel__section agricart-tools-reorder-settings__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.pages.tools.re_order_secret_coding') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.pages.tools.re_order_secret_coding_hint') }}</p>

        <div class="agricart-secret-coding-form__digits agricart-tools-reorder-settings__digits">
            @foreach (ReOrderSettingsForm::DIGIT_ORDER as $digit)
                <div class="agricart-form-panel__field" wire:key="tools-ro-digit-{{ $digit }}">
                    <label class="agricart-form-panel__label" for="tools-ro-digit-{{ $digit }}">
                        {{ __('secret_coding.digit_equals.' . $digit) }}
                    </label>
                    <x-filament::input.wrapper>
                        <input
                            id="tools-ro-digit-{{ $digit }}"
                            type="text"
                            wire:model.live="digitSlots.{{ $digit }}"
                            class="fi-input agricart-tools-reorder-settings__digit-input"
                            maxlength="8"
                            dir="ltr"
                            placeholder="A"
                        />
                    </x-filament::input.wrapper>
                </div>
            @endforeach
        </div>
        <p class="agricart-form-panel__hint">{{ __('agricart.pages.tools.re_order_rate_digit_hint') }}</p>
    </section>

    <section class="agricart-form-panel__section agricart-tools-reorder-settings__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.pages.tools.re_order_units') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.pages.tools.re_order_units_hint') }}</p>

        <div class="agricart-tools-reorder-settings__grid-2">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.pages.tools.re_order_unit_en') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model.live="draftUnitEn" wire:keydown.enter.prevent="addUnit" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.pages.tools.re_order_unit_ur') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model.live="draftUnitUr" wire:keydown.enter.prevent="addUnit" class="fi-input" dir="rtl" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="agricart-tools-reorder-settings__add-row">
            <x-filament::button color="primary" size="sm" tag="button" type="button" icon="heroicon-o-plus" wire:click="addUnit">
                {{ __('agricart.pages.tools.re_order_add_unit') }}
            </x-filament::button>
        </div>

        @if (count($units) > 0)
            <div class="agricart-tools-reorder-settings__chips">
                @foreach ($units as $index => $row)
                    @php
                        $labelEn = trim((string) ($row['nameEn'] ?? ''));
                        $labelUr = trim((string) ($row['nameUr'] ?? ''));
                    @endphp
                    <div class="agricart-tools-reorder-settings__chip" wire:key="unit-chip-{{ $index }}">
                        <div class="agricart-tools-reorder-settings__chip-body">
                            <div class="agricart-tools-reorder-settings__chip-names">
                                @if ($labelEn !== '')
                                    <span dir="ltr">{{ $labelEn }}</span>
                                @endif
                                @if ($labelEn !== '' && $labelUr !== '')
                                    <span class="agricart-tools-reorder-settings__dot">·</span>
                                @endif
                                @if ($labelUr !== '')
                                    <span dir="rtl">{{ $labelUr }}</span>
                                @endif
                            </div>
                        </div>
                        <button
                            type="button"
                            class="agricart-tools-reorder-settings__icon-btn"
                            wire:click="removeUnit({{ $index }})"
                            aria-label="{{ __('agricart.pages.tools.re_order_remove_unit') }}"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="agricart-form-panel__section agricart-tools-reorder-settings__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.pages.tools.re_order_phrases') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.pages.tools.re_order_phrases_hint') }}</p>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label">{{ __('agricart.pages.tools.re_order_phrase_add') }}</label>
            <x-filament::input.wrapper>
                <input
                    type="text"
                    wire:model.live="draftPhrase"
                    wire:keydown.enter.prevent="addPhrase"
                    class="fi-input"
                    @if (app()->getLocale() === 'ur') dir="rtl" @endif
                />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-tools-reorder-settings__add-row">
            <x-filament::button color="primary" size="sm" tag="button" type="button" icon="heroicon-o-plus" wire:click="addPhrase">
                {{ __('agricart.pages.tools.re_order_add_phrase') }}
            </x-filament::button>
        </div>

        @if (count($phrases) > 0)
            <div class="agricart-tools-reorder-settings__chips">
                @foreach ($phrases as $index => $phrase)
                    <div class="agricart-tools-reorder-settings__chip" wire:key="phrase-chip-{{ $index }}">
                        <div class="agricart-tools-reorder-settings__chip-body">
                            <div class="agricart-tools-reorder-settings__chip-names">
                                <span>{{ $phrase }}</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="agricart-tools-reorder-settings__icon-btn"
                            wire:click="removePhrase({{ $index }})"
                            aria-label="{{ __('agricart.pages.tools.re_order_remove_phrase') }}"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

</div>
