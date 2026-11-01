@php
    /** @var array{renew_soon: int, expired: int, renewable: int, days: int, total_alerts: int} $reminders */
    $active = $activeFilter ?? 'all';
@endphp

@if (($reminders['total_alerts'] ?? 0) > 0)
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:.85rem 1rem;margin-bottom:.85rem">
        <strong style="display:block;color:#92400e;font-size:.9rem">{{ __('agricart.pages.vault.reminder_title') }}</strong>
        <p style="margin:.35rem 0 0;color:#78350f;font-size:.85rem">
            {{ __('agricart.pages.vault.reminder_body', [
                'soon' => $reminders['renew_soon'],
                'expired' => $reminders['expired'],
                'days' => $reminders['days'],
            ]) }}
        </p>
        <div style="margin-top:.55rem;display:flex;flex-wrap:wrap;gap:.4rem">
            @if ($reminders['renew_soon'] > 0)
                <button type="button" wire:click="setFileFilter('renew_soon')" style="border:1px solid #f59e0b;background:#fff7ed;color:#b45309;border-radius:8px;padding:.3rem .65rem;font-size:.78rem;font-weight:700;cursor:pointer">
                    {{ __('agricart.pages.vault.filter_renew_soon') }} ({{ $reminders['renew_soon'] }})
                </button>
            @endif
            @if ($reminders['expired'] > 0)
                <button type="button" wire:click="setFileFilter('expired')" style="border:1px solid #f87171;background:#fef2f2;color:#b91c1c;border-radius:8px;padding:.3rem .65rem;font-size:.78rem;font-weight:700;cursor:pointer">
                    {{ __('agricart.pages.vault.filter_expired') }} ({{ $reminders['expired'] }})
                </button>
            @endif
        </div>
    </div>
@endif

<div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.75rem">
    @php
        $chips = [
            'all' => __('agricart.pages.vault.filter_all'),
            'renew_soon' => __('agricart.pages.vault.filter_renew_soon').' ('.$reminders['renew_soon'].')',
            'expired' => __('agricart.pages.vault.filter_expired').' ('.$reminders['expired'].')',
            'renewable' => __('agricart.pages.vault.filter_renewable').' ('.$reminders['renewable'].')',
        ];
    @endphp
    @foreach ($chips as $key => $label)
        <button
            type="button"
            wire:click="setFileFilter('{{ $key }}')"
            style="border:1px solid {{ $active === $key ? '#0f766e' : '#cbd5e1' }};background:{{ $active === $key ? '#ecfdf5' : '#fff' }};color:{{ $active === $key ? '#0f766e' : '#475569' }};border-radius:8px;padding:.35rem .7rem;font-size:.78rem;font-weight:600;cursor:pointer"
        >{{ $label }}</button>
    @endforeach
</div>
