<div class="agricart-media-library">
    @if ($statusMessage)
        <div @class([
            'agricart-media-library__notice',
            'is-danger' => $statusType === 'danger',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-media-library__summary">
        @foreach ([
            'active' => ['label' => __('agricart.media.stats.all'), 'count' => $stats['all']],
            'used' => ['label' => __('agricart.media.stats.used'), 'count' => $stats['used']],
            'unused' => ['label' => __('agricart.media.stats.unused'), 'count' => $stats['unused']],
            'trash' => ['label' => __('agricart.media.stats.trash'), 'count' => $stats['trash']],
        ] as $filter => $stat)
            <button
                type="button"
                @class(['agricart-media-library__summary-card', 'is-active' => $statusFilter === $filter || ($filter === 'active' && $statusFilter === 'active')])
                wire:click="{{ $filter === 'trash' ? "applyFilter('status:trash')" : ($filter === 'active' ? "applyFilter('status:active')" : "applyFilter('usage:{$filter}')") }}"
            >
                <span>{{ $stat['label'] }}</span>
                <strong>{{ number_format($stat['count']) }}</strong>
            </button>
        @endforeach
    </div>

    @if ($canManage && $usageFilter === 'unused' && $statusFilter !== 'trash' && $stats['unused'] > 0)
        <div class="agricart-media-library__bulk">
            <p>{{ __('agricart.media.bulk_unused_help') }}</p>
            <button
                type="button"
                class="agricart-media-library__bulk-btn"
                wire:click="trashAllUnused"
                wire:confirm="{{ __('agricart.media.confirm_trash_unused') }}"
            >
                {{ __('agricart.media.actions.trash_all_unused') }}
            </button>
        </div>
    @endif

    @if ($assets->isEmpty())
        <div class="agricart-media-library__empty">
            <strong>{{ __('agricart.media.empty_title') }}</strong>
            <span>{{ __('agricart.media.empty_text') }}</span>
        </div>
    @else
        <div class="agricart-media-library__grid">
            @foreach ($assets as $asset)
                @php($assetUrl = $asset->url())
                <article class="agricart-media-card" wire:key="media-asset-{{ $asset->id }}">
                    <button
                        type="button"
                        class="agricart-media-card__preview"
                        wire:click="openAsset({{ $asset->id }})"
                        aria-label="{{ __('agricart.media.actions.view') }}: {{ $asset->display_name }}"
                    >
                        @if ($assetUrl)
                            <img src="{{ $assetUrl }}" alt="{{ $asset->display_name }}" loading="lazy" decoding="async" />
                        @else
                            <span aria-hidden="true">×</span>
                        @endif
                    </button>
                    <div class="agricart-media-card__body">
                        <strong title="{{ $asset->display_name }}">{{ $asset->display_name }}</strong>
                        @if ($asset->original_name)
                            <span title="{{ $asset->original_name }}">{{ $asset->original_name }}</span>
                        @endif
                        <small>
                            {{ $asset->width && $asset->height ? "{$asset->width} × {$asset->height}" : __('agricart.media.unknown_dimensions') }}
                            · {{ number_format($asset->byte_size / 1024, 1) }} KB
                        </small>
                        <div class="agricart-media-card__badges">
                            <span>{{ ucfirst($asset->source_module) }}</span>
                            <span>{{ trans_choice('agricart.media.usage_count', $asset->usages_count, ['count' => $asset->usages_count]) }}</span>
                        </div>
                    </div>
                    <div class="agricart-media-card__actions">
                        <button type="button" wire:click="openAsset({{ $asset->id }})">{{ __('agricart.media.actions.view') }}</button>
                        @if (! $asset->trashed_at)
                            <button type="button" wire:click="selectAsset({{ $asset->id }})">{{ __('agricart.media.actions.select') }}</button>
                        @endif
                        @if ($canManage && $asset->usages_count === 0)
                            @if ($asset->trashed_at)
                                <button type="button" wire:click="restoreAsset({{ $asset->id }})">{{ __('agricart.media.actions.restore') }}</button>
                            @else
                                <button type="button" class="is-danger" wire:click="trashAsset({{ $asset->id }})">{{ __('agricart.media.actions.trash') }}</button>
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="agricart-media-library__pagination">
            {{ $assets->links() }}
        </div>
    @endif
</div>
