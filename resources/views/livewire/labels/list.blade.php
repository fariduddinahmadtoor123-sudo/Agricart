<div class="agricart-labels">
    @if ($labelProfiles->isNotEmpty())
        <div class="agricart-labels__toolbar">
            <label class="agricart-labels__profile-picker">
                <span>{{ __('agricart.labels.print.profile_label') }}</span>
                <select wire:model.live="labelProfileKey" class="agricart-backups__input">
                    @foreach ($labelProfiles as $profile)
                        <option value="{{ $profile->key }}">{{ $profile->localizedName() }} ({{ $profile->dimensionsLabel() }})</option>
                    @endforeach
                </select>
            </label>
            <p class="agricart-labels__toolbar-hint">{{ __('agricart.labels.print.profile_hint') }}</p>
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.labels.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-labels__grid">
            @foreach ($products as $product)
                @include('livewire.labels.partials.product-card', [
                    'product' => $product,
                    'labelProfileKey' => $labelProfileKey,
                ])
            @endforeach
        </div>

        <div class="agricart-categories__pagination">
            {{ $products->links() }}
        </div>
    @endif
</div>
