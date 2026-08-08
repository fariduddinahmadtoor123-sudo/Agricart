<div class="agricart-media-form">
    @if ($statusMessage)
        <div @class(['agricart-media-form__notice', 'is-danger' => $statusType === 'danger']) role="status">
            {{ $statusMessage }}
        </div>
    @endif

    @if (! $asset)
        <section class="agricart-media-form__upload">
            <label>
                <span>{{ __('agricart.media.fields.file') }} *</span>
                <input type="file" wire:model="upload" accept="image/jpeg,image/png,image/webp,image/gif" />
                @error('upload') <small class="is-error">{{ $message }}</small> @enderror
            </label>

            @if ($upload)
                <img src="{{ $upload->temporaryUrl() }}" alt="" class="agricart-media-form__upload-preview" />
            @endif

            <label>
                <span>{{ __('agricart.media.fields.display_name') }}</span>
                <input type="text" wire:model="displayName" maxlength="255" placeholder="{{ __('agricart.media.fields.display_name_placeholder') }}" />
                @error('displayName') <small class="is-error">{{ $message }}</small> @enderror
            </label>

            <p>{{ __('agricart.media.upload_help') }}</p>
            <button type="button" class="agricart-media-form__primary" wire:click="save" wire:loading.attr="disabled">
                {{ __('agricart.media.actions.upload') }}
            </button>
        </section>
    @else
        <div class="agricart-media-form__detail" x-data="{ copied: false }">
            <div class="agricart-media-form__visual">
                @if ($asset->url())
                    <img src="{{ $asset->url() }}" alt="" />
                @else
                    <span>{{ __('agricart.media.missing_file') }}</span>
                @endif
            </div>

            <div class="agricart-media-form__information">
                <label>
                    <span>{{ __('agricart.media.fields.display_name') }}</span>
                    <input type="text" wire:model="displayName" maxlength="255" @disabled(! $canManage) />
                    @error('displayName') <small class="is-error">{{ $message }}</small> @enderror
                </label>

                <dl>
                    <div><dt>{{ __('agricart.media.fields.original_name') }}</dt><dd>{{ $asset->original_name ?: '—' }}</dd></div>
                    <div><dt>{{ __('agricart.media.fields.type') }}</dt><dd>{{ $asset->mime_type ?: '—' }}</dd></div>
                    <div><dt>{{ __('agricart.media.fields.dimensions') }}</dt><dd>{{ $asset->width && $asset->height ? "{$asset->width} × {$asset->height}" : '—' }}</dd></div>
                    <div><dt>{{ __('agricart.media.fields.size') }}</dt><dd>{{ number_format($asset->byte_size / 1024, 1) }} KB</dd></div>
                    <div><dt>{{ __('agricart.media.fields.source') }}</dt><dd>{{ ucfirst($asset->source_module) }}</dd></div>
                    <div><dt>{{ __('agricart.media.fields.uploaded') }}</dt><dd>{{ $asset->created_at?->format('d M Y, h:i A') }}</dd></div>
                    <div><dt>{{ __('agricart.media.fields.path') }}</dt><dd dir="ltr">{{ $asset->path }}</dd></div>
                    <div>
                        <dt>{{ __('agricart.media.fields.variant') }}</dt>
                        <dd>{{ __('agricart.media.variants.storefront') }}</dd>
                    </div>
                </dl>

                @if (is_array($pairedOriginal) && isset($pairedOriginal['asset']))
                    @php($master = $pairedOriginal['asset'])
                    <div class="agricart-media-form__master" role="note">
                        <strong>{{ __('agricart.media.master_original.title') }}</strong>
                        <p>{{ __('agricart.media.master_original.help') }}</p>
                        <dl>
                            <div>
                                <dt>{{ __('agricart.media.master_original.status') }}</dt>
                                <dd>
                                    {{ $pairedOriginal['file_exists']
                                        ? __('agricart.media.master_original.on_disk')
                                        : __('agricart.media.master_original.missing') }}
                                </dd>
                            </div>
                            <div>
                                <dt>{{ __('agricart.media.fields.size') }}</dt>
                                <dd>{{ number_format($master->byte_size / 1024, 1) }} KB</dd>
                            </div>
                            <div>
                                <dt>{{ __('agricart.media.fields.path') }}</dt>
                                <dd dir="ltr">{{ $master->path }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif

                <div class="agricart-media-form__actions">
                    @if ($canManage)
                        <button type="button" class="agricart-media-form__primary" wire:click="save">{{ __('agricart.media.actions.save_name') }}</button>
                    @endif
                    @if (! $asset->trashed_at)
                        <button type="button" wire:click="selectAsset">{{ __('agricart.media.actions.select') }}</button>
                    @endif
                    @if ($asset->url())
                        <button
                            type="button"
                            x-on:click="navigator.clipboard.writeText(@js($asset->url())); copied = true; setTimeout(() => copied = false, 1500)"
                            x-text="copied ? @js(__('agricart.media.actions.copied')) : @js(__('agricart.media.actions.copy_url'))"
                        ></button>
                        <a href="{{ $asset->url() }}" download>{{ __('agricart.media.actions.download') }}</a>
                    @endif
                    @if ($canManage && $asset->usages->isEmpty())
                        @if ($asset->trashed_at)
                            <button type="button" wire:click="restore">{{ __('agricart.media.actions.restore') }}</button>
                            <button type="button" class="is-danger" wire:click="permanentlyDelete" wire:confirm="{{ __('agricart.media.confirm_delete') }}">
                                {{ __('agricart.media.actions.delete_permanently') }}
                            </button>
                        @else
                            <button type="button" class="is-danger" wire:click="trash">{{ __('agricart.media.actions.trash') }}</button>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <section class="agricart-media-form__usages">
            <h3>{{ __('agricart.media.used_in') }} ({{ $asset->usages->count() }})</h3>
            @if ($asset->usages->isEmpty())
                <p>{{ __('agricart.media.not_used') }}</p>
            @else
                <ul>
                    @foreach ($asset->usages as $usage)
                        <li>
                            <strong>{{ $usage->label ?: $usage->collection }}</strong>
                            <span>{{ class_basename($usage->usable_type) }} #{{ $usage->usable_id }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
