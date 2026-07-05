@php
    use Filament\Support\Icons\Heroicon;

    $fields = \App\Filament\Layout\ModuleFormPanel::sampleFields(__('agricart.form_panel.record'));
@endphp

<section class="agricart-form-panel__section">
    <h3 class="agricart-form-panel__section-title">{{ __('agricart.form_panel.sections.general') }}</h3>

    <div class="agricart-form-panel__fields">
        @foreach ($fields as $field)
            <div @class([
                'agricart-form-panel__field',
                'agricart-form-panel__field--full' => $field['type'] === 'textarea',
            ])>
                <label
                    class="agricart-form-panel__label"
                    for="agricart-form-{{ $field['name'] }}"
                >
                    {{ $field['label'] }}
                    @if ($field['required'])
                        <span class="agricart-form-panel__required" aria-hidden="true">*</span>
                    @endif
                </label>

                @if ($field['type'] === 'textarea')
                    <x-filament::input.wrapper>
                        <textarea
                            id="agricart-form-{{ $field['name'] }}"
                            rows="4"
                            class="fi-input agricart-form-panel__textarea"
                            placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => $field['label']]) }}"
                        ></textarea>
                    </x-filament::input.wrapper>
                @elseif ($field['type'] === 'select')
                    <x-filament::input.wrapper>
                        <select
                            id="agricart-form-{{ $field['name'] }}"
                            class="fi-select-input"
                        >
                            <option value="">{{ __('agricart.form_panel.fields.select_status') }}</option>
                            @foreach ($field['options'] ?? [] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </x-filament::input.wrapper>
                @else
                    <x-filament::input.wrapper>
                        <input
                            id="agricart-form-{{ $field['name'] }}"
                            type="text"
                            class="fi-input"
                            placeholder="{{ __('agricart.form_panel.fields.enter', ['field' => $field['label']]) }}"
                            @required($field['required'])
                        />
                    </x-filament::input.wrapper>
                @endif
            </div>
        @endforeach
    </div>
</section>

<section class="agricart-form-panel__section">
    <h3 class="agricart-form-panel__section-title">{{ __('agricart.form_panel.sections.notes') }}</h3>
    <p class="agricart-form-panel__hint">
        {{ __('agricart.form_panel.sample_hint') }}
    </p>
</section>
