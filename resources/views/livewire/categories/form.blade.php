@php
    use App\Filament\Forms\CategoryForm;
    use Filament\Support\Icons\Heroicon;

    $sectionsByKey = collect($sections)->keyBy('key');
    $sectionsWithoutTitle = ['discovery', 'core'];
    $readonly = $this->isReadonly();
@endphp

<div
    class="agricart-category-form"
    style="position: relative;"
    x-data
    x-init="
        $watch('$wire.enriching', (value) => {
            window.dispatchEvent(new CustomEvent('category-form-busy', {
                detail: { enriching: !!value, saving: !!$wire.saving },
                bubbles: true,
            }));
        });
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('category-form-busy', {
                detail: { enriching: !!$wire.enriching, saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:category-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:category-form-open.window="$wire.openForm($event.detail.categoryId ?? null, $event.detail.mode ?? 'create')"
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

    {{-- Frozen hero --}}
    <div class="agricart-category-form__hero" id="agricart-cat-section-hero">
        <div class="agricart-category-form__search agricart-category-form__hero-parent-search">
            <label class="fi-sr-only" for="agricart-cat-parent-search">{{ __('agricart.form_panel.categories.hero_parent_search_aria') }}</label>
            @if ($selectedParentLabel)
                <div class="agricart-category-form__picker-chips agricart-category-form__hero-parent-chip">
                    <span class="agricart-category-form__picker-chip agricart-category-form__picker-chip--single">
                        <span>{{ __('agricart.categories.hints.hero_parent_selected') }}: {{ $selectedParentLabel }}</span>
                        @unless ($readonly)
                            <button type="button" class="agricart-category-form__picker-chip-remove" wire:click="clearParent()" title="{{ __('agricart.categories.faqs.remove') }}">×</button>
                        @endunless
                    </span>
                </div>
            @endif
            <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                <input
                    id="agricart-cat-parent-search"
                    type="search"
                    wire:model.live.debounce.300ms="parent_search"
                    class="fi-input fi-input-has-inline-prefix"
                    placeholder="{{ __('agricart.form_panel.categories.hero_parent_search') }}"
                    @disabled($readonly)
                />
            </x-filament::input.wrapper>
            @if (mb_strlen(trim($parent_search)) >= 2)
                <div class="agricart-category-form__picker-results agricart-category-form__hero-parent-results" role="listbox">
                    @forelse ($filteredParentOptions as $id => $label)
                        <button
                            type="button"
                            class="agricart-category-form__picker-result"
                            wire:click="selectParent({{ $id }})"
                            wire:key="hero-parent-{{ $id }}"
                            role="option"
                            @disabled($readonly)
                        >
                            {{ $label }}
                        </button>
                    @empty
                        <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                            {{ __('agricart.categories.hints.parent_empty') }}
                        </p>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="agricart-category-form__hero-row">
            <div class="agricart-category-form__hero-icons">
                <div class="agricart-category-form__icons" role="list">
                    @for ($slot = 0; $slot < 4; $slot++)
                        @php
                            $preview = $hierarchySlotPreviews[$slot] ?? ['url' => null, 'name' => null];
                        @endphp
                        <div
                            class="agricart-category-form__icon-slot agricart-category-form__icon-slot--hierarchy"
                            role="listitem"
                            title="{{ $preview['name'] ?? __('agricart.form_panel.categories.select_icon', ['number' => $slot + 1]) }}"
                            wire:key="hierarchy-slot-{{ $slot }}-{{ $hierarchySlots[$slot] ?? 'empty' }}"
                        >
                            @if ($preview['url'])
                                <img src="{{ $preview['url'] }}" alt="" class="agricart-category-form__icon-preview" />
                            @else
                                <x-filament::icon :icon="Heroicon::OutlinedMoon" class="agricart-category-form__icon-placeholder" />
                            @endif
                        </div>
                    @endfor

                    <label class="agricart-category-form__icon-slot agricart-category-form__icon-slot--upload" title="{{ __('agricart.categories.image.upload') }}">
                        @if ($imagePreviewUrl)
                            <img src="{{ $imagePreviewUrl }}" alt="" class="agricart-category-form__icon-preview" />
                        @else
                            <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-category-form__icon-placeholder" />
                        @endif
                        @unless ($readonly)
                            <input type="file" wire:model="imageUpload" accept="image/*" class="agricart-category-form__file-input" />
                        @endunless
                    </label>
                </div>
                <div wire:loading wire:target="imageUpload" class="agricart-category-form__upload-hint">
                    {{ __('agricart.categories.image.uploading') }}
                </div>
            </div>

            <div class="agricart-category-form__hero-names">
                <div class="agricart-category-form__names">
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label" for="agricart-cat-name-en">
                            {{ __('agricart.form_panel.categories.english_name') }}
                            <span class="agricart-form-panel__required">*</span>
                        </label>
                        <x-filament::input.wrapper>
                            <input id="agricart-cat-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($readonly) required />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field">
                        <label class="agricart-form-panel__label" for="agricart-cat-name-ur">
                            {{ __('agricart.form_panel.categories.urdu_name') }}
                            <span class="agricart-form-panel__required">*</span>
                        </label>
                        <x-filament::input.wrapper>
                            <input id="agricart-cat-name-ur" type="text" wire:model="name_ur" dir="rtl" class="fi-input" @disabled($readonly) required />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scrollable details --}}
    <div class="agricart-category-form__details">
        @foreach ($detailSectionKeys as $sectionKey)
            @php
                $section = $sectionsByKey->get($sectionKey);
                if (! $section) continue;
                $showTitle = ! in_array($sectionKey, $sectionsWithoutTitle, true);
                $anchorId = in_array($sectionKey, array_values($scrollAnchors), true) ? 'agricart-cat-section-' . $sectionKey : null;
            @endphp

            <section
                @if ($anchorId) id="{{ $anchorId }}" @endif
                @class(['agricart-form-panel__section', 'agricart-category-form__section', 'agricart-category-form__section--' . $sectionKey])
            >
                @if ($showTitle)
                    <h3 class="agricart-form-panel__section-title">{{ $section['label'] }}</h3>
                @endif

                <div class="agricart-form-panel__fields">
                    @if ($sectionKey === 'core')
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.visual_map_code') }}</label>
                            <x-filament::input.wrapper>
                                <input type="text" wire:model="visual_map_code" class="fi-input agricart-category-form__map-code" readonly />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.display_in') }}</label>
                            <div class="agricart-category-form__picker">
                                @if ($selectedDisplayCategories !== [])
                                    <div class="agricart-category-form__picker-chips">
                                        @foreach ($selectedDisplayCategories as $id => $label)
                                            <span class="agricart-category-form__picker-chip" wire:key="display-chip-{{ $id }}">
                                                <span>{{ $label }}</span>
                                                @unless ($readonly)
                                                    <button type="button" class="agricart-category-form__picker-chip-remove" wire:click="removeDisplayCategory({{ $id }})" title="{{ __('agricart.categories.faqs.remove') }}">×</button>
                                                @endunless
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                                    <input
                                        type="search"
                                        wire:model.live.debounce.300ms="display_category_search"
                                        class="fi-input fi-input-has-inline-prefix"
                                        placeholder="{{ __('agricart.categories.hints.display_in_search') }}"
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
                                                wire:key="display-result-{{ $id }}"
                                                role="option"
                                                @disabled($readonly)
                                            >
                                                {{ $label }}
                                            </button>
                                        @empty
                                            <p class="agricart-form-panel__hint agricart-category-form__picker-empty">
                                                {{ __('agricart.categories.hints.display_in_empty') }}
                                            </p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                        </div>
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.slug') }}</label>
                            <x-filament::input.wrapper>
                                <input type="text" wire:model="slug" class="fi-input" placeholder="{{ __('agricart.categories.hints.slug_auto') }}" @disabled($readonly) />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.code') }}</label>
                            <x-filament::input.wrapper>
                                <input type="text" wire:model="code" class="fi-input" @disabled($readonly) />
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.status') }}</label>
                            <x-filament::input.wrapper>
                                <select wire:model="status" class="fi-select-input" @disabled($readonly)>
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-filament::input.wrapper>
                        </div>
                    @endif

                    @if ($sectionKey === 'content' || $sectionKey === 'seo' || $sectionKey === 'compliance')
                        @foreach ($section['fields'] as $field)
                            @include('livewire.categories.partials.field', ['field' => $field, 'readonly' => $readonly])
                        @endforeach
                    @endif

                    @if ($sectionKey === 'faqs')
                        <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__faqs">
                            <div class="agricart-category-form__faqs-header">
                                <span class="agricart-category-form__bilingual-label">{{ __('agricart.categories.sections.faqs') }}</span>
                                @unless ($readonly)
                                    <x-filament::button color="gray" outlined size="sm" type="button" wire:click="addFaq">
                                        {{ __('agricart.categories.faqs.add') }}
                                    </x-filament::button>
                                @endunless
                            </div>
                            @if ($faqs === [])
                                <p class="agricart-form-panel__hint">{{ __('agricart.categories.faqs.empty') }}</p>
                            @endif
                            @foreach ($faqs as $index => $faq)
                                <div class="agricart-category-form__faq-item" wire:key="faq-{{ $index }}">
                                    <div class="agricart-category-form__faq-item-head">
                                        <span class="agricart-category-form__faq-index">{{ __('agricart.categories.faqs.item', ['number' => $index + 1]) }}</span>
                                        @unless ($readonly)
                                            <button type="button" class="agricart-category-form__faq-remove" wire:click="removeFaq({{ $index }})">{{ __('agricart.categories.faqs.remove') }}</button>
                                        @endunless
                                    </div>
                                    <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                                        <div class="agricart-form-panel__field">
                                            <label class="agricart-form-panel__label">{{ __('agricart.categories.faqs.question_en') }}</label>
                                            <x-filament::input.wrapper>
                                                <input type="text" wire:model="faqs.{{ $index }}.question_en" class="fi-input" @disabled($readonly) />
                                            </x-filament::input.wrapper>
                                        </div>
                                        <div class="agricart-form-panel__field">
                                            <label class="agricart-form-panel__label">{{ __('agricart.categories.faqs.question_ur') }}</label>
                                            <x-filament::input.wrapper>
                                                <input type="text" wire:model="faqs.{{ $index }}.question_ur" dir="rtl" class="fi-input" @disabled($readonly) />
                                            </x-filament::input.wrapper>
                                        </div>
                                    </div>
                                    <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                                        <div class="agricart-form-panel__field">
                                            <label class="agricart-form-panel__label">{{ __('agricart.categories.faqs.answer_en') }}</label>
                                            <x-filament::input.wrapper>
                                                <textarea rows="3" wire:model="faqs.{{ $index }}.answer_en" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                                            </x-filament::input.wrapper>
                                        </div>
                                        <div class="agricart-form-panel__field">
                                            <label class="agricart-form-panel__label">{{ __('agricart.categories.faqs.answer_ur') }}</label>
                                            <x-filament::input.wrapper>
                                                <textarea rows="3" wire:model="faqs.{{ $index }}.answer_ur" dir="rtl" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                                            </x-filament::input.wrapper>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($sectionKey === 'system')
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.ai_prompt') }}</label>
                            <x-filament::input.wrapper>
                                <textarea rows="4" wire:model="enrichmentPrompt" class="fi-input agricart-form-panel__textarea" @disabled($readonly)></textarea>
                            </x-filament::input.wrapper>
                        </div>
                        <div class="agricart-form-panel__field agricart-form-panel__field--full">
                            <label class="agricart-form-panel__label">{{ __('agricart.categories.fields.ai_status') }}</label>
                            <x-filament::input.wrapper>
                                <select wire:model="ai_status" class="fi-select-input" disabled>
                                    @foreach ($aiStatusOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-filament::input.wrapper>
                        </div>
                        <p class="agricart-form-panel__hint agricart-category-form__ai-hint">{{ __('agricart.categories.hints.ai_enrichment') }}</p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    @if ($saving)
        <div class="agricart-category-form__loading" role="status" aria-live="polite">
            <span>{{ __('agricart.categories.messages.saving') }}</span>
        </div>
    @endif
</div>
