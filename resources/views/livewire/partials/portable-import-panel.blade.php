@props([
    'hint',
    'importLabel',
    'cancelLabel',
    'title' => null,
])

<section
    class="agricart-portable-import"
    wire:key="portable-import-panel"
    x-data="{
        uploading: false,
        progress: 0,
        uploadingLabel: @js(__('agricart.portable.uploading_percent')),
    }"
    x-on:livewire-upload-start="uploading = true; progress = 0"
    x-on:livewire-upload-finish="uploading = false; progress = 100"
    x-on:livewire-upload-cancel="uploading = false; progress = 0"
    x-on:livewire-upload-error="uploading = false; progress = 0"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
>
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
                x-bind:disabled="uploading"
            />
        </label>

        <div
            class="agricart-portable-import__progress"
            x-show="uploading"
            x-cloak
            role="status"
            aria-live="polite"
        >
            <div class="agricart-portable-import__progress-track" aria-hidden="true">
                <div
                    class="agricart-portable-import__progress-bar"
                    x-bind:style="'width: ' + progress + '%'"
                ></div>
            </div>
            <p class="agricart-portable-import__progress-label" x-text="uploadingLabel.replace(':percent', String(progress))"></p>
        </div>

        <div
            class="agricart-portable-import__progress"
            wire:loading.flex
            wire:target="importUploadedArchive"
            role="status"
            aria-live="polite"
        >
            <div class="agricart-portable-import__progress-track" aria-hidden="true">
                <div class="agricart-portable-import__progress-bar is-pulse"></div>
            </div>
            <p class="agricart-portable-import__progress-label">
                {{ __('agricart.portable.importing_hint') }}
            </p>
        </div>

        @if ($importArchive)
            <p
                class="agricart-portable-import__ready"
                x-show="!uploading"
                wire:loading.remove
                wire:target="importArchive,importUploadedArchive"
            >
                {{ __('agricart.portable.file_ready', ['name' => $importArchive->getClientOriginalName()]) }}
            </p>
            <p
                class="agricart-portable-import__hint"
                x-show="!uploading"
                wire:loading.remove
                wire:target="importArchive,importUploadedArchive"
            >
                {{ __('agricart.portable.click_import_hint') }}
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
                x-bind:disabled="uploading"
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
                x-bind:disabled="uploading"
            >
                {{ $cancelLabel }}
            </button>
        </div>
    </div>
</section>
