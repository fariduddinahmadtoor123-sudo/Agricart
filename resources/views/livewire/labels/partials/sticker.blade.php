@php
    $titleText = $data['title'] ?? '—';
    $lines = $data['lines'] ?? [];
    $labelCode = $data['label_code'] ?? '—';
    $isRtl = ($locale ?? 'en') === 'ur';
@endphp

<article class="agricart-labels__sticker agricart-labels__sticker--{{ $type }}" @if ($isRtl) dir="rtl" @endif>
    <header class="agricart-labels__sticker-head">
        <span>{{ $title }}</span>
        <code>{{ $labelCode }}</code>
    </header>

    <div class="agricart-labels__sticker-body">
        <h4 class="agricart-labels__sticker-title">{{ $titleText }}</h4>

        @if ($lines !== [])
            <dl class="agricart-labels__sticker-lines">
                @foreach ($lines as $line)
                    <div class="agricart-labels__sticker-line">
                        <dt>{{ $line['label'] ?? '' }}</dt>
                        <dd>{{ $line['value'] ?? '' }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        @if ($type === 'qr')
            <div class="agricart-labels__qr-placeholder" aria-hidden="true">
                <span class="agricart-labels__qr-grid"></span>
            </div>
            <p class="agricart-labels__sticker-foot">{{ $data['qr_payload'] ?? $labelCode }}</p>
        @else
            <div class="agricart-labels__barcode" aria-hidden="true"></div>
            <p class="agricart-labels__sticker-foot">{{ $data['barcode_value'] ?? $labelCode }}</p>
        @endif
    </div>
</article>
