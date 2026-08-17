@php
    $readonly = $this->isReadonly();
@endphp

<div
    class="agricart-form-panel__fields agricart-finance-expense-category-form"
    x-data
    x-on:finance-expense-category-form-open.window="$wire.openForm($event.detail?.categoryId ?? $event.detail?.[0]?.categoryId ?? null, $event.detail?.mode ?? $event.detail?.[0]?.mode ?? 'create')"
    x-on:finance-expense-category-form-action.window="$wire.handlePanelAction($event.detail?.action ?? $event.detail?.[0]?.action)"
>
    <style>
        .agricart-form-panel__fields.agricart-finance-expense-category-form {
            display: flex !important;
            flex-direction: column;
            gap: 0.9rem;
        }

        .agricart-finance-expense-category-form__row,
        .agricart-finance-expense-category-form__notes {
            width: 100%;
            min-width: 0;
            grid-column: 1 / -1;
        }

        .agricart-finance-expense-category-form__row {
            display: grid !important;
            gap: 0.75rem 1rem;
            align-items: end;
        }

        .agricart-finance-expense-category-form__row--meta {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr);
        }

        .agricart-finance-expense-category-form__row--names {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .agricart-finance-expense-category-form__row--meta .fi-input-wrp {
            min-height: 2.625rem;
        }

        .agricart-finance-expense-category-form__row--meta input.fi-input,
        .agricart-finance-expense-category-form__row--meta select.fi-input,
        .agricart-finance-expense-category-form__row--meta select.fi-select-input {
            min-height: 2.625rem;
            height: 2.625rem;
            padding-block: 0.5rem;
        }

        .agricart-finance-expense-category-form__notes .fi-input-wrp {
            display: flex;
            width: 100%;
        }

        .agricart-finance-expense-category-form__notes textarea.fi-input {
            display: block;
            width: 100% !important;
            min-height: 8.5rem;
            padding: 0.65rem 0.75rem;
        }
    </style>

    @if ($statusMessage)
        <div @class([
            'agricart-form-panel__alert',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-finance-expense-category-form__row agricart-finance-expense-category-form__row--meta agricart-form-panel__field--full">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-category-code">{{ __('agricart.finance.expense_categories.fields.code') }}</label>
            <x-filament::input.wrapper>
                <input id="expense-category-code" type="text" wire:model="code" class="fi-input" readonly @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('code') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-category-status">{{ __('agricart.finance.expense_categories.fields.status') }}</label>
            <x-filament::input.wrapper>
                <select id="expense-category-status" wire:model="status" class="fi-input" @disabled($readonly)>
                    <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                    <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
                </select>
            </x-filament::input.wrapper>
            @error('status') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-category-kind">{{ __('agricart.finance.expense_categories.fields.kind') }}</label>
            <x-filament::input.wrapper>
                <select id="expense-category-kind" wire:model="kind" class="fi-input" @disabled($readonly)>
                    <option value="operating">{{ __('agricart.finance.expense_categories.kinds.operating') }}</option>
                    <option value="other">{{ __('agricart.finance.expense_categories.kinds.other') }}</option>
                </select>
            </x-filament::input.wrapper>
            @error('kind') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="agricart-finance-expense-category-form__row agricart-finance-expense-category-form__row--names agricart-form-panel__field--full">
        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-category-name-en">
                {{ __('agricart.finance.expense_categories.fields.name_en') }}
                <span class="agricart-form-panel__required">*</span>
            </label>
            <x-filament::input.wrapper>
                <input id="expense-category-name-en" type="text" wire:model="name_en" class="fi-input" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('name_en') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>

        <div class="agricart-form-panel__field">
            <label class="agricart-form-panel__label" for="expense-category-name-ur">{{ __('agricart.finance.expense_categories.fields.name_ur') }}</label>
            <x-filament::input.wrapper>
                <input id="expense-category-name-ur" type="text" wire:model="name_ur" class="fi-input" dir="rtl" @disabled($readonly) />
            </x-filament::input.wrapper>
            @error('name_ur') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="agricart-form-panel__field agricart-form-panel__field--full agricart-finance-expense-category-form__notes">
        <label class="agricart-form-panel__label" for="expense-category-notes">{{ __('agricart.finance.expense_categories.fields.notes') }}</label>
        <x-filament::input.wrapper>
            <textarea id="expense-category-notes" wire:model="notes" class="fi-input agricart-form-panel__textarea" rows="5" @disabled($readonly)></textarea>
        </x-filament::input.wrapper>
        @error('notes') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>
</div>
