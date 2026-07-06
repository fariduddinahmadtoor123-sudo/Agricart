<div class="agricart-labels">
    @if ($products->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.labels.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-labels__grid">
            @foreach ($products as $product)
                @include('livewire.labels.partials.product-card', ['product' => $product])
            @endforeach
        </div>

        <div class="agricart-categories__pagination">
            {{ $products->links() }}
        </div>
    @endif
</div>
