@php
    $readonly = $this->isReadonly();
@endphp

<div class="agricart-form-panel__fields agricart-finance-expense-category-form">
    @if ($statusMessage)
        <div @class([
            'agricart-form-panel__alert',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_categories.fields.code') }}</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="code" class="fi-input" @disabled($readonly) />
        </x-filament::input.wrapper>
        @error('code') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_categories.fields.name_en') }} *</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="name_en" class="fi-input" @disabled($readonly) />
        </x-filament::input.wrapper>
        @error('name_en') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_categories.fields.name_ur') }}</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="name_ur" class="fi-input" dir="rtl" @disabled($readonly) />
        </x-filament::input.wrapper>
        @error('name_ur') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_categories.fields.kind') }}</label>
        <x-filament::input.wrapper>
            <select wire:model="kind" class="fi-input" @disabled($readonly)>
                <option value="operating">{{ __('agricart.finance.expense_categories.kinds.operating') }}</option>
                <option value="other">{{ __('agricart.finance.expense_categories.kinds.other') }}</option>
            </select>
        </x-filament::input.wrapper>
        @error('kind') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_categories.fields.status') }}</label>
        <x-filament::input.wrapper>
            <select wire:model="status" class="fi-input" @disabled($readonly)>
                <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
            </select>
        </x-filament::input.wrapper>
        @error('status') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field agricart-form-panel__field--full">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expense_categories.fields.notes') }}</label>
        <x-filament::input.wrapper>
            <textarea wire:model="notes" class="fi-input agricart-form-panel__textarea" rows="3" @disabled($readonly)></textarea>
        </x-filament::input.wrapper>
        @error('notes') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>
</div>
