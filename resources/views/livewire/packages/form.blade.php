@php use Filament\Support\Icons\Heroicon; @endphp

<div
    class="agricart-package-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('packages-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:packages-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:packages-form-open.window="$wire.openForm($event.detail.packageId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-category-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-brand-form__hero agricart-category-form__hero">
        <div class="agricart-brand-form__image-col">
            <label class="agricart-brand-form__image-upload" title="{{ __('agricart.packages.image.upload') }}">
                @if ($imagePreviewUrl)
                    <img src="{{ $imagePreviewUrl }}" alt="" class="agricart-brand-form__image-preview" />
                @else
                    <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-brand-form__image-placeholder" />
                    <span class="agricart-brand-form__image-label">{{ __('agricart.packages.image.upload') }}</span>
                @endif
                @unless ($this->isReadonly())
                    <input type="file" wire:model="imageUpload" accept="image/*" class="agricart-category-form__file-input" />
                @endunless
            </label>
            <div wire:loading wire:target="imageUpload" class="agricart-brand-form__upload-hint">
                {{ __('agricart.packages.image.uploading') }}
            </div>
        </div>

        <div class="agricart-category-form__hero-names">
            <div class="agricart-category-form__names">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-package-name-en">
                        {{ __('agricart.packages.fields.name_en') }}
                        <span class="agricart-form-panel__required">*</span>
                    </label>
                    <x-filament::input.wrapper>
                        <input id="agricart-package-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($this->isReadonly()) required />
                    </x-filament::input.wrapper>
                    <p class="agricart-form-panel__hint">{{ __('agricart.packages.hints.name_from_file') }}</p>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-package-name-ur">
                        {{ __('agricart.packages.fields.name_ur') }}
                    </label>
                    <x-filament::input.wrapper>
                        <input id="agricart-package-name-ur" type="text" wire:model="name_ur" dir="rtl" class="fi-input" @disabled($this->isReadonly()) />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
    </div>

    <section class="agricart-form-panel__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.packages.sections.details') }}</h3>
        <div class="agricart-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-package-number">{{ __('agricart.packages.fields.package_number') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-package-number" type="text" value="{{ $package_number }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.packages.hints.package_number') }}</p>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-package-slug">{{ __('agricart.packages.fields.slug') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-package-slug" type="text" wire:model="slug" class="fi-input" placeholder="{{ __('agricart.packages.hints.slug_auto') }}" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-package-status">{{ __('agricart.packages.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select id="agricart-package-status" wire:model="status" class="fi-select-input" @disabled($this->isReadonly())>
                        <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                        <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                    </select>
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-package-discount">{{ __('agricart.packages.fields.discount_percent') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-package-discount" type="number" min="0" max="100" step="0.01" wire:model="discount_percent" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.packages.hints.discount_percent') }}</p>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-package-sort">{{ __('agricart.packages.fields.sort_order') }}</label>
                <x-filament::input.wrapper>
                    <input id="agricart-package-sort" type="number" min="0" step="1" wire:model="sort_order" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-package-notes">{{ __('agricart.packages.fields.notes') }}</label>
                <textarea
                    id="agricart-package-notes"
                    rows="3"
                    wire:model="notes"
                    class="agricart-brand-form__textarea"
                    @disabled($this->isReadonly())
                ></textarea>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.packages.sections.categories') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.packages.hints.categories') }}</p>
        <div class="agricart-form-panel__field agricart-form-panel__field--full">
            @if ($selectedCategories !== [])
                <div class="agricart-category-form__picker-chips">
                    @foreach ($selectedCategories as $id => $label)
                        <span class="agricart-category-form__picker-chip" wire:key="package-cat-chip-{{ $id }}">
                            <span>{{ $label }}</span>
                            @unless ($this->isReadonly())
                                <button type="button" class="agricart-category-form__picker-chip-remove" wire:click="removeCategory({{ $id }})" title="{{ __('agricart.packages.actions.remove_category') }}">×</button>
                            @endunless
                        </span>
                    @endforeach
                </div>
            @endif
            @unless ($this->isReadonly())
                <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="category_search"
                        class="fi-input fi-input-has-inline-prefix"
                        placeholder="{{ __('agricart.packages.hints.category_search') }}"
                    />
                </x-filament::input.wrapper>
                @if (mb_strlen(trim($category_search)) >= 2)
                    <div class="agricart-category-form__picker-results" role="listbox">
                        @forelse ($categoryOptions as $id => $label)
                            <button
                                type="button"
                                class="agricart-category-form__picker-result"
                                wire:click="addCategory({{ $id }})"
                                wire:key="package-cat-result-{{ $id }}"
                                role="option"
                            >
                                {{ $label }}
                            </button>
                        @empty
                            <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                {{ __('agricart.packages.hints.category_empty') }}
                            </p>
                        @endforelse
                    </div>
                @endif
            @endunless
        </div>
    </section>

    <section class="agricart-form-panel__section">
        <h3 class="agricart-form-panel__section-title">
            {{ __('agricart.packages.sections.items') }}
            <span class="agricart-form-panel__required">*</span>
        </h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.packages.hints.items') }}</p>

        @unless ($this->isReadonly())
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="product_search"
                        class="fi-input fi-input-has-inline-prefix"
                        placeholder="{{ __('agricart.packages.hints.product_search') }}"
                    />
                </x-filament::input.wrapper>
                @if (mb_strlen(trim($product_search)) >= 2)
                    <div class="agricart-category-form__picker-results" role="listbox">
                        @forelse ($productResults as $result)
                            <button
                                type="button"
                                class="agricart-category-form__picker-result"
                                wire:click="addProduct({{ $result['id'] }})"
                                wire:key="package-product-result-{{ $result['id'] }}"
                                role="option"
                            >
                                {{ $result['label'] }}
                            </button>
                        @empty
                            <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                {{ __('agricart.packages.hints.product_empty') }}
                            </p>
                        @endforelse
                    </div>
                @endif
            </div>
        @endunless

        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('agricart.packages.fields.product') }}</th>
                        <th>{{ __('agricart.packages.fields.quantity') }}</th>
                        @unless ($this->isReadonly())
                            <th>{{ __('agricart.packages.list.col_actions') }}</th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $row)
                        <tr wire:key="package-item-{{ $index }}-{{ $row['product_id'] }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ $row['product_name'] }}
                                @if (filled($row['product_code'] ?? null))
                                    <code>{{ $row['product_code'] }}</code>
                                @endif
                            </td>
                            <td>
                                <x-filament::input.wrapper>
                                    <input
                                        type="number"
                                        min="0.001"
                                        step="0.001"
                                        wire:model="items.{{ $index }}.quantity"
                                        class="fi-input"
                                        @disabled($this->isReadonly())
                                    />
                                </x-filament::input.wrapper>
                            </td>
                            @unless ($this->isReadonly())
                                <td class="agricart-categories__actions">
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="removeItem({{ $index }})">
                                        {{ __('agricart.packages.actions.remove_item') }}
                                    </button>
                                </td>
                            @endunless
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $this->isReadonly() ? 3 : 4 }}">
                                <p class="agricart-form-panel__hint">{{ __('agricart.packages.hints.items_empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
