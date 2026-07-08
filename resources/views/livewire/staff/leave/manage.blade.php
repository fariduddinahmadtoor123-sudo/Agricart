<div class="agricart-staff-leave">
    @if ($statusMessage)
        <div @class([
            'agricart-store-admin__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-form-panel__section">
        <h2 class="agricart-form-panel__section-title">{{ __('agricart.staff.leave.sections.allowance') }}</h2>
        <p class="agricart-customer-form__hint">{{ __('agricart.staff.leave.hints.allowance') }}</p>
        @if ($canManage)
            <div class="agricart-user-form__identity-grid">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="monthly-paid-leaves">{{ __('agricart.staff.leave.fields.monthly_paid_leaves') }}</label>
                    <x-filament::input.wrapper>
                        <input id="monthly-paid-leaves" type="number" min="0" max="31" wire:model="monthly_paid_leaves" class="fi-input" />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field agricart-staff-leave__save-wrap">
                    <x-filament::button type="button" color="primary" wire:click="saveAllowance">
                        {{ __('agricart.staff.leave.actions.save_allowance') }}
                    </x-filament::button>
                </div>
            </div>
        @else
            <p>{{ __('agricart.staff.leave.fields.monthly_paid_leaves') }}: <strong>{{ $monthly_paid_leaves }}</strong></p>
        @endif
    </section>

    <section class="agricart-form-panel__section">
        <h2 class="agricart-form-panel__section-title">{{ __('agricart.staff.leave.sections.public_holidays') }}</h2>
        <p class="agricart-customer-form__hint">{{ __('agricart.staff.leave.hints.public_holidays') }}</p>

        @if ($canManage)
            <div class="agricart-supplier-form__meta-grid">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label">{{ __('agricart.staff.leave.fields.holiday_date') }}</label>
                    <x-filament::input.wrapper>
                        <input type="date" wire:model="holiday_date" class="fi-input" />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label">{{ __('agricart.staff.leave.fields.title_en') }}</label>
                    <x-filament::input.wrapper>
                        <input type="text" wire:model="title_en" class="fi-input" />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label">{{ __('agricart.staff.leave.fields.title_ur') }}</label>
                    <x-filament::input.wrapper>
                        <input type="text" wire:model="title_ur" dir="rtl" class="fi-input" />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="holiday_is_paid" />
                        <span>{{ __('agricart.staff.leave.fields.is_paid') }}</span>
                    </label>
                </div>
                <div class="agricart-form-panel__field agricart-staff-leave__save-wrap">
                    <x-filament::button type="button" color="primary" wire:click="addHoliday">
                        {{ __('agricart.staff.leave.actions.add_holiday') }}
                    </x-filament::button>
                </div>
            </div>
        @endif

        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.staff.leave.list.col_date') }}</th>
                        <th>{{ __('agricart.staff.leave.list.col_title') }}</th>
                        <th>{{ __('agricart.staff.leave.list.col_paid') }}</th>
                        @if ($canManage)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($holidays as $holiday)
                        <tr wire:key="holiday-{{ $holiday->id }}">
                            <td>{{ $holiday->holiday_date->format('d M Y') }}</td>
                            <td>{{ $holiday->title_en }}@if ($holiday->title_ur) / {{ $holiday->title_ur }}@endif</td>
                            <td>{{ $holiday->is_paid ? __('agricart.staff.attendance.paid.yes') : __('agricart.staff.attendance.paid.no') }}</td>
                            @if ($canManage)
                                <td>
                                    <button type="button" class="agricart-categories__action agricart-staff-members__action-muted" wire:click="deleteHoliday({{ $holiday->id }})">
                                        {{ __('agricart.staff.leave.actions.remove') }}
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="agricart-categories__empty">{{ __('agricart.staff.leave.list.empty_holidays') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
