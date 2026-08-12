<div class="agricart-record-thread">
    <header class="agricart-record-thread__head">
        <h3>{{ $channelName !== '' ? $channelName : __('communication.record_threads.heading') }}</h3>
        <p>{{ __('communication.record_threads.lead') }}</p>
    </header>

    <ol class="agricart-record-thread__list">
        @forelse ($messages as $message)
            @php
                $isMine = $authUser
                    && $message->participant
                    && (int) $message->participant->actor_id === (int) $authUser->id
                    && $message->participant->actor_type === 'user';
                $actorLabel = match ($message->participant?->actor_type) {
                    'user' => $authorNames[$message->participant->actor_id] ?? __('communication.record_threads.staff'),
                    'supplier' => __('communication.record_threads.supplier'),
                    'customer' => __('communication.record_threads.customer'),
                    default => __('communication.record_threads.system'),
                };
            @endphp
            <li
                wire:key="record-thread-msg-{{ $message->id }}"
                @class(['agricart-record-thread__item', 'is-mine' => $isMine])
            >
                <p class="agricart-record-thread__meta">
                    <strong>{{ $actorLabel }}</strong>
                    · {{ agricart_datetime($message->created_at, 'd M Y H:i') }}
                </p>
                @if (filled($message->body))
                    <p class="agricart-record-thread__body">{{ $message->body }}</p>
                @endif
                @if ($message->attachments->isNotEmpty())
                    <ul class="agricart-record-thread__files">
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
            <li class="agricart-record-thread__empty">{{ __('communication.record_threads.empty') }}</li>
        @endforelse
    </ol>

    <form class="agricart-record-thread__compose" wire:submit.prevent="postMessage">
        <label>
            <span class="sr-only">{{ __('communication.record_threads.body_label') }}</span>
            <textarea
                rows="3"
                class="fi-input"
                wire:model="messageBody"
                placeholder="{{ __('communication.record_threads.body_placeholder') }}"
            ></textarea>
        </label>

        <label class="agricart-record-thread__files-input">
            <span>{{ __('communication.record_threads.files_label') }}</span>
            <input type="file" wire:model="messageFiles" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip" />
        </label>

        @error('body') <p class="agricart-record-thread__error">{{ $message }}</p> @enderror
        @error('messageFiles.*') <p class="agricart-record-thread__error">{{ $message }}</p> @enderror
        @error('attachments') <p class="agricart-record-thread__error">{{ $message }}</p> @enderror
        @error('attachments.0') <p class="agricart-record-thread__error">{{ $message }}</p> @enderror

        <div wire:loading wire:target="messageFiles" class="agricart-record-thread__loading">
            {{ __('communication.record_threads.uploading') }}
        </div>

        @if (count($messageFiles) > 0)
            <ul class="agricart-record-thread__pending">
                @foreach ($messageFiles as $file)
                    <li wire:key="record-pending-{{ $loop->index }}">{{ $file->getClientOriginalName() }}</li>
                @endforeach
            </ul>
        @endif

        <button type="submit" class="agricart-categories__action is-primary" wire:loading.attr="disabled">
            {{ __('communication.record_threads.send') }}
        </button>
    </form>
</div>
