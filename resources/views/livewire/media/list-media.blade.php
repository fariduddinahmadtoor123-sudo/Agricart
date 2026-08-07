<div class="agricart-media-library">
    @if ($statusMessage)
        <div @class([
            'agricart-media-library__notice',
            'is-danger' => $statusType === 'danger',
        ]) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="agricart-media-library__summary" role="tablist" aria-label="{{ __('agricart.media.stats.nav_label') }}">
        @foreach ([
            'all' => ['label' => __('agricart.media.stats.all'), 'hint' => __('agricart.media.stats.hints.all')],
            'used' => ['label' => __('agricart.media.stats.used'), 'hint' => __('agricart.media.stats.hints.used')],
            'unused' => ['label' => __('agricart.media.stats.unused'), 'hint' => __('agricart.media.stats.hints.unused')],
            'trash' => ['label' => __('agricart.media.stats.trash'), 'hint' => __('agricart.media.stats.hints.trash')],
        ] as $bucket => $meta)
            <button
                type="button"
                role="tab"
                aria-selected="{{ $activeBucket === $bucket ? 'true' : 'false' }}"
                title="{{ $meta['hint'] }}"
                @class(['agricart-media-library__summary-card', 'is-active' => $activeBucket === $bucket])
                wire:click="setSummaryBucket('{{ $bucket }}')"
            >
                <span class="agricart-media-library__summary-copy">
                    <span class="agricart-media-library__summary-label">{{ $meta['label'] }}</span>
                    <span class="agricart-media-library__summary-size">{{ $formattedSizes[$bucket] }}</span>
                </span>
                <strong>{{ number_format($stats[$bucket]) }}</strong>
            </button>
        @endforeach
    </div>

    @if ($canManage && $activeBucket === 'unused' && $stats['unused'] > 0)
        <div class="agricart-media-library__bulk">
            <div class="agricart-media-library__bulk-copy">
                <p>{{ __('agricart.media.bulk_unused_help') }}</p>
                <p class="agricart-media-library__bulk-meta">
                    {{ __('agricart.media.bulk_unused_size', ['size' => $formattedSizes['unused'], 'count' => number_format($stats['unused'])]) }}
                </p>
            </div>
            <button
                type="button"
                class="agricart-media-library__bulk-btn"
                wire:click="trashAllUnused"
                wire:confirm="{{ __('agricart.media.confirm_trash_unused_sized', ['size' => $formattedSizes['unused'], 'count' => number_format($stats['unused'])]) }}"
            >
                {{ __('agricart.media.actions.trash_all_unused') }}
            </button>
        </div>
    @endif

    @if ($canManage && $activeBucket === 'trash')
        <div class="agricart-media-library__bulk agricart-media-library__bulk--trash">
            <div class="agricart-media-library__bulk-copy">
                <p>{{ __('agricart.media.bulk_trash_help', ['days' => $stats['trash_retention_days']]) }}</p>
                @if ($stats['trash'] > 0)
                    <p class="agricart-media-library__bulk-meta">
                        {{ __('agricart.media.bulk_trash_size', ['size' => $formattedSizes['trash'], 'count' => number_format($stats['trash'])]) }}
                    </p>
                @endif
            </div>
            <div class="agricart-media-library__bulk-actions">
                <button
                    type="button"
                    class="agricart-media-library__bulk-btn agricart-media-library__bulk-btn--secondary"
                    wire:click="pruneOrphanFiles"
                    wire:confirm="{{ __('agricart.media.confirm_prune_orphans') }}"
                >
                    {{ __('agricart.media.actions.prune_orphans') }}
                </button>
                @if ($stats['trash'] > 0)
                    <button
                        type="button"
                        class="agricart-media-library__bulk-btn"
                        wire:click="emptyTrash"
                        wire:confirm="{{ __('agricart.media.confirm_empty_trash', ['size' => $formattedSizes['trash'], 'count' => number_format($stats['trash'])]) }}"
                    >
                        {{ __('agricart.media.actions.empty_trash') }}
                    </button>
                @endif
            </div>
        </div>
    @endif

    @if ($canManage && $activeBucket === 'all')
        <div class="agricart-media-library__bulk agricart-media-library__bulk--subtle">
            <div class="agricart-media-library__bulk-copy">
                <p>{{ __('agricart.media.bulk_repair_help') }}</p>
                <p>{{ __('agricart.media.bulk_orphans_help') }}</p>
            </div>
            <div class="agricart-media-library__bulk-actions">
                <button
                    type="button"
                    class="agricart-media-library__bulk-btn agricart-media-library__bulk-btn--secondary"
                    wire:click="repairUsageLinks"
                    wire:confirm="{{ __('agricart.media.confirm_repair_links') }}"
                >
                    {{ __('agricart.media.actions.repair_links') }}
                </button>
                <button
                    type="button"
                    class="agricart-media-library__bulk-btn agricart-media-library__bulk-btn--secondary"
                    wire:click="pruneOrphanFiles"
                    wire:confirm="{{ __('agricart.media.confirm_prune_orphans') }}"
                >
                    {{ __('agricart.media.actions.prune_orphans') }}
                </button>
            </div>
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
