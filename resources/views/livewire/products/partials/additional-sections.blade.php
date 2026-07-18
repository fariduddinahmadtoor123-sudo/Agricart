@php
    $readonly = $this->isReadonly();
@endphp

<div class="agricart-product-form__details-inner">
    <section class="agricart-form-panel__section agricart-product-form__section">
        <div class="agricart-form-panel__fields">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.products.fields.system_code') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" value="{{ $code ?: __('agricart.products.hints.auto_on_save') }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.products.hints.system_code') }}</p>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.products.fields.sku') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="sku" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.form_panel.fields.status') }}</label>
                <x-filament::input.wrapper>
                    <select wire:model="status" class="fi-select-input" @disabled($readonly)>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-product-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.products.sections.descriptive') }}</h3>
        <div class="agricart-form-panel__fields">
            @foreach ([
                ['base' => 'short_description', 'label' => __('agricart.categories.fields.short_description'), 'rows' => 2],
                ['base' => 'description', 'label' => __('agricart.products.fields.full_description'), 'rows' => 4],
                ['base' => 'usage_guidelines', 'label' => __('agricart.categories.fields.usage_guidelines'), 'rows' => 3],
                ['base' => 'benefits', 'label' => __('agricart.categories.fields.benefits'), 'rows' => 3],
                ['base' => 'warnings', 'label' => __('agricart.categories.fields.warnings'), 'rows' => 3],
                ['base' => 'buying_guide', 'label' => __('agricart.categories.fields.buying_guide'), 'rows' => 3],
            ] as $field)
                <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__bilingual">
                    <span class="agricart-category-form__bilingual-label">{{ $field['label'] }}</span>
                    <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.english') }}</label>
                            <x-filament::input.wrapper>
                                <textarea rows="{{ $field['rows'] }}" wire:model="{{ $field['base'] }}_en" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.urdu') }}</label>
                            <x-filament::input.wrapper>
                                <textarea rows="{{ $field['rows'] }}" wire:model="{{ $field['base'] }}_ur" dir="rtl" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-product-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.products.sections.seo') }}</h3>
        <div class="agricart-form-panel__fields">
            @foreach ([
                ['base' => 'seo_title', 'label' => __('agricart.categories.fields.seo_title'), 'rows' => 1, 'textarea' => false],
                ['base' => 'seo_description', 'label' => __('agricart.categories.fields.seo_description'), 'rows' => 3, 'textarea' => true],
                ['base' => 'seo_keywords', 'label' => __('agricart.categories.fields.seo_keywords'), 'rows' => 1, 'textarea' => false],
                ['base' => 'seo_focus_keyword', 'label' => __('agricart.categories.fields.seo_focus_keyword'), 'rows' => 1, 'textarea' => false],
            ] as $field)
                <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__bilingual">
                    <span class="agricart-category-form__bilingual-label">{{ $field['label'] }}</span>
                    <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.english') }}</label>
                            <x-filament::input.wrapper>
                                @if ($field['textarea'])
                                    <textarea rows="{{ $field['rows'] }}" wire:model="{{ $field['base'] }}_en" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                                @else
                                    <input type="text" wire:model="{{ $field['base'] }}_en" class="fi-input" @disabled($readonly) />
                                @endif
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.urdu') }}</label>
                            <x-filament::input.wrapper>
                                @if ($field['textarea'])
                                    <textarea rows="{{ $field['rows'] }}" wire:model="{{ $field['base'] }}_ur" dir="rtl" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                                @else
                                    <input type="text" wire:model="{{ $field['base'] }}_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                                @endif
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-product-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.products.sections.technical') }}</h3>
        <div class="agricart-form-panel__fields">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.hs_code') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" wire:model="hs_code" class="fi-input" @disabled($readonly) />
                </x-filament::input.wrapper>
            </div>

            @foreach ([
                ['base' => 'customs_notes', 'label' => __('agricart.categories.fields.customs_notes')],
                ['base' => 'import_notes', 'label' => __('agricart.categories.fields.import_notes')],
                ['base' => 'export_notes', 'label' => __('agricart.categories.fields.export_notes')],
            ] as $field)
                <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__bilingual">
                    <span class="agricart-category-form__bilingual-label">{{ $field['label'] }}</span>
                    <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.english') }}</label>
                            <x-filament::input.wrapper>
                                <textarea rows="3" wire:model="{{ $field['base'] }}_en" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.labels.urdu') }}</label>
                            <x-filament::input.wrapper>
                                <textarea rows="3" wire:model="{{ $field['base'] }}_ur" dir="rtl" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                            </x-filament::input.wrapper>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.search_terms') }}</label>
                <p class="agricart-form-panel__hint agricart-category-form__json-hint">{{ __('agricart.categories.hints.search_terms') }}</p>
                <x-filament::input.wrapper>
                    <textarea rows="3" wire:model="search_terms_json" class="fi-input agricart-form-panel__textarea agricart-category-form__json-input" @disabled($readonly)></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-product-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.products.sections.labels') }}</h3>
        <p class="agricart-form-panel__hint agricart-product-form__label-hint">{{ __('agricart.products.hints.product_label') }}</p>
        <div class="agricart-form-panel__fields">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.products.fields.category_visual_map_code') }}</label>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        value="{{ $categoryVisualMapCode ?? __('agricart.products.hints.select_parent_category') }}"
                        class="fi-input"
                        readonly
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.products.fields.label_code') }}</label>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        value="{{ $labelCodePreview ?? __('agricart.products.hints.label_pending') }}"
                        class="fi-input agricart-product-form__map-code"
                        readonly
                    />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.products.hints.label_numbering') }}</p>
            </div>
        </div>

        <div class="agricart-product-form__label-previews">
            <div class="agricart-product-form__label-card">
                <span class="agricart-product-form__label-card-title">{{ __('agricart.products.fields.label_qr') }}</span>
                <div class="agricart-product-form__label-card-body">
                    <span class="agricart-product-form__label-card-code">{{ $labelCodePreview ?? '—' }}</span>
                    <span class="agricart-product-form__label-card-placeholder">{{ __('agricart.products.hints.label_preview_qr') }}</span>
                </div>
            </div>
            <div class="agricart-product-form__label-card">
                <span class="agricart-product-form__label-card-title">{{ __('agricart.products.fields.label_barcode') }}</span>
                <div class="agricart-product-form__label-card-body">
                    <span class="agricart-product-form__label-card-code">{{ $labelCodePreview ?? '—' }}</span>
                    <span class="agricart-product-form__label-card-barcode" aria-hidden="true"></span>
                    <span class="agricart-product-form__label-card-placeholder">{{ __('agricart.products.hints.label_preview_barcode') }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-product-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.products.sections.system') }}</h3>
        <div class="agricart-form-panel__fields">
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label">{{ __('agricart.products.ai.ai_message') }}</label>
                <p class="agricart-form-panel__hint">{{ __('agricart.products.ai.ai_message_hint') }}</p>
                <textarea
                    rows="9"
                    wire:model="enrichmentPrompt"
                    class="agricart-product-form__ai-prompt"
                    @disabled($readonly)
                >{{ $enrichmentPrompt }}</textarea>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.ai_status') }}</label>
                <x-filament::input.wrapper>
                    <select wire:model="ai_status" class="fi-select-input" disabled>
                        @foreach ($aiStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.products.fields.ai_generated_at') }}</label>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        value="{{ $ai_generated_at ?: __('agricart.products.hints.not_set') }}"
                        class="fi-input"
                        readonly
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label">{{ __('agricart.products.fields.ai_version') }}</label>
                <x-filament::input.wrapper>
                    <input type="text" value="{{ $ai_version ?: __('agricart.products.hints.not_set') }}" class="fi-input" readonly />
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>
</div>
