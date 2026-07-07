@php
    $tag = $this->tagPreviewForProduct($product);
@endphp

<div class="agricart-price-tags__product" wire:key="price-tag-product-{{ $product->id }}">
    <div class="agricart-price-tags__product-head">
        <div>
            <h3 class="agricart-price-tags__product-title">{{ $product->name_en }}</h3>
            @if (filled($product->name_ur))
                <p class="agricart-price-tags__product-meta" dir="rtl">{{ $product->name_ur }}</p>
            @endif
        </div>
        <div class="agricart-price-tags__product-codes">
            @if (filled($product->label_code))
                <code>{{ $product->label_code }}</code>
            @endif
            <span>{{ $product->code }}</span>
        </div>
    </div>

    <div class="agricart-price-tags__product-preview">
        @include('livewire.price-tags.partials.tag-sticker', ['tag' => $tag, 'preview' => true])
    </div>

    <div class="agricart-price-tags__product-actions">
        @if ($canCreate)
            <button type="button" class="agricart-price-tags__btn" wire:click="addProduct({{ $product->id }})">
                {{ __('price_tags.actions.add_to_queue') }}
            </button>
        @endif
        <button type="button" class="agricart-price-tags__btn is-primary" wire:click="printProduct({{ $product->id }})">
            {{ __('price_tags.actions.print_one') }}
        </button>
    </div>
</div>
