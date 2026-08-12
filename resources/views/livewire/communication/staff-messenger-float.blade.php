@if ($authUser)
    <div
        class="agricart-staff-messenger"
        wire:key="agricart-staff-messenger-root"
        @if ($open && $peerId) wire:poll.15s @endif
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
                    <button
                        type="button"
                        class="agricart-staff-messenger__close"
                        wire:click="close"
                        title="{{ __('communication.messenger.close') }}"
                        aria-label="{{ __('communication.messenger.close') }}"
                    >×</button>
                </header>

                @if ($errorMessage)
                    <p class="agricart-staff-messenger__error agricart-staff-messenger__error--banner" role="alert">{{ $errorMessage }}</p>
                @endif

                @if ($peerId)
                    <div class="agricart-staff-messenger__chat" wire:key="messenger-chat-{{ $peerId }}">
                        <div class="agricart-staff-messenger__peer-bar">
                            <div class="agricart-staff-messenger__peer">
                                <strong>{{ $peerName !== '' ? $peerName : __('communication.messenger.title') }}</strong>
                                <span>
                                    {{ $peerOnline ? __('communication.messenger.online') : __('communication.messenger.offline') }}
                                    @if ($peerEmail !== '')
                                        · {{ $peerEmail }}
                                    @endif
                                </span>
                            </div>
                            <button type="button" class="agricart-staff-messenger__change" wire:click="clearPeer">
                                {{ __('communication.messenger.change') }}
                            </button>
                        </div>

                        <ol class="agricart-staff-messenger__messages" data-staff-messenger-messages>
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
                                    <time>{{ agricart_datetime($message->created_at, 'H:i', false) }}</time>
                                </li>
                            @empty
                                <li class="agricart-staff-messenger__empty">{{ __('communication.messenger.empty_thread') }}</li>
                            @endforelse
                        </ol>

                        <form class="agricart-staff-messenger__compose" wire:submit.prevent="send">
                            <input
                                type="text"
                                class="agricart-staff-messenger__input"
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
                                class="agricart-staff-messenger__send"
                                wire:loading.attr="disabled"
                                wire:target="send"
                            >{{ __('communication.messenger.send') }}</button>
                        </form>
                    </div>
                @else
                    <div class="agricart-staff-messenger__picker" wire:key="messenger-picker" data-staff-messenger-picker>
                        <div class="agricart-staff-messenger__search">
                            <label class="sr-only" for="agricart-staff-messenger-search">{{ __('communication.messenger.search') }}</label>
                            <input
                                id="agricart-staff-messenger-search"
                                type="search"
                                class="agricart-staff-messenger__input"
                                placeholder="{{ __('communication.messenger.search') }}"
                                autocomplete="off"
                                autofocus
                                oninput="window.agricartFilterStaffMessenger && window.agricartFilterStaffMessenger(this)"
                            />
                            <p class="agricart-staff-messenger__search-meta" data-staff-messenger-meta>
                                {{ __('communication.messenger.staff_available', ['count' => $staffCount]) }}
                            </p>
                        </div>

                        <div class="agricart-staff-messenger__results">
                            @if ($staffCount === 0)
                                <p class="agricart-staff-messenger__empty agricart-staff-messenger__empty--pad">
                                    {{ __('communication.messenger.empty_people') }}
                                </p>
                            @else
                                <ul class="agricart-staff-messenger__people" role="listbox">
                                    @foreach ($staffDirectory as $row)
                                        <li
                                            wire:key="staff-row-{{ $row['id'] }}"
                                            role="option"
                                            data-staff-row
                                            data-name="{{ \Illuminate\Support\Str::lower($row['name']) }}"
                                            data-email="{{ \Illuminate\Support\Str::lower($row['email']) }}"
                                        >
                                            <button
                                                type="button"
                                                class="agricart-staff-messenger__person"
                                                wire:click.prevent="selectPeer({{ (int) $row['id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="selectPeer"
                                            >
                                                <span @class(['agricart-staff-messenger__dot', 'is-online' => ! empty($row['online'])])></span>
                                                <span class="agricart-staff-messenger__person-text">
                                                    <strong>{{ $row['name'] }}</strong>
                                                    <span>{{ $row['email'] }}</span>
                                                </span>
                                                <span class="agricart-staff-messenger__hint">{{ __('communication.messenger.start') }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="agricart-staff-messenger__empty agricart-staff-messenger__empty--pad" data-staff-messenger-no-match hidden>
                                    {{ __('communication.messenger.empty_people') }}
                                </p>
                            @endif
                        </div>
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

    @once
        <script>
            window.agricartFilterStaffMessenger = function (input) {
                const root = input.closest('[data-staff-messenger-picker]');
                if (!root) return;

                const needle = String(input.value || '').trim().toLowerCase();
                const rows = root.querySelectorAll('[data-staff-row]');
                const empty = root.querySelector('[data-staff-messenger-no-match]');
                const meta = root.querySelector('[data-staff-messenger-meta]');
                let visible = 0;

                rows.forEach((row) => {
                    const hay = (row.getAttribute('data-name') || '') + ' ' + (row.getAttribute('data-email') || '');
                    const show = needle === '' || hay.indexOf(needle) !== -1;
                    row.hidden = !show;
                    if (show) visible++;
                });

                if (empty) {
                    empty.hidden = !(needle !== '' && visible === 0);
                }

                if (meta) {
                    const total = rows.length;
                    const availableTpl = {!! \Illuminate\Support\Js::from(__('communication.messenger.staff_available', ['count' => ':count'])) !!};
                    const matchesTpl = {!! \Illuminate\Support\Js::from(__('communication.messenger.matches', ['count' => ':count'])) !!};
                    meta.textContent = needle === ''
                        ? availableTpl.replace(':count', String(total))
                        : availableTpl.replace(':count', String(total)) + ' · ' + matchesTpl.replace(':count', String(visible));
                }
            };
        </script>
    @endonce
@endif
