<div
    class="agricart-user-form agricart-staff-advance-form"
    x-on:staff-advance-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:staff-advance-form-open.window="$wire.openForm($event.detail.defaultType ?? null)"
>
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-form-panel__section">
        <div class="agricart-unit-form__grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-advance-type">{{ __('agricart.staff.advances.fields.type') }}</label>
                <select id="staff-advance-type" wire:model.live="type" class="fi-select-input">
                    <option value="{{ \App\Models\StaffAdvanceTransaction::TYPE_ADVANCE }}">{{ __('agricart.staff.advances.types.advance') }}</option>
                    <option value="{{ \App\Models\StaffAdvanceTransaction::TYPE_REPAYMENT }}">{{ __('agricart.staff.advances.types.repayment') }}</option>
                </select>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-advance-user">{{ __('agricart.staff.advances.fields.staff_member') }}</label>
                <select id="staff-advance-user" wire:model.live="userId" class="fi-select-input">
                    <option value="">{{ __('agricart.staff.advances.hints.select_staff') }}</option>
                    @foreach ($rosterOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}@if ($option->user_number) ({{ $option->user_number }})@endif</option>
                    @endforeach
                </select>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-advance-amount">{{ __('agricart.staff.advances.fields.amount') }}</label>
                <x-filament::input.wrapper>
                    <input id="staff-advance-amount" type="number" step="0.01" min="0" wire:model="amount" class="fi-input" />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-advance-date">{{ __('agricart.staff.advances.fields.date') }}</label>
                <x-filament::input.wrapper>
                    <input id="staff-advance-date" type="date" wire:model="transaction_date" class="fi-input" />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field" style="grid-column: 1 / -1;">
                <label class="agricart-form-panel__label" for="staff-advance-notes">{{ __('agricart.staff.advances.fields.notes') }}</label>
                <textarea id="staff-advance-notes" wire:model="notes" rows="3" class="fi-input"></textarea>
            </div>
        </div>

        @if ($userId && $balance !== null)
            <div class="agricart-staff-advances__form-balance">
                <p>{{ __('agricart.staff.advances.hints.current_balance') }}</p>
                <ul class="agricart-staff-advances__form-balance-list">
                    <li>{{ __('agricart.staff.advances.list.col_owes_us') }}: <strong>{{ $owesUs > 0 ? $money($owesUs) : '—' }}</strong></li>
                    <li>{{ __('agricart.staff.advances.list.col_we_owe') }}: <strong>{{ $weOwe > 0 ? $money($weOwe) : '—' }}</strong></li>
                </ul>
                <p class="agricart-customer-form__hint">{{ __('agricart.staff.advances.hints.balance_help') }}</p>
            </div>
        @endif
    </section>
</div>
