@php
    use Filament\Support\Icons\Heroicon;

    $readonly = $this->isReadonly();
@endphp

<div
    class="agricart-product-form"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('product-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('product-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:product-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:product-form-open.window="$wire.openForm($event.detail.productId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-product-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    {{-- Row 1: Category hierarchy + display categories --}}
    <div class="agricart-product-form__categories">
        <div class="agricart-product-form__category-col agricart-product-form__category-col--parent">
            <label class="agricart-product-form__row-label" for="agricart-product-parent-search">
                {{ __('agricart.products.fields.parent_category') }}
            </label>

            <div class="agricart-category-form__picker agricart-product-form__search">
                <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                    <input
                        id="agricart-product-parent-search"
                        type="search"
                        wire:model.live.debounce.300ms="parent_search"
                        class="fi-input fi-input-has-inline-prefix"
                        placeholder="{{ __('agricart.products.hints.parent_search') }}"
                        @disabled($readonly)
                    />
                </x-filament::input.wrapper>

                @if (mb_strlen(trim($parent_search)) >= 2)
                    <div class="agricart-category-form__picker-results" role="listbox">
                        @forelse ($filteredParentOptions as $id => $label)
                            <button
                                type="button"
                                class="agricart-category-form__picker-result"
                                wire:click="selectParent({{ $id }})"
                                wire:key="product-parent-{{ $id }}"
                                role="option"
                                @disabled($readonly)
                            >
                                {{ $label }}
                            </button>
                        @empty
                            <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                {{ __('agricart.products.hints.parent_empty') }}
                            </p>
                        @endforelse
                    </div>
                @endif
            </div>

            {{-- Parent row: hierarchy first, then main product image (6 boxes) --}}
            <div class="agricart-product-form__icons agricart-product-form__icons--parent" role="list">
                @for ($slot = 0; $slot < 5; $slot++)
                    @php $preview = $hierarchySlotPreviews[$slot] ?? ['url' => null, 'name' => null]; @endphp
                    <div
                        class="agricart-category-form__icon-slot agricart-category-form__icon-slot--hierarchy"
                        role="listitem"
                        title="{{ $preview['name'] ?? __('agricart.products.hints.hierarchy_slot', ['number' => $slot + 1]) }}"
                        wire:key="product-hierarchy-{{ $slot }}-{{ $hierarchySlots[$slot] ?? 'empty' }}"
                    >
                        @if ($preview['url'])
                            <img src="{{ $preview['url'] }}" alt="" class="agricart-category-form__icon-preview" />
                        @else
                            <x-filament::icon :icon="Heroicon::OutlinedPlus" class="agricart-category-form__icon-placeholder" />
                        @endif
                    </div>
                @endfor

                <div class="agricart-product-form__image-slot" role="listitem">
                    <label class="agricart-category-form__icon-slot agricart-category-form__icon-slot--upload agricart-product-form__image-upload" title="{{ __('agricart.products.fields.image') }}">
                        @if ($imagePreviewUrl)
                            <img src="{{ $imagePreviewUrl }}" alt="" class="agricart-category-form__icon-preview" />
                        @else
                            <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-category-form__icon-placeholder" />
                        @endif
                        @unless ($readonly)
                            <input type="file" wire:model="imageUpload" accept="image/*" class="agricart-category-form__file-input" />
                        @endunless
                    </label>
                    <div wire:loading wire:target="imageUpload" class="agricart-category-form__upload-hint">
                        {{ __('agricart.categories.image.uploading') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="agricart-product-form__category-col agricart-product-form__category-col--names">
            <div class="agricart-form-panel__field agricart-product-form__name-field">
                <label class="agricart-form-panel__label" for="agricart-product-name-en">
                    {{ __('agricart.form_panel.categories.english_name') }}
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-product-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-product-form__name-field">
                <label class="agricart-form-panel__label" for="agricart-product-name-ur">
                    {{ __('agricart.form_panel.categories.urdu_name') }}
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-product-name-ur" type="text" wire:model="name_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="agricart-product-form__category-col agricart-product-form__category-col--display">
            <label class="agricart-product-form__row-label" for="agricart-product-display-search">
                {{ __('agricart.products.fields.display_categories') }}
            </label>

            <div class="agricart-category-form__picker agricart-product-form__search">
                @if ($selectedDisplayCategories !== [])
                    <div class="agricart-category-form__picker-chips">
                        @foreach ($selectedDisplayCategories as $id => $label)
                            <span class="agricart-category-form__picker-chip" wire:key="product-display-chip-{{ $id }}">
                                <span>{{ $label }}</span>
                                @unless ($readonly)
                                    <button type="button" class="agricart-category-form__picker-chip-remove" wire:click="removeDisplayCategory({{ $id }})" title="{{ __('agricart.products.actions.remove') }}">×</button>
                                @endunless
                            </span>
                        @endforeach
                    </div>
                @endif

                <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                    <input
                        id="agricart-product-display-search"
                        type="search"
                        wire:model.live.debounce.300ms="display_category_search"
                        class="fi-input fi-input-has-inline-prefix"
                        placeholder="{{ __('agricart.products.hints.display_search') }}"
                        @disabled($readonly)
                    />
                </x-filament::input.wrapper>

                @if (mb_strlen(trim($display_category_search)) >= 2)
                    <div class="agricart-category-form__picker-results" role="listbox">
                        @forelse ($filteredDisplayCategoryOptions as $id => $label)
                            <button
                                type="button"
                                class="agricart-category-form__picker-result"
                                wire:click="addDisplayCategory({{ $id }})"
                                wire:key="product-display-{{ $id }}"
                                role="option"
                                @disabled($readonly)
                            >
                                {{ $label }}
                            </button>
                        @empty
                            <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                {{ __('agricart.products.hints.display_empty') }}
                            </p>
                        @endforelse
                    </div>
                @endif
            </div>

            <div class="agricart-product-form__icons agricart-product-form__icons--display" role="list">
                @for ($slot = 0; $slot < 4; $slot++)
                    <div class="agricart-product-form__image-slot" role="listitem" wire:key="product-gallery-{{ $slot }}">
                        <label
                            class="agricart-category-form__icon-slot agricart-category-form__icon-slot--upload agricart-product-form__image-upload"
                            title="{{ __('agricart.products.fields.gallery_image') }} {{ $slot + 1 }}"
                        >
                            @if ($galleryPreviewUrls[$slot] ?? null)
                                <img src="{{ $galleryPreviewUrls[$slot] }}" alt="" class="agricart-category-form__icon-preview" />
                            @else
                                <x-filament::icon :icon="Heroicon::OutlinedPlus" class="agricart-category-form__icon-placeholder" />
                            @endif
                            @unless ($readonly)
                                <input type="file" wire:model="galleryUploads.{{ $slot }}" accept="image/*" class="agricart-category-form__file-input" />
                            @endunless
                        </label>
                        @if (($galleryPreviewUrls[$slot] ?? null) && ! $readonly)
                            <button
                                type="button"
                                class="agricart-product-form__image-remove"
                                wire:click="removeGalleryImage({{ $slot }})"
                                title="{{ __('agricart.products.actions.remove') }}"
                            >×</button>
                        @endif
                        <div wire:loading wire:target="galleryUploads.{{ $slot }}" class="agricart-category-form__upload-hint">
                            {{ __('agricart.categories.image.uploading') }}
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Row 2: Controls, brand, attributes --}}
    <div class="agricart-product-form__identity">
        <div class="agricart-product-form__controls-field">
            <label class="agricart-form-panel__label" for="agricart-product-control-search">
                {{ __('agricart.products.fields.controls') }}
            </label>
            <div class="agricart-category-form__picker">
                @if ($selectedControls !== [])
                    <div class="agricart-product-form__control-chips">
                        @foreach ($selectedControls as $control)
                            <span class="agricart-product-form__control-chip" wire:key="product-control-{{ $control['id'] }}">
                                <span class="agricart-product-form__control-chip-name">{{ $control['name_en'] }}</span>
                                <span class="agricart-product-form__control-chip-value">{{ $control['value_en'] }}</span>
                                @unless ($readonly)
                                    <button type="button" class="agricart-category-form__picker-chip-remove" wire:click="removeControl({{ $control['id'] }})" title="{{ __('agricart.products.actions.remove') }}">×</button>
                                @endunless
                            </span>
                        @endforeach
                    </div>
                @endif
                <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                    <input
                        id="agricart-product-control-search"
                        type="search"
                        wire:model.live.debounce.300ms="control_search"
                        class="fi-input fi-input-has-inline-prefix"
                        placeholder="{{ __('agricart.products.hints.control_search') }}"
                        @disabled($readonly)
                    />
                </x-filament::input.wrapper>
                @if (mb_strlen(trim($control_search)) >= 2)
                    <div class="agricart-category-form__picker-results" role="listbox">
                        @forelse ($filteredControlOptions as $id => $label)
                            <button
                                type="button"
                                class="agricart-category-form__picker-result"
                                wire:click="addControl({{ $id }})"
                                wire:key="product-control-result-{{ $id }}"
                                role="option"
                                @disabled($readonly)
                            >
                                {{ $label }}
                            </button>
                        @empty
                            <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                {{ __('agricart.products.hints.control_empty') }}
                            </p>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__brand-field">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.brand') }}</label>
            <div class="agricart-product-form__brand-input-row">
                <div class="agricart-category-form__picker agricart-product-form__brand-picker">
                    @if ($selectedBrand['label'])
                        <span class="agricart-product-form__inline-chip agricart-product-form__brand-chip">
                            <span>{{ $selectedBrand['label'] }}</span>
                            @unless ($readonly)
                                <button type="button" wire:click="clearBrand()" title="{{ __('agricart.products.actions.remove') }}">×</button>
                            @endunless
                        </span>
                    @else
                        <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="brand_search"
                                class="fi-input fi-input-has-inline-prefix"
                                placeholder="{{ __('agricart.products.hints.brand_search') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                        @if (mb_strlen(trim($brand_search)) >= 2)
                            <div class="agricart-category-form__picker-results" role="listbox">
                                @forelse ($filteredBrandOptions as $id => $label)
                                    <button
                                        type="button"
                                        class="agricart-category-form__picker-result"
                                        wire:click="selectBrand({{ $id }})"
                                        wire:key="product-brand-{{ $id }}"
                                        role="option"
                                        @disabled($readonly)
                                    >
                                        {{ $label }}
                                    </button>
                                @empty
                                    <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                        {{ __('agricart.products.hints.brand_empty') }}
                                    </p>
                                @endforelse
                            </div>
                        @endif
                    @endif
                </div>
                <div class="agricart-product-form__brand-logo" title="{{ __('agricart.products.fields.brand_logo') }}">
                    @if ($selectedBrand['logo_url'])
                        <img src="{{ $selectedBrand['logo_url'] }}" alt="" />
                    @else
                        <x-filament::icon :icon="Heroicon::OutlinedCube" class="agricart-product-form__brand-logo-placeholder" />
                    @endif
                </div>
            </div>
        </div>

        <div class="agricart-product-form__attributes">
            <div class="agricart-product-form__attributes-header">
                <span class="agricart-form-panel__label">{{ __('agricart.products.fields.attributes') }}</span>
                <span class="agricart-form-panel__label">{{ __('agricart.products.fields.attribute_value') }}</span>
                <span class="agricart-product-form__attributes-header-spacer" aria-hidden="true"></span>
            </div>
            @foreach ($attributeRows as $index => $row)
                @php
                    $selectedAttributeLabel = $attributeLabelsByRow[$index] ?? null;
                    $rowAttributeOptions = $filteredAttributeOptionsByRow[$index] ?? [];
                @endphp
                <div class="agricart-product-form__attribute-row" wire:key="product-attribute-{{ $index }}">
                    <div class="agricart-form-panel__field agricart-product-form__attribute-select">
                        <div class="agricart-category-form__picker agricart-product-form__attribute-picker agricart-product-form__picker-slot">
                            @if ($selectedAttributeLabel)
                                <span class="agricart-product-form__inline-chip">
                                    <span>{{ $selectedAttributeLabel }}</span>
                                    @unless ($readonly)
                                        <button type="button" wire:click="clearAttribute({{ $index }})" title="{{ __('agricart.products.actions.remove') }}">×</button>
                                    @endunless
                                </span>
                            @else
                                <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                                    <input
                                        type="search"
                                        wire:model.live.debounce.200ms="attributeSearches.{{ $index }}"
                                        class="fi-input fi-input-has-inline-prefix"
                                        placeholder="{{ __('agricart.products.hints.attribute_search') }}"
                                        @disabled($readonly)
                                    />
                                </x-filament::input.wrapper>
                                @if (mb_strlen(trim($attributeSearches[$index] ?? '')) >= 2)
                                    <div class="agricart-category-form__picker-results" role="listbox">
                                        @forelse ($rowAttributeOptions as $id => $label)
                                            <button
                                                type="button"
                                                class="agricart-category-form__picker-result"
                                                wire:click="selectAttribute({{ $index }}, {{ $id }})"
                                                wire:key="product-attribute-option-{{ $index }}-{{ $id }}"
                                                role="option"
                                                @disabled($readonly)
                                            >
                                                {{ $label }}
                                            </button>
                                        @empty
                                            <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                                {{ __('agricart.products.hints.attribute_empty') }}
                                            </p>
                                        @endforelse
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="agricart-form-panel__field agricart-product-form__attribute-value">
                        <x-filament::input.wrapper>
                            <input
                                type="text"
                                wire:model="attributeRows.{{ $index }}.value"
                                class="fi-input"
                                placeholder="{{ __('agricart.products.hints.attribute_value') }}"
                                @disabled($readonly)
                            />
                        </x-filament::input.wrapper>
                    </div>
                    @unless ($readonly)
                        <div class="agricart-product-form__attribute-actions">
                            @if ($index === count($attributeRows) - 1)
                                <button
                                    type="button"
                                    class="agricart-product-form__icon-btn"
                                    wire:click="addAttributeRow()"
                                    title="{{ __('agricart.products.actions.add_attribute') }}"
                                >
                                    <x-filament::icon :icon="Heroicon::OutlinedPlus" />
                                </button>
                            @endif
                            @if (count($attributeRows) > 1)
                                <button
                                    type="button"
                                    class="agricart-product-form__icon-btn agricart-product-form__icon-btn--muted"
                                    wire:click="removeAttributeRow({{ $index }})"
                                    title="{{ __('agricart.products.actions.remove') }}"
                                >
                                    <x-filament::icon :icon="Heroicon::OutlinedXMark" />
                                </button>
                            @endif
                        </div>
                    @endunless
                </div>
            @endforeach
        </div>
    </div>

    {{-- Row 3: Units & quantities --}}
    <div class="agricart-product-form__quantities">
        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--wide">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.unit') }}</label>
            <div class="agricart-category-form__picker agricart-product-form__inline-picker agricart-product-form__picker-slot">
                @if ($selectedUnitLabel)
                    <span class="agricart-product-form__inline-chip">{{ $selectedUnitLabel }}
                        @unless ($readonly)
                            <button type="button" wire:click="clearUnit()">×</button>
                        @endunless
                    </span>
                @else
                    <x-filament::input.wrapper>
                        <input type="search" wire:model.live.debounce.200ms="unit_search" class="fi-input" placeholder="{{ __('agricart.products.hints.unit_search') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                    @if (filled(trim($unit_search)) && $filteredUnitOptions !== [])
                        <div class="agricart-category-form__picker-results">
                            @foreach ($filteredUnitOptions as $id => $label)
                                <button type="button" class="agricart-category-form__picker-result" wire:click="selectUnit({{ $id }})" wire:key="product-unit-{{ $id }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--narrow">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.qty') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="unit_qty" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--wide">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.pack_unit') }}</label>
            <div class="agricart-category-form__picker agricart-product-form__inline-picker agricart-product-form__picker-slot">
                @if ($selectedPackUnitLabel)
                    <span class="agricart-product-form__inline-chip">{{ $selectedPackUnitLabel }}
                        @unless ($readonly)
                            <button type="button" wire:click="clearPackUnit()">×</button>
                        @endunless
                    </span>
                @else
                    <x-filament::input.wrapper>
                        <input type="search" wire:model.live.debounce.200ms="pack_unit_search" class="fi-input" placeholder="{{ __('agricart.products.hints.pack_unit_search') }}" @disabled($readonly) />
                    </x-filament::input.wrapper>
                    @if (filled(trim($pack_unit_search)) && $filteredPackUnitOptions !== [])
                        <div class="agricart-category-form__picker-results">
                            @foreach ($filteredPackUnitOptions as $id => $label)
                                <button type="button" class="agricart-category-form__picker-result" wire:click="selectPackUnit({{ $id }})" wire:key="product-pack-unit-{{ $id }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--narrow">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.qty') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="pack_qty" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--narrow">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.required_qty') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="required_qty" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--narrow">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.alert_qty') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="alert_qty" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--rate">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.retail') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="retail_price" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--rate">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.wholesale') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="wholesale_price" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--rate">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.super_wholesale') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="super_wholesale_price" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>

        <div class="agricart-form-panel__field agricart-product-form__qty-field agricart-product-form__qty-field--rate">
            <label class="agricart-form-panel__label">{{ __('agricart.products.fields.distributor') }}</label>
            <x-filament::input.wrapper>
                <input type="text" wire:model="distributor_price" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
        </div>
    </div>

    {{-- Additional Information --}}
    <div class="agricart-product-form__details">
        <h3 class="agricart-form-panel__section-title agricart-product-form__details-heading">{{ __('agricart.products.sections.additional') }}</h3>
        @include('livewire.products.partials.additional-sections')
    </div>
</div>
