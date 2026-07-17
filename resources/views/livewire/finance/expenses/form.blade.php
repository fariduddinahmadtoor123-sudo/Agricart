@php
    $readonly = $this->isReadonly();
@endphp

<div class="agricart-form-panel__fields agricart-finance-expense-form">
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
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expenses.fields.expense_number') }}</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="expense_number" class="fi-input" readonly disabled />
        </x-filament::input.wrapper>
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expenses.fields.category') }} *</label>
        <x-filament::input.wrapper>
            <select wire:model="expense_category_id" class="fi-input" @disabled($readonly)>
                <option value="">{{ __('agricart.finance.expenses.fields.category_none') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->displayName() }}</option>
                @endforeach
            </select>
        </x-filament::input.wrapper>
        @error('expense_category_id') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expenses.fields.name_en') }} *</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="name_en" class="fi-input" @disabled($readonly) />
        </x-filament::input.wrapper>
        @error('name_en') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expenses.fields.name_ur') }}</label>
        <x-filament::input.wrapper>
            <input type="text" wire:model="name_ur" class="fi-input" dir="rtl" @disabled($readonly) />
        </x-filament::input.wrapper>
        @error('name_ur') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    <div class="agricart-form-panel__field agricart-form-panel__field--full">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expenses.fields.information') }}</label>
        <x-filament::input.wrapper>
            <textarea wire:model="information" class="fi-input agricart-form-panel__textarea" rows="4" @disabled($readonly) placeholder="{{ __('agricart.finance.expenses.fields.information_placeholder') }}"></textarea>
        </x-filament::input.wrapper>
        @error('information') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
        <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.hints.information') }}</p>
    </div>

    <div class="agricart-form-panel__field">
        <label class="agricart-form-panel__label">{{ __('agricart.finance.expenses.fields.status') }}</label>
        <x-filament::input.wrapper>
            <select wire:model="status" class="fi-input" @disabled($readonly)>
                <option value="active">{{ __('agricart.form_panel.statuses.active') }}</option>
                <option value="inactive">{{ __('agricart.form_panel.statuses.inactive') }}</option>
            </select>
        </x-filament::input.wrapper>
        @error('status') <p class="agricart-form-panel__error">{{ $message }}</p> @enderror
    </div>

    @if (! $readonly)
        <div class="agricart-form-panel__field agricart-form-panel__field--full">
            <label class="agricart-form-panel__label">{{ __('agricart.finance.expenses.ai.prompt_label') }}</label>
            <x-filament::input.wrapper>
                <textarea wire:model="enrichmentPrompt" class="fi-input agricart-form-panel__textarea" rows="2" placeholder="{{ __('agricart.finance.expenses.ai.prompt_placeholder') }}"></textarea>
            </x-filament::input.wrapper>
            <p class="agricart-form-panel__hint">{{ __('agricart.finance.expenses.ai.hint') }}</p>
        </div>
    @endif
</div>
