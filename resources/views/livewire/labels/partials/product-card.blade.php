@php
    $stickers = [
        ['type' => 'qr', 'locale' => 'en', 'title' => __('agricart.labels.sticker.qr_en'), 'data' => $product->label_qr['en'] ?? null],
        ['type' => 'barcode', 'locale' => 'en', 'title' => __('agricart.labels.sticker.barcode_en'), 'data' => $product->label_barcode['en'] ?? null],
        ['type' => 'qr', 'locale' => 'ur', 'title' => __('agricart.labels.sticker.qr_ur'), 'data' => $product->label_qr['ur'] ?? null],
        ['type' => 'barcode', 'locale' => 'ur', 'title' => __('agricart.labels.sticker.barcode_ur'), 'data' => $product->label_barcode['ur'] ?? null],
    ];
@endphp

<div class="agricart-labels__product" wire:key="label-product-{{ $product->id }}">
    <div class="agricart-labels__product-head">
        <div>
            <h3 class="agricart-labels__product-title">{{ $product->name_en }}</h3>
            <p class="agricart-labels__product-meta" dir="rtl">{{ $product->name_ur }}</p>
        </div>
        <div class="agricart-labels__product-codes">
            <code>{{ $product->label_code }}</code>
            <span>{{ $product->code }}</span>
        </div>
    </div>

    <div class="agricart-labels__stickers">
        @foreach ($stickers as $sticker)
            @include('livewire.labels.partials.sticker', [
                'title' => $sticker['title'],
                'type' => $sticker['type'],
                'locale' => $sticker['locale'],
                'data' => $sticker['data'],
            ])
        @endforeach
    </div>
</div>
