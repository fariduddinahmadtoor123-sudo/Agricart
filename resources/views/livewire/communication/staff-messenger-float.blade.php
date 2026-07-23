@if ($authUser)
    <div
        class="agricart-staff-messenger"
        @if ($open) wire:poll.8s @else wire:poll.45s @endif
        x-data
        x-on:staff-messenger-scroll.window="$nextTick(() => { const el = $refs.messages; if (el) el.scrollTop = el.scrollHeight })"
    >
        @if ($open)
            <section
                class="agricart-staff-messenger__panel"
                wire:key="staff-messenger-panel-{{ $peerId ?? 'list' }}"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('communication.messenger.title') }}"
            >
                <header class="agricart-staff-messenger__head">
                    @if ($peerId && $peer)
                        <button type="button" class="agricart-staff-messenger__back" wire:click="backToList" aria-label="{{ __('communication.messenger.back') }}">←</button>
                        <div class="agricart-staff-messenger__peer">
                            <strong>{{ $peer->name }}</strong>
                            <span>
                                @php
                                    $peerOnline = collect($directory)->first(fn ($row) => (int) $row['user']->id === (int) $peer->id)['online'] ?? false;
                                @endphp
                                {{ $peerOnline ? __('communication.messenger.online') : __('communication.messenger.offline') }}
                            </span>
                        </div>
                    @else
                        <div>
                            <strong>{{ __('communication.messenger.title') }}</strong>
                            <span>{{ __('communication.messenger.lead') }}</span>
                        </div>
                    @endif
                    <button type="button" class="agricart-staff-messenger__close" wire:click="toggle" aria-label="{{ __('communication.messenger.close') }}">×</button>
                </header>

                @if ($errorMessage)
                    <p class="agricart-staff-messenger__error agricart-staff-messenger__error--banner" role="alert">{{ $errorMessage }}</p>
                @endif

                @if ($peerId && $peer)
                    <ol class="agricart-staff-messenger__messages" x-ref="messages" wire:key="dm-list-{{ $peerId }}-{{ $messages->count() }}">
                        @forelse ($messages as $message)
                            @php
                                $isMine = $message->participant
                                    && $message->participant->actor_type === 'user'
                                    && (int) $message->participant->actor_id === (int) $authUser->id;
                            @endphp
                            <li
                                wire:key="dm-{{ $message->id }}"
                                @class(['agricart-staff-messenger__bubble', 'is-mine' => $isMine])
                            >
                                <p>{{ $message->body }}</p>
                                <time>{{ $message->created_at?->timezone(config('app.timezone'))->format('H:i') }}</time>
                            </li>
                        @empty
                            <li class="agricart-staff-messenger__empty">{{ __('communication.messenger.empty_thread') }}</li>
                        @endforelse
                    </ol>

                    <form class="agricart-staff-messenger__compose" wire:submit.prevent="send">
                        <input
                            type="text"
                            class="fi-input"
                            wire:model="messageText"
                            placeholder="{{ __('communication.messenger.placeholder') }}"
                            maxlength="5000"
                            autocomplete="off"
                            wire:loading.attr="disabled"
                            wire:target="send"
                        />
                        @error('body') <p class="agricart-staff-messenger__error">{{ $message }}</p> @enderror
                        <button
                            type="submit"
                            class="agricart-categories__action is-primary"
                            wire:loading.attr="disabled"
                            wire:target="send"
                        >{{ __('communication.messenger.send') }}</button>
                    </form>
                @else
                    <div class="agricart-staff-messenger__search">
                        <label class="sr-only" for="agricart-staff-messenger-search">{{ __('communication.messenger.search') }}</label>
                        <input
                            id="agricart-staff-messenger-search"
                            type="search"
                            class="fi-input"
                            wire:model.live.debounce.200ms="staffSearch"
                            placeholder="{{ __('communication.messenger.search') }}"
                        />
                    </div>

                    <ol class="agricart-staff-messenger__people">
                        @forelse ($directory as $row)
                            <li wire:key="staff-dm-{{ $row['user']->id }}">
                                <button
                                    type="button"
                                    class="agricart-staff-messenger__person"
                                    wire:click.prevent="openPeer({{ (int) $row['user']->id }})"
                                >
                                    <span @class(['agricart-staff-messenger__dot', 'is-online' => $row['online']])></span>
                                    <span class="agricart-staff-messenger__person-text">
                                        <strong>{{ $row['user']->name }}</strong>
                                        <span>
                                            @if ($row['last_body'])
                                                {{ \Illuminate\Support\Str::limit($row['last_body'], 42) }}
                                            @else
                                                {{ $row['online'] ? __('communication.messenger.online') : __('communication.messenger.start') }}
                                            @endif
                                        </span>
                                    </span>
                                    @if ($row['unread'] > 0)
                                        <span class="agricart-staff-messenger__badge">{{ $row['unread'] }}</span>
                                    @endif
                                </button>
                            </li>
                        @empty
                            <li class="agricart-staff-messenger__empty">{{ __('communication.messenger.empty_people') }}</li>
                        @endforelse
                    </ol>
                @endif
            </section>
        @endif

        <button
            type="button"
            class="agricart-staff-messenger__fab"
            wire:click="toggle"
            title="{{ __('communication.messenger.open') }}"
            aria-label="{{ __('communication.messenger.open') }}"
            aria-expanded="{{ $open ? 'true' : 'false' }}"
        >
            @if ($unreadTotal > 0 && ! $open)
                <span class="agricart-staff-messenger__fab-badge">{{ $unreadTotal > 9 ? '9+' : $unreadTotal }}</span>
            @endif
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
            </svg>
        </button>
    </div>
@endif
