@php
    $livewirePrefix = $toolbar['livewirePrefix'] ?? 'staff-attendance';
@endphp

<div
    class="agricart-staff-attendance-toolbar"
    x-data="{
        attendanceDate: @js(now()->format('Y-m-d')),
        syncMarkButton() {
            document.querySelectorAll('[data-agricart-open-form-panel][data-form-key=staff-attendance]').forEach((button) => {
                button.setAttribute('data-attendance-date', this.attendanceDate);
            });
        },
        pushDate(date) {
            this.attendanceDate = date;
            window.Livewire.dispatch(@js($livewirePrefix . '-set-date'), { date });
            this.syncMarkButton();
        },
    }"
    x-init="
        syncMarkButton();
        Livewire.on(@js($livewirePrefix . '-toolbar-sync'), (payload) => {
            const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});
            if (data.attendanceDate) {
                attendanceDate = data.attendanceDate;
            }
            syncMarkButton();
        });
    "
>
    <label class="fi-sr-only" for="agricart-staff-attendance-toolbar-date">
        {{ __('agricart.staff.attendance.fields.date') }}
    </label>
    <input
        id="agricart-staff-attendance-toolbar-date"
        type="date"
        class="agricart-staff-attendance-toolbar__date"
        x-model="attendanceDate"
        x-on:change="pushDate($event.target.value)"
    />
</div>
