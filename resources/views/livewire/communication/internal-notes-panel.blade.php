<div class="agricart-internal-notes">
    <header class="agricart-internal-notes__head">
        <h3>{{ __('communication.internal_notes.heading') }}</h3>
        <p>{{ __('communication.internal_notes.lead') }}</p>
    </header>

    <ol class="agricart-internal-notes__list">
        @forelse ($messages as $message)
            @php
                $isMine = $authUser
                    && $message->participant
                    && (int) $message->participant->actor_id === (int) $authUser->id
                    && $message->participant->actor_type === 'user';
            @endphp
            <li
                wire:key="internal-note-{{ $message->id }}"
                @class(['agricart-internal-notes__item', 'is-mine' => $isMine, 'is-system' => $message->message_type === 'system'])
            >
                <p class="agricart-internal-notes__meta">
                    <strong>
                        @if ($message->participant?->actor_type === 'user' && $message->participant->actor_id)
                            {{ $authorNames[$message->participant->actor_id] ?? __('communication.internal_notes.staff') }}
                        @else
                            {{ __('communication.internal_notes.system') }}
                        @endif
                    </strong>
                    · {{ agricart_datetime($message->created_at, 'd M Y H:i') }}
                </p>
                @if (filled($message->body))
                    <p class="agricart-internal-notes__body">{{ $message->body }}</p>
                @endif
                @if ($message->attachments->isNotEmpty())
                    <ul class="agricart-internal-notes__files">
                        @foreach ($message->attachments as $attachment)
                            <li>
                                <a
                                    href="{{ route('admin.communication.attachments.show', $attachment) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >{{ $attachment->original_name }}</a>
                                <span>{{ strtoupper($attachment->kind) }} · {{ number_format($attachment->size / 1024, 1) }} KB</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @empty
            <li class="agricart-internal-notes__empty">{{ __('communication.internal_notes.empty') }}</li>
        @endforelse
    </ol>

    <form class="agricart-internal-notes__compose" wire:submit.prevent="postNote">
        <label>
            <span class="sr-only">{{ __('communication.internal_notes.body_label') }}</span>
            <textarea
                rows="3"
                class="fi-input"
                wire:model="noteBody"
                placeholder="{{ __('communication.internal_notes.body_placeholder') }}"
            ></textarea>
        </label>

        <label class="agricart-internal-notes__files-input">
            <span>{{ __('communication.internal_notes.files_label') }}</span>
            <input type="file" wire:model="noteFiles" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip" />
        </label>

        @error('body') <p class="agricart-internal-notes__error">{{ $message }}</p> @enderror
        @error('noteFiles.*') <p class="agricart-internal-notes__error">{{ $message }}</p> @enderror
        @error('attachments') <p class="agricart-internal-notes__error">{{ $message }}</p> @enderror
        @error('attachments.0') <p class="agricart-internal-notes__error">{{ $message }}</p> @enderror

        <div wire:loading wire:target="noteFiles" class="agricart-internal-notes__loading">
            {{ __('communication.internal_notes.uploading') }}
        </div>

        @if (count($noteFiles) > 0)
            <ul class="agricart-internal-notes__pending">
                @foreach ($noteFiles as $file)
                    <li wire:key="pending-{{ $loop->index }}">{{ $file->getClientOriginalName() }}</li>
                @endforeach
            </ul>
        @endif

        <button type="submit" class="agricart-categories__action is-primary" wire:loading.attr="disabled">
            {{ __('communication.internal_notes.post') }}
        </button>
    </form>
</div>
