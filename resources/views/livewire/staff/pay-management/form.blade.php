<div
    class="agricart-user-form agricart-staff-pay-form"
    x-on:staff-salary-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:staff-salary-form-open.window="$wire.openForm($event.detail.userId ?? null, $event.detail.mode ?? 'create')"
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
        <p class="agricart-customer-form__hint">{{ __('agricart.staff.pay_management.hints.form') }}</p>

        <div class="agricart-user-form__identity-grid">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-salary-user">
                    {{ __('agricart.staff.pay_management.fields.staff_member') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <select
                        id="staff-salary-user"
                        wire:model="userId"
                        class="fi-select-input"
                        @disabled($assignmentId !== null)
                    >
                        <option value="">{{ __('agricart.staff.pay_management.hints.select_staff') }}</option>
                        @foreach ($rosterOptions as $staff)
                            <option value="{{ $staff->id }}">
                                {{ $staff->name }}@if ($staff->user_number) ({{ $staff->user_number }})@endif
                            </option>
                        @endforeach
                    </select>
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-salary-gross">
                    {{ __('agricart.staff.pay_management.fields.monthly_gross') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input
                        id="staff-salary-gross"
                        type="number"
                        min="1"
                        step="1"
                        wire:model="monthly_gross"
                        class="fi-input"
                        placeholder="50000"
                    />
                </x-filament::input.wrapper>
                <p class="agricart-form-panel__hint">{{ __('agricart.staff.pay_management.hints.monthly_gross') }}</p>
            </div>

            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="staff-salary-effective">
                    {{ __('agricart.staff.pay_management.fields.effective_from') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="staff-salary-effective" type="date" wire:model="effective_from" class="fi-input" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="agricart-form-panel__field agricart-user-form__field--wide">
            <label class="agricart-form-panel__label" for="staff-salary-notes">{{ __('agricart.staff.pay_management.fields.notes') }}</label>
            <x-filament::input.wrapper>
                <textarea id="staff-salary-notes" wire:model="notes" class="fi-input" rows="3"></textarea>
            </x-filament::input.wrapper>
        </div>
    </section>
</div>
