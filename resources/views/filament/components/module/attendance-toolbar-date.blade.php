@php
    $livewirePrefix = $toolbar['livewirePrefix'] ?? 'staff-attendance';
    $toolbarDateKey = $toolbar['toolbarDateKey'] ?? 'attendanceDate';
    $toolbarDateLabel = $toolbar['toolbarDateLabel'] ?? __('agricart.staff.attendance.fields.date');
    $toolbarInputType = $toolbar['toolbarInputType'] ?? 'date';
    $initialValue = $toolbarInputType === 'month' ? now()->format('Y-m') : now()->format('Y-m-d');
    $inputId = 'agricart-' . ($toolbar['resourceSlug'] ?? 'staff') . '-toolbar-date';
@endphp

<div
    class="agricart-staff-attendance-toolbar"
    x-data="{
        focusDate: @js($initialValue),
        inputType: @js($toolbarInputType),
        syncMarkButton() {
            document.querySelectorAll('[data-agricart-open-form-panel][data-form-key=staff-attendance]').forEach((button) => {
                button.setAttribute('data-attendance-date', this.focusDate);
            });
            document.querySelectorAll('[data-agricart-open-form-panel][data-form-key=staff-payroll]').forEach((button) => {
                button.setAttribute('data-payroll-month', this.focusDate);
            });
        },
        pushDate(date) {
            this.focusDate = date;
            if (this.inputType === 'month') {
                window.Livewire.dispatch(@js($livewirePrefix . '-set-month'), { month: date });
            } else {
                window.Livewire.dispatch(@js($livewirePrefix . '-set-date'), { date });
                this.syncMarkButton();
            }
        },
    }"
    x-init="
        syncMarkButton();
        Livewire.on(@js($livewirePrefix . '-toolbar-sync'), (payload) => {
            const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});
            const next = data.payrollMonth ?? data.attendanceDate ?? data.focusDate;
            if (next) {
                focusDate = next;
            }
            syncMarkButton();
        });
    "
>
    <label class="fi-sr-only" for="{{ $inputId }}">
        {{ $toolbarDateLabel }}
    </label>
    <input
        id="{{ $inputId }}"
        type="{{ $toolbarInputType }}"
        class="agricart-staff-attendance-toolbar__date"
        x-model="focusDate"
        x-on:change="pushDate($event.target.value)"
    />
</div>
