@props([
    'id',
    'name',
    'label' => null,
    'inputClass' => '',
    'autocomplete' => 'current-password',
    'disabled' => false,
])

@php
    $showLabel = __('agricart.form_panel.show_password');
    $hideLabel = __('agricart.form_panel.hide_password');
    $inputClasses = trim('ag-password-field__input ' . $inputClass);
@endphp

<div class="ag-password-field">
    @if (filled($label))
        <label class="ag-password-field__label" for="{{ $id }}">{!! $label !!}</label>
    @endif

    <div class="ag-password-field__control">
        <input
            id="{{ $id }}"
            type="password"
            name="{{ $name }}"
            data-ag-password-input
            @disabled($disabled)
            autocomplete="{{ $autocomplete }}"
            class="{{ $inputClasses }}"
            {{ $attributes->except(['name']) }}
        />

        <button
            type="button"
            class="ag-password-field__toggle"
            data-ag-password-toggle
            aria-label="{{ $showLabel }}"
            data-label-show="{{ $showLabel }}"
            data-label-hide="{{ $hideLabel }}"
            @disabled($disabled)
        >
            <svg data-ag-password-icon-show xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <svg data-ag-password-icon-hide hidden xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <path d="M1 1l22 22"/>
                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
            </svg>
        </button>
    </div>
</div>
