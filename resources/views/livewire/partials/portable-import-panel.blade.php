@props([
    'hint',
    'importLabel',
    'cancelLabel',
    'title' => null,
])

<section class="agricart-portable-import" wire:key="portable-import-panel">
    <header class="agricart-portable-import__head">
        <h2 class="agricart-portable-import__title">{{ $title ?? __('agricart.portable.panel_title') }}</h2>
        <p class="agricart-portable-import__hint">{{ $hint }}</p>
    </header>

    <div class="agricart-portable-import__body">
        <label class="agricart-portable-import__file">
            <span class="agricart-portable-import__file-label">{{ __('agricart.portable.choose_file') }}</span>
            <input
                type="file"
                wire:model="importArchive"
                accept=".ags,application/octet-stream"
                class="agricart-portable-import__input"
            />
        </label>

        <p class="agricart-portable-import__upload-status" wire:loading wire:target="importArchive">
            {{ __('agricart.portable.uploading') }}
        </p>

        @if ($importArchive)
            <p class="agricart-portable-import__ready" wire:loading.remove wire:target="importArchive">
                {{ __('agricart.portable.file_ready', ['name' => $importArchive->getClientOriginalName()]) }}
            </p>
        @endif

        @error('importArchive')
            <p class="agricart-portable-import__error" role="alert">{{ $message }}</p>
        @enderror

        <div class="agricart-portable-import__actions">
            <button
                type="button"
                class="agricart-categories__action is-primary"
                wire:click="importUploadedArchive"
                wire:loading.attr="disabled"
                wire:target="importArchive,importUploadedArchive"
            >
                <span wire:loading.remove wire:target="importUploadedArchive">{{ $importLabel }}</span>
                <span wire:loading wire:target="importUploadedArchive">{{ __('agricart.portable.importing') }}</span>
            </button>
            <button
                type="button"
                class="agricart-categories__action"
                wire:click="closeImportPanel"
                wire:loading.attr="disabled"
                wire:target="importUploadedArchive"
            >
                {{ $cancelLabel }}
            </button>
        </div>
    </div>
</section>
