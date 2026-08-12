@php
    $isReceipt = $paper === 'receipt';
    $receiptWidth = $receiptProfile?->width_mm ?? 75;
    $receiptMargin = $receiptProfile?->margin_mm ?? 2;
    $widthCss = rtrim(rtrim(number_format((float) $receiptWidth, 2, '.', ''), '0'), '.');
    $marginCss = rtrim(rtrim(number_format((float) $receiptMargin, 2, '.', ''), '0'), '.');
    $printRoute = fn (array $overrides = []): string => route('admin.staff-notes.print', array_merge([
        'note' => $note,
        'paper' => $paper,
        'locale' => $locale,
        'autoprint' => 0,
    ], $overrides));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale === 'ur' ? 'ur' : 'en' }}" @if ($locale === 'ur') dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $note->title }} — {{ __('staff_notes.print.title') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f5f7; color: #0f172a; }
        .print-shell { min-height: 100vh; display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 1.5rem; }
        .print-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; }
        .print-actions button, .print-actions a {
            border: 1px solid #ebedf0; background: #fff; padding: 0.5rem 0.875rem; font-weight: 700;
            cursor: pointer; text-decoration: none; color: inherit; font-size: 0.8125rem; border-radius: 0;
        }
        .print-actions button.is-primary, .print-actions a.is-primary { background: #83B735; border-color: #83B735; color: #fff; }
        .print-actions a.is-active { border-color: #0f172a; box-shadow: inset 0 0 0 1px #0f172a; }
        .note-sheet { width: 100%; background: #fff; border: 1px solid #ebedf0; padding: 1rem; }
        .note-sheet--a4 { max-width: 210mm; font-size: 0.9rem; line-height: 1.5; }
        .note-sheet--receipt { max-width: {{ $widthCss }}mm; padding: 0.55rem 0.45rem; font-size: 0.7rem; line-height: 1.35; }
        .note-sheet__head { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 0.55rem; margin-bottom: 0.75rem; }
        .note-sheet--receipt .note-sheet__head { border-bottom-style: dashed; border-bottom-width: 1px; margin-bottom: 0.45rem; padding-bottom: 0.35rem; }
        .note-sheet__logo { max-height: 3rem; margin: 0 auto 0.35rem; display: block; }
        .note-sheet--receipt .note-sheet__logo { max-height: 2.25rem; }
        .note-sheet__store { margin: 0; font-size: 1.25rem; font-weight: 800; }
        .note-sheet--receipt .note-sheet__store { font-size: 0.85rem; }
        .note-sheet__doc { margin: 0.25rem 0 0; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .note-sheet--receipt .note-sheet__doc { font-size: 0.65rem; }
        .note-sheet__meta-line { margin: 0.15rem 0 0; font-size: 0.75rem; color: #64748b; }
        .note-sheet--receipt .note-sheet__meta-line { font-size: 0.6rem; }
        .note-sheet__status {
            display: inline-block; margin: 0 0 0.5rem; padding: 0.2rem 0.45rem;
            border: 1px solid #0f172a; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
        }
        .note-sheet--receipt .note-sheet__status { font-size: 0.58rem; }
        .note-sheet__title { margin: 0 0 0.65rem; font-size: 1.35rem; font-weight: 800; line-height: 1.25; }
        .note-sheet--receipt .note-sheet__title { font-size: 0.9rem; margin-bottom: 0.4rem; }
        .note-sheet__body { margin: 0 0 0.85rem; white-space: pre-wrap; word-break: break-word; }
        .note-sheet--receipt .note-sheet__body { margin-bottom: 0.5rem; }
        .note-sheet__facts { display: grid; gap: 0.35rem; border-top: 1px solid #ebedf0; padding-top: 0.65rem; }
        .note-sheet--receipt .note-sheet__facts { gap: 0.2rem; padding-top: 0.4rem; }
        .note-sheet__fact { display: flex; justify-content: space-between; gap: 0.75rem; font-size: 0.8125rem; }
        .note-sheet--receipt .note-sheet__fact { font-size: 0.62rem; }
        .note-sheet__fact span:first-child { color: #64748b; font-weight: 600; }
        .note-sheet__fact span:last-child { font-weight: 700; text-align: end; }
        @media print {
            body { background: #fff; }
            .print-actions { display: none; }
            .print-shell { padding: 0; }
            .note-sheet { border: none; max-width: none; width: 100%; }
            .note-sheet--receipt { padding: 0; }
        }
        @if ($isReceipt)
        @media print { @page { size: {{ $widthCss }}mm auto; margin: {{ $marginCss }}mm; } }
        @else
        @media print { @page { size: A4 portrait; margin: 12mm; } }
        @endif
    </style>
</head>
<body class="{{ $isReceipt ? 'is-receipt' : 'is-a4' }}">
    <div class="print-shell">
        <div class="print-actions">
            <a href="{{ $printRoute(['paper' => 'a4']) }}" @class(['is-active' => ! $isReceipt])>{{ __('pos.print.layout_a4') }}</a>
            <a href="{{ $printRoute(['paper' => 'receipt']) }}" @class(['is-active' => $isReceipt])>{{ __('pos.print.layout_receipt') }}</a>
            <a href="{{ $printRoute(['locale' => 'en']) }}" @class(['is-active' => $locale === 'en'])>{{ __('pos.locales.en') }}</a>
            <a href="{{ $printRoute(['locale' => 'ur']) }}" @class(['is-active' => $locale === 'ur'])>{{ __('pos.locales.ur') }}</a>
            <button type="button" class="is-primary" onclick="window.print()">{{ __('pos.print.print_button') }}</button>
            <button type="button" onclick="window.close()">{{ __('pos.print.close_button') }}</button>
        </div>

        <article @class(['note-sheet', $isReceipt ? 'note-sheet--receipt' : 'note-sheet--a4'])>
            <header class="note-sheet__head">
                @if ($storeLogoUrl)
                    <img src="{{ $storeLogoUrl }}" alt="" class="note-sheet__logo" />
                @endif
                <p class="note-sheet__store">{{ $storeName }}</p>
                <p class="note-sheet__doc">{{ __('staff_notes.print.title') }}</p>
                @if (filled($storePhone) || filled($storeAddress))
                    <p class="note-sheet__meta-line">
                        {{ collect([$storePhone, $storeAddress])->filter()->implode(' · ') }}
                    </p>
                @endif
            </header>

            <span class="note-sheet__status">{{ __('staff_notes.statuses.'.$note->status) }}</span>
            <h1 class="note-sheet__title">{{ $note->title }}</h1>

            @if (filled($note->body))
                <div class="note-sheet__body">{{ $note->body }}</div>
            @endif

            <div class="note-sheet__facts">
                <div class="note-sheet__fact">
                    <span>{{ __('staff_notes.print.from') }}</span>
                    <span>{{ $note->creator?->name ?? '—' }}</span>
                </div>
                <div class="note-sheet__fact">
                    <span>{{ __('staff_notes.print.to') }}</span>
                    <span>{{ $note->assignee?->name ?? __('staff_notes.fields.assigned_none') }}</span>
                </div>
                @if ($note->due_on)
                    <div class="note-sheet__fact">
                        <span>{{ __('staff_notes.print.due') }}</span>
                        <span>{{ $note->due_on->format('d M Y') }}</span>
                    </div>
                @endif
                <div class="note-sheet__fact">
                    <span>{{ __('staff_notes.print.created') }}</span>
                    <span>{{ agricart_datetime($note->created_at, 'd M Y H:i') }}</span>
                </div>
                @if ($note->isDone())
                    <div class="note-sheet__fact">
                        <span>{{ __('staff_notes.print.done_by') }}</span>
                        <span>
                            {{ $note->completedByUser?->name ?? '—' }}
                            @if ($note->completed_at)
                                · {{ agricart_datetime($note->completed_at, 'd M Y H:i') }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </article>
    </div>

    @if ($autoprint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
