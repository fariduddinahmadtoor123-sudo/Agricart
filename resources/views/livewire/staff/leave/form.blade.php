@php
    use Filament\Support\Icons\Heroicon;
@endphp

<div
    class="agricart-user-form agricart-staff-leave-form"
    x-on:staff-leave-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:staff-leave-form-open.window="$wire.openForm()"
>
    @if ($statusMessage)
        <div class="agricart-form-panel__status is-{{ $statusType }}">
            {{ $statusMessage }}
        </div>
    @endif

    @if (! $canManage)
        <p>{{ __('agricart.access.denied') }}</p>
    @else
        <section class="agricart-form-panel__section agricart-user-form__section">
            <h2 class="agricart-form-panel__section-title">{{ __('agricart.staff.leave.sections.allowance') }}</h2>
            <div class="agricart-staff-leave-form__allowance-row">
                <label class="agricart-form-panel__label" for="monthly-paid-leaves">{{ __('agricart.staff.leave.fields.monthly_paid_leaves') }}</label>
                <x-filament::input.wrapper>
                    <input
                        id="monthly-paid-leaves"
                        type="number"
                        min="0"
                        max="31"
                        wire:model="monthly_paid_leaves"
                        class="fi-input agricart-staff-leave-form__allowance-input"
                    />
                </x-filament::input.wrapper>
            </div>
        </section>

        <section class="agricart-form-panel__section agricart-user-form__section">
            <h2 class="agricart-form-panel__section-title">{{ __('agricart.staff.leave.sections.public_holidays') }}</h2>

            <div
                class="agricart-staff-leave-form__holiday-headings"
                style="display: grid; grid-template-columns: 8.5rem minmax(0, 1fr) minmax(0, 1fr) 3.25rem 3.75rem; gap: 0.5rem; align-items: end;"
            >
                <span class="agricart-form-panel__label">{{ __('agricart.staff.leave.fields.holiday_date') }}</span>
                <span class="agricart-form-panel__label">{{ __('agricart.staff.leave.fields.title_en') }}</span>
                <span class="agricart-form-panel__label">{{ __('agricart.staff.leave.fields.title_ur') }}</span>
                <span class="agricart-form-panel__label agricart-staff-leave-form__paid-heading">{{ __('agricart.staff.leave.fields.is_paid') }}</span>
                <span class="agricart-user-form__repeatable-spacer" aria-hidden="true"></span>
            </div>

            @foreach ($newHolidays as $index => $row)
                <div
                    class="agricart-staff-leave-form__holiday-row"
                    wire:key="new-holiday-{{ $index }}"
                    style="display: grid; grid-template-columns: 8.5rem minmax(0, 1fr) minmax(0, 1fr) 3.25rem 3.75rem; gap: 0.5rem; align-items: center;"
                >
                    <div class="agricart-form-panel__field agricart-staff-leave-form__holiday-field">
                        <x-filament::input.wrapper>
                            <input type="date" wire:model="newHolidays.{{ $index }}.holiday_date" class="fi-input" />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field agricart-staff-leave-form__holiday-field">
                        <x-filament::input.wrapper>
                            <input type="text" wire:model="newHolidays.{{ $index }}.title_en" class="fi-input" />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-form-panel__field agricart-staff-leave-form__holiday-field">
                        <x-filament::input.wrapper>
                            <input type="text" wire:model="newHolidays.{{ $index }}.title_ur" dir="rtl" class="fi-input" />
                        </x-filament::input.wrapper>
                    </div>
                    <div class="agricart-staff-leave-form__paid-cell">
                        <input
                            type="checkbox"
                            wire:model="newHolidays.{{ $index }}.is_paid"
                            class="agricart-staff-leave-form__paid-input"
                            title="{{ __('agricart.staff.leave.fields.is_paid') }}"
                        />
                    </div>
                    <div class="agricart-user-form__repeatable-actions">
                        @if ($loop->last)
                            <button
                                type="button"
                                class="agricart-product-form__icon-btn"
                                wire:click="addHolidayRow"
                                title="{{ __('agricart.staff.leave.actions.add_holiday_row') }}"
                            >
                                <x-filament::icon :icon="Heroicon::OutlinedPlus" />
                            </button>
                        @endif
                        @if (count($newHolidays) > 1)
                            <button
                                type="button"
                                class="agricart-product-form__icon-btn agricart-product-form__icon-btn--muted"
                                wire:click="removeHolidayRow({{ $index }})"
                                title="{{ __('agricart.staff.leave.actions.remove') }}"
                            >
                                <x-filament::icon :icon="Heroicon::OutlinedMinus" />
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        @if ($holidays->isNotEmpty())
            <section class="agricart-form-panel__section agricart-user-form__section">
                <h3 class="agricart-form-panel__section-title">{{ __('agricart.staff.leave.sections.saved_holidays') }}</h3>
                <table class="agricart-categories__table agricart-staff-leave-form__saved-table">
                    <thead>
                        <tr>
                            <th>{{ __('agricart.staff.leave.list.col_date') }}</th>
                            <th>{{ __('agricart.staff.leave.list.col_title') }}</th>
                            <th>{{ __('agricart.staff.leave.list.col_paid') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($holidays as $holiday)
                            <tr wire:key="saved-holiday-{{ $holiday->id }}">
                                <td dir="ltr">{{ $holiday->holiday_date->format('Y-m-d') }}</td>
                                <td>{{ $holiday->title_en }}</td>
                                <td>{{ $holiday->is_paid ? __('agricart.staff.attendance.paid.yes') : __('agricart.staff.attendance.paid.no') }}</td>
                                <td>
                                    <button type="button" class="agricart-categories__link-danger" wire:click="deleteHoliday({{ $holiday->id }})">
                                        {{ __('agricart.staff.leave.actions.remove') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    @endif
</div>
