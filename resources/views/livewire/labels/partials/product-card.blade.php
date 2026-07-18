@php
    use App\Services\Printing\LabelRenderService;
    use Illuminate\Support\Facades\Storage;

    $renderer = app(LabelRenderService::class);
    $stickers = [
        ['type' => 'qr', 'locale' => 'en', 'title' => __('agricart.labels.sticker.qr_en'), 'data' => $product->label_qr['en'] ?? null],
        ['type' => 'barcode', 'locale' => 'en', 'title' => __('agricart.labels.sticker.barcode_en'), 'data' => $product->label_barcode['en'] ?? null],
        ['type' => 'qr', 'locale' => 'ur', 'title' => __('agricart.labels.sticker.qr_ur'), 'data' => $product->label_qr['ur'] ?? null],
        ['type' => 'barcode', 'locale' => 'ur', 'title' => __('agricart.labels.sticker.barcode_ur'), 'data' => $product->label_barcode['ur'] ?? null],
    ];
    $previewSticker = $stickers[0];
    $previewData = filled($product->image_path)
        ? null
        : $renderer->stickerData($product, $previewSticker['locale'], $previewSticker['type']);
@endphp

<details class="agricart-labels__product" wire:key="label-product-{{ $product->id }}">
    <summary class="agricart-labels__product-summary">
        <div class="agricart-labels__product-head">
            <h3 class="agricart-labels__product-title">{{ $product->name_en }}</h3>
            <span class="agricart-labels__expand-label">View all labels</span>
        </div>
        <div class="agricart-labels__preview">
            @if (filled($product->image_path))
                <div class="agricart-labels__preview-image-wrap">
                    <img
                        src="{{ Storage::disk('public')->url($product->image_path) }}"
                        alt="{{ $product->name_en }}"
                        class="agricart-labels__preview-image"
                        loading="lazy"
                    />
                </div>
            @else
                @include('livewire.labels.partials.sticker', [
                    'title' => $previewSticker['title'],
                    'type' => $previewSticker['type'],
                    'locale' => $previewSticker['locale'],
                    'data' => $previewData,
                ])
            @endif
        </div>
    </summary>

    <div class="agricart-labels__details">
        <div class="agricart-labels__product-codes">
            <code>{{ $product->label_code }}</code>
            <span>{{ $product->code }}</span>
        </div>
        <div class="agricart-labels__stickers">
        @foreach ($stickers as $sticker)
            @php
                $stickerData = $renderer->stickerData($product, $sticker['locale'], $sticker['type']);
                $printUrl = route('admin.labels.print', [
                    'product' => $product->id,
                    'type' => $sticker['type'],
                    'locale' => $sticker['locale'],
                    'profile' => $labelProfileKey,
                ]);
            @endphp
            <div class="agricart-labels__sticker-wrap">
                @include('livewire.labels.partials.sticker', [
                    'title' => $sticker['title'],
                    'type' => $sticker['type'],
                    'locale' => $sticker['locale'],
                    'data' => $stickerData,
                ])
                <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="agricart-labels__print-btn">
                    {{ __('agricart.labels.print.print_button') }}
                </a>
            </div>
        @endforeach
        </div>
    </div>
</details>
