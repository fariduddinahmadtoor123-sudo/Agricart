@php
    use Filament\Support\Icons\Heroicon;

    $fieldId = 'agricart-cat-' . ($field['name'] ?? ($field['base'] ?? 'field'));
@endphp

@switch($field['type'])
    @case('category_search')
        <div class="agricart-category-form__search agricart-form-panel__field--full">
            <label class="fi-sr-only" for="{{ $fieldId }}">{{ __('agricart.form_panel.categories.search_aria') }}</label>
            <x-filament::input.wrapper :prefix-icon="Heroicon::OutlinedMagnifyingGlass" inline-prefix>
                <input
                    id="{{ $fieldId }}"
                    type="search"
                    autocomplete="off"
                    class="fi-input fi-input-has-inline-prefix"
                    placeholder="{{ __('agricart.form_panel.categories.search') }}"
                />
            </x-filament::input.wrapper>
        </div>
        @break

    @case('visual_mapping')
        <div class="agricart-category-form__icons-block">
            <div class="agricart-category-form__icons" role="list">
                <template x-for="(slot, index) in categoryVisualMapping.slots" :key="index">
                    <button
                        type="button"
                        class="agricart-category-form__icon-slot"
                        role="listitem"
                        x-bind:class="{ 'is-selected': categoryVisualMapping.selected === index }"
                        x-bind:title="@js(__('agricart.form_panel.categories.select_icon', ['number' => '__N__']))".replace('__N__', index + 1)"
                        x-on:click="selectCategoryVisualIcon(index)"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-category-form__icon-placeholder" />
                    </button>
                </template>
            </div>
            <input type="hidden" name="visual_mapping" x-bind:value="JSON.stringify(categoryVisualMapping)" />
        </div>
        @break

    @case('category_names')
        <div class="agricart-category-form__names">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="{{ $fieldId }}-en">
                    {{ __('agricart.form_panel.categories.english_name') }}
                    @if ($field['required'] ?? false)
                        <span class="agricart-form-panel__required" aria-hidden="true">*</span>
                    @endif
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="{{ $fieldId }}-en"
                        type="text"
                        name="{{ $field['base'] }}_en"
                        class="fi-input"
                        placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => __('agricart.form_panel.categories.english_name')]) }}"
                        @required($field['required'] ?? false)
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="{{ $fieldId }}-ur">
                    {{ __('agricart.form_panel.categories.urdu_name') }}
                    @if ($field['required'] ?? false)
                        <span class="agricart-form-panel__required" aria-hidden="true">*</span>
                    @endif
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="{{ $fieldId }}-ur"
                        type="text"
                        name="{{ $field['base'] }}_ur"
                        class="fi-input"
                        dir="rtl"
                        placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => __('agricart.form_panel.categories.urdu_name')]) }}"
                        @required($field['required'] ?? false)
                    />
                </x-filament::input.wrapper>
            </div>
        </div>
        @break

    @case('bilingual_text')
        <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__bilingual">
            <span class="agricart-category-form__bilingual-label">
                {{ $field['label'] }}
                @if ($field['required'] ?? false)
                    <span class="agricart-form-panel__required" aria-hidden="true">*</span>
                @endif
            </span>
            <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="{{ $fieldId }}-en">
                        {{ __('agricart.categories.labels.english') }}
                    </label>
                    <x-filament::input.wrapper>
                        <input
                            id="{{ $fieldId }}-en"
                            type="text"
                            name="{{ $field['base'] }}_en"
                            class="fi-input"
                            placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => $field['label'] . ' (' . __('agricart.categories.labels.english') . ')']) }}"
                            @required($field['required'] ?? false)
                        />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="{{ $fieldId }}-ur">
                        {{ __('agricart.categories.labels.urdu') }}
                    </label>
                    <x-filament::input.wrapper>
                        <input
                            id="{{ $fieldId }}-ur"
                            type="text"
                            name="{{ $field['base'] }}_ur"
                            class="fi-input"
                            dir="rtl"
                            placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => $field['label'] . ' (' . __('agricart.categories.labels.urdu') . ')']) }}"
                            @required($field['required'] ?? false)
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
        @break

    @case('bilingual_textarea')
        <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__bilingual">
            <span class="agricart-category-form__bilingual-label">{{ $field['label'] }}</span>
            <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="{{ $fieldId }}-en">
                        {{ __('agricart.categories.labels.english') }}
                    </label>
                    <x-filament::input.wrapper>
                        <textarea
                            id="{{ $fieldId }}-en"
                            name="{{ $field['base'] }}_en"
                            rows="{{ $field['rows'] ?? 4 }}"
                            class="fi-input agricart-form-panel__textarea"
                            placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => $field['label'] . ' (' . __('agricart.categories.labels.english') . ')']) }}"
                        ></textarea>
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="{{ $fieldId }}-ur">
                        {{ __('agricart.categories.labels.urdu') }}
                    </label>
                    <x-filament::input.wrapper>
                        <textarea
                            id="{{ $fieldId }}-ur"
                            name="{{ $field['base'] }}_ur"
                            rows="{{ $field['rows'] ?? 4 }}"
                            class="fi-input agricart-form-panel__textarea"
                            dir="rtl"
                            placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => $field['label'] . ' (' . __('agricart.categories.labels.urdu') . ')']) }}"
                        ></textarea>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
        @break

    @case('parent')
        <div @class(['agricart-form-panel__field', 'agricart-form-panel__field--full' => $field['full'] ?? false])>
            <label class="agricart-form-panel__label" for="{{ $fieldId }}">{{ $field['label'] }}</label>
            <x-filament::input.wrapper>
                <select id="{{ $fieldId }}" name="{{ $field['name'] }}" class="fi-select-input">
                    <option value="">{{ __('agricart.categories.fields.parent_none') }}</option>
                    @foreach (\App\Filament\Forms\CategoryForm::parentOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>
        </div>
        @break

    @case('select')
        <div @class(['agricart-form-panel__field', 'agricart-form-panel__field--full' => $field['full'] ?? false])>
            <label class="agricart-form-panel__label" for="{{ $fieldId }}">{{ $field['label'] }}</label>
            <x-filament::input.wrapper>
                <select id="{{ $fieldId }}" name="{{ $field['name'] }}" class="fi-select-input">
                    @foreach ($field['options'] ?? [] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </x-filament::input.wrapper>
        </div>
        @break

    @case('text')
        <div @class(['agricart-form-panel__field', 'agricart-form-panel__field--full' => $field['full'] ?? false])>
            <label class="agricart-form-panel__label" for="{{ $fieldId }}">
                {{ $field['label'] }}
                @if ($field['required'] ?? false)
                    <span class="agricart-form-panel__required" aria-hidden="true">*</span>
                @endif
            </label>
            <x-filament::input.wrapper>
                <input
                    id="{{ $fieldId }}"
                    type="text"
                    name="{{ $field['name'] }}"
                    class="fi-input"
                    placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => $field['label']]) }}"
                    @required($field['required'] ?? false)
                />
            </x-filament::input.wrapper>
        </div>
        @break

    @case('json')
        <div class="agricart-form-panel__field agricart-form-panel__field--full">
            <label class="agricart-form-panel__label" for="{{ $fieldId }}">{{ $field['label'] }}</label>
            <p class="agricart-form-panel__hint agricart-category-form__json-hint">{{ __('agricart.categories.hints.search_terms') }}</p>
            <x-filament::input.wrapper>
                <textarea
                    id="{{ $fieldId }}"
                    name="{{ $field['name'] }}"
                    rows="3"
                    class="fi-input agricart-form-panel__textarea agricart-category-form__json-input"
                    placeholder="{{ $field['placeholder'] ?? '[]' }}"
                ></textarea>
            </x-filament::input.wrapper>
        </div>
        @break

    @case('faqs_repeater')
        <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-category-form__faqs">
            <div class="agricart-category-form__faqs-header">
                <span class="agricart-category-form__bilingual-label">{{ __('agricart.categories.sections.faqs') }}</span>
                <x-filament::button
                    color="gray"
                    outlined
                    size="sm"
                    tag="button"
                    type="button"
                    x-on:click="addCategoryFaq()"
                >
                    {{ __('agricart.categories.faqs.add') }}
                </x-filament::button>
            </div>

            <template x-if="categoryFaqs.length === 0">
                <p class="agricart-form-panel__hint">{{ __('agricart.categories.faqs.empty') }}</p>
            </template>

            <div class="agricart-category-form__faqs-list">
                <template x-for="(faq, index) in categoryFaqs" :key="index">
                    <div class="agricart-category-form__faq-item">
                        <div class="agricart-category-form__faq-item-head">
                            <span class="agricart-category-form__faq-index" x-text="@js(__('agricart.categories.faqs.item')).replace(':number', index + 1)"></span>
                            <button
                                type="button"
                                class="agricart-category-form__faq-remove"
                                x-on:click="removeCategoryFaq(index)"
                            >
                                {{ __('agricart.categories.faqs.remove') }}
                            </button>
                        </div>
                        <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                            <div class="agricart-form-panel__field">
                                <label class="agricart-form-panel__label" x-bind:for="'faq-q-en-' + index">{{ __('agricart.categories.faqs.question_en') }}</label>
                                <x-filament::input.wrapper>
                                    <input type="text" class="fi-input" x-bind:id="'faq-q-en-' + index" x-model="faq.question_en" />
                                </x-filament::input.wrapper>
                            </div>
                            <div class="agricart-form-panel__field">
                                <label class="agricart-form-panel__label" x-bind:for="'faq-q-ur-' + index">{{ __('agricart.categories.faqs.question_ur') }}</label>
                                <x-filament::input.wrapper>
                                    <input type="text" class="fi-input" dir="rtl" x-bind:id="'faq-q-ur-' + index" x-model="faq.question_ur" />
                                </x-filament::input.wrapper>
                            </div>
                        </div>
                        <div class="agricart-form-panel__fields agricart-form-panel__fields--pair">
                            <div class="agricart-form-panel__field">
                                <label class="agricart-form-panel__label" x-bind:for="'faq-a-en-' + index">{{ __('agricart.categories.faqs.answer_en') }}</label>
                                <x-filament::input.wrapper>
                                    <textarea rows="3" class="fi-input agricart-form-panel__textarea" x-bind:id="'faq-a-en-' + index" x-model="faq.answer_en"></textarea>
                                </x-filament::input.wrapper>
                            </div>
                            <div class="agricart-form-panel__field">
                                <label class="agricart-form-panel__label" x-bind:for="'faq-a-ur-' + index">{{ __('agricart.categories.faqs.answer_ur') }}</label>
                                <x-filament::input.wrapper>
                                    <textarea rows="3" class="fi-input agricart-form-panel__textarea" dir="rtl" x-bind:id="'faq-a-ur-' + index" x-model="faq.answer_ur"></textarea>
                                </x-filament::input.wrapper>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <input type="hidden" name="faqs" x-bind:value="JSON.stringify(categoryFaqs)" />
        </div>
        @break
@endswitch
