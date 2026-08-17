@php
    $livewirePrefix = $toolbar['livewirePrefix'] ?? 'finance-expenses';
    $fromId = 'agricart-expenses-estimate-from';
    $toId = 'agricart-expenses-estimate-to';
@endphp

<div
    class="agricart-expense-estimate-toolbar"
    title="{{ __('agricart.finance.expenses.hints.estimated_period') }}"
    x-data="{
        from: @js(now()->startOfMonth()->toDateString()),
        to: @js(now()->endOfMonth()->toDateString()),
        total: '—',
        push() {
            window.Livewire.dispatch(@js($livewirePrefix . '-set-estimate-range'), {
                from: this.from,
                to: this.to,
            });
        },
        sync(payload) {
            const data = Array.isArray(payload) ? (payload[0] ?? {}) : (payload ?? {});

            if (data.estimateFrom) {
                this.from = data.estimateFrom;
            }

            if (data.estimateTo) {
                this.to = data.estimateTo;
            }

            if (data.estimateTotal !== undefined && data.estimateTotal !== null) {
                this.total = data.estimateTotal;
            }
        },
    }"
    x-init="Livewire.on(@js($livewirePrefix . '-toolbar-sync'), (payload) => sync(payload))"
>
    <label class="fi-sr-only" for="{{ $fromId }}">{{ __('agricart.finance.expenses.list.estimate_from') }}</label>
    <input
        id="{{ $fromId }}"
        type="date"
        class="agricart-expense-estimate-toolbar__date"
        x-model="from"
        x-on:change="push()"
    />
    <span class="agricart-expense-estimate-toolbar__sep" aria-hidden="true">–</span>
    <label class="fi-sr-only" for="{{ $toId }}">{{ __('agricart.finance.expenses.list.estimate_to') }}</label>
    <input
        id="{{ $toId }}"
        type="date"
        class="agricart-expense-estimate-toolbar__date"
        x-model="to"
        x-on:change="push()"
    />
    <span class="agricart-expense-estimate-toolbar__total">
        <span class="agricart-expense-estimate-toolbar__total-label">{{ __('agricart.finance.expenses.list.estimate_label') }}</span>
        <strong class="agricart-expense-estimate-toolbar__total-value" dir="ltr" x-text="total">—</strong>
    </span>
</div>
