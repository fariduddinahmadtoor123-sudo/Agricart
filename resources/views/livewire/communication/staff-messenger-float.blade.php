@if ($authUser)
    <div
        class="agricart-staff-messenger"
        @if ($open && $peerId) wire:poll.8s @elseif ($open) wire:poll.30s @else wire:poll.45s @endif
        x-data="{ picking: false }"
        x-on:staff-messenger-scroll.window="$nextTick(() => { const el = $refs.messages; if (el) el.scrollTop = el.scrollHeight })"
    >
        @if ($open)
            <section
                class="agricart-staff-messenger__panel"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('communication.messenger.title') }}"
            >
                <header class="agricart-staff-messenger__head">
                    <div class="agricart-staff-messenger__brand">
                        <strong>{{ __('communication.messenger.title') }}</strong>
                        <span>{{ __('communication.messenger.lead') }}</span>
                    </div>
                    <button type="button" class="agricart-staff-messenger__close" wire:click="toggle" aria-label="{{ __('communication.messenger.close') }}">×</button>
                </header>

                @if ($errorMessage)
                    <p class="agricart-staff-messenger__error agricart-staff-messenger__error--banner" role="alert">{{ $errorMessage }}</p>
                @endif

                <div class="agricart-staff-messenger__search" wire:key="messenger-search">
                    <label class="sr-only" for="agricart-staff-messenger-search">{{ __('communication.messenger.search') }}</label>
                    <input
                        id="agricart-staff-messenger-search"
                        type="search"
                        class="fi-input"
                        wire:model.live.debounce.250ms="staffSearch"
                        placeholder="{{ __('communication.messenger.search') }}"
                        autocomplete="off"
                        @focus="picking = true"
                        @blur="setTimeout(() => picking = false, 180)"
                    />

                    @if ($peerId === null && $suggestions->isNotEmpty())
                        <ul class="agricart-staff-messenger__suggestions" role="listbox" aria-label="{{ __('communication.messenger.search') }}">
                            @foreach ($suggestions as $row)
                                @php $personId = (int) $row['user']->id; @endphp
                                <li wire:key="staff-suggest-{{ $personId }}" role="option">
                                    <button
                                        type="button"
                                        class="agricart-staff-messenger__person"
                                        x-on:mousedown.prevent="$wire.selectPeer({{ $personId }})"
                                    >
                                        <span @class(['agricart-staff-messenger__dot', 'is-online' => $row['online']])></span>
                                        <span class="agricart-staff-messenger__person-text">
                                            <strong>{{ $row['user']->name }}</strong>
                                            <span>{{ $row['user']->email }}</span>
                                        </span>
                                        <span class="agricart-staff-messenger__hint">{{ __('communication.messenger.start') }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @elseif ($peerId === null && trim($staffSearch) !== '')
                        <p class="agricart-staff-messenger__empty agricart-staff-messenger__empty--inline">{{ __('communication.messenger.empty_people') }}</p>
                    @endif
                </div>

                @if ($peerId && $peer)
                    <div class="agricart-staff-messenger__peer-bar">
                        <div class="agricart-staff-messenger__peer">
                            <strong>{{ $peer->name }}</strong>
                            <span>{{ $peerOnline ? __('communication.messenger.online') : __('communication.messenger.offline') }} · {{ $peer->email }}</span>
                        </div>
                        <button type="button" class="agricart-staff-messenger__change" wire:click="clearPeer">
                            {{ __('communication.messenger.change') }}
                        </button>
                    </div>

                    <ol class="agricart-staff-messenger__messages" x-ref="messages" wire:key="dm-list-{{ $peerId }}">
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
                            autofocus
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
                    <div class="agricart-staff-messenger__idle">
                        <p>{{ __('communication.messenger.idle_hint') }}</p>
                    </div>
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
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
            </svg>
            <span class="agricart-staff-messenger__fab-label">{{ __('communication.messenger.fab_label') }}</span>
        </button>
    </div>
@endif
