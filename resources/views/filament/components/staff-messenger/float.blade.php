@auth
    @php
        $messengerUser = auth()->user();
    @endphp
    @if ($messengerUser instanceof \App\Models\User && $messengerUser->canUseAdmin())
        @once
            <script>
                window.agricartStaffMessenger = function (config) {
                    return {
                        open: false,
                        peer: null,
                        staff: [],
                        messages: [],
                        draft: '',
                        query: '',
                        error: '',
                        unread: 0,
                        loading: false,
                        sending: false,
                        labels: config.labels,
                        staffUrl: config.staffUrl,
                        messagesBaseUrl: config.messagesBaseUrl,
                        sendUrl: config.sendUrl,

                        get filteredStaff() {
                            const needle = String(this.query || '').trim().toLowerCase();
                            if (! needle) {
                                return this.staff;
                            }

                            return this.staff.filter((row) => {
                                const hay = `${row.name || ''} ${row.email || ''}`.toLowerCase();
                                return hay.includes(needle);
                            });
                        },

                        get staffMeta() {
                            const total = this.staff.length;
                            const visible = this.filteredStaff.length;
                            const available = String(this.labels.staffAvailable || '').replace(':count', String(total));
                            if (! String(this.query || '').trim()) {
                                return available;
                            }

                            return available + ' · ' + String(this.labels.matches || '').replace(':count', String(visible));
                        },

                        csrf() {
                            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        },

                        async toggle() {
                            if (this.open) {
                                this.close();
                                return;
                            }

                            this.open = true;
                            this.error = '';
                            await this.loadStaff();
                            this.$nextTick(() => this.$refs.search?.focus());
                        },

                        close() {
                            this.open = false;
                            this.error = '';
                            this.clearPeer();
                        },

                        clearPeer() {
                            this.peer = null;
                            this.messages = [];
                            this.draft = '';
                            this.error = '';
                            this.$nextTick(() => this.$refs.search?.focus());
                        },

                        async loadStaff() {
                            this.loading = true;
                            this.error = '';

                            try {
                                const response = await fetch(this.staffUrl, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    throw new Error('staff');
                                }

                                const data = await response.json();
                                this.staff = Array.isArray(data.staff) ? data.staff : [];
                                this.unread = Number(data.unread || 0);
                            } catch (e) {
                                this.error = this.labels.forbidden;
                                this.staff = [];
                            } finally {
                                this.loading = false;
                            }
                        },

                        async selectPeer(row) {
                            // Immediate UI switch — chat opens before the network returns.
                            this.peer = {
                                id: Number(row.id),
                                name: row.name || '',
                                email: row.email || '',
                                online: !! row.online,
                            };
                            this.messages = [];
                            this.draft = '';
                            this.error = '';
                            this.$nextTick(() => this.$refs.compose?.focus());

                            try {
                                const response = await fetch(`${this.messagesBaseUrl}/${this.peer.id}/messages`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    const payload = await response.json().catch(() => ({}));
                                    this.error = payload.message || this.labels.peerError;
                                    return;
                                }

                                const data = await response.json();
                                if (data.peer) {
                                    this.peer = data.peer;
                                }
                                this.messages = Array.isArray(data.messages) ? data.messages : [];
                                this.$nextTick(() => this.scrollMessages());
                            } catch (e) {
                                this.error = this.labels.peerError;
                            }
                        },

                        async send() {
                            const body = String(this.draft || '').trim();
                            if (! this.peer || ! body || this.sending) {
                                return;
                            }

                            this.sending = true;
                            this.error = '';

                            try {
                                const response = await fetch(this.sendUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': this.csrf(),
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        peer_id: this.peer.id,
                                        body,
                                    }),
                                });

                                const data = await response.json().catch(() => ({}));

                                if (! response.ok) {
                                    this.error = data.message || this.labels.bodyError;
                                    return;
                                }

                                if (data.message) {
                                    this.messages.push(data.message);
                                }
                                this.draft = '';
                                this.$nextTick(() => {
                                    this.scrollMessages();
                                    this.$refs.compose?.focus();
                                });
                            } catch (e) {
                                this.error = this.labels.forbidden;
                            } finally {
                                this.sending = false;
                            }
                        },

                        scrollMessages() {
                            const el = this.$refs.messages;
                            if (el) {
                                el.scrollTop = el.scrollHeight;
                            }
                        },
                    };
                };
            </script>
        @endonce

        <div
            class="agricart-staff-messenger"
            x-data="agricartStaffMessenger({
                staffUrl: @js(route('admin.messenger.staff')),
                messagesBaseUrl: @js(url('/admin/messenger/peers')),
                sendUrl: @js(route('admin.messenger.send')),
                labels: {
                    title: @js(__('communication.messenger.title')),
                    lead: @js(__('communication.messenger.lead')),
                    open: @js(__('communication.messenger.open')),
                    close: @js(__('communication.messenger.close')),
                    change: @js(__('communication.messenger.change')),
                    send: @js(__('communication.messenger.send')),
                    search: @js(__('communication.messenger.search')),
                    placeholder: @js(__('communication.messenger.placeholder')),
                    online: @js(__('communication.messenger.online')),
                    offline: @js(__('communication.messenger.offline')),
                    start: @js(__('communication.messenger.start')),
                    staffAvailable: @js(__('communication.messenger.staff_available', ['count' => ':count'])),
                    matches: @js(__('communication.messenger.matches', ['count' => ':count'])),
                    emptyPeople: @js(__('communication.messenger.empty_people')),
                    emptyThread: @js(__('communication.messenger.empty_thread')),
                    fabLabel: @js(__('communication.messenger.fab_label')),
                    peerError: @js(__('communication.messenger.errors.peer')),
                    forbidden: @js(__('communication.messenger.errors.forbidden')),
                    bodyError: @js(__('communication.messenger.errors.body')),
                }
            })"
        >
            <div
                class="agricart-staff-messenger__panel"
                x-show="open"
                x-cloak
                x-bind:style="open ? 'display: flex' : 'display: none'"
                role="dialog"
                aria-modal="true"
                :aria-label="labels.title"
            >
                <header class="agricart-staff-messenger__head">
                    <div class="agricart-staff-messenger__brand">
                        <strong x-text="labels.title"></strong>
                        <span x-text="labels.lead"></span>
                    </div>
                    <button
                        type="button"
                        class="agricart-staff-messenger__close"
                        @click="close()"
                        :title="labels.close"
                        :aria-label="labels.close"
                    >×</button>
                </header>

                <p
                    class="agricart-staff-messenger__error agricart-staff-messenger__error--banner"
                    x-show="error"
                    x-text="error"
                    role="alert"
                ></p>

                {{-- Chat opens instantly on staff click --}}
                <div
                    class="agricart-staff-messenger__chat"
                    x-show="peer"
                    x-bind:style="peer ? 'display: flex' : 'display: none'"
                >
                    <div class="agricart-staff-messenger__peer-bar">
                        <div class="agricart-staff-messenger__peer">
                            <strong x-text="peer?.name || ''"></strong>
                            <span>
                                <span x-text="peer?.online ? labels.online : labels.offline"></span>
                                <span x-show="peer?.email"> · <span x-text="peer?.email"></span></span>
                            </span>
                        </div>
                        <button type="button" class="agricart-staff-messenger__change" @click="clearPeer()">
                            <span x-text="labels.change"></span>
                        </button>
                    </div>

                    <ol class="agricart-staff-messenger__messages" x-ref="messages">
                        <template x-for="message in messages" :key="message.id">
                            <li
                                class="agricart-staff-messenger__bubble"
                                :class="{ 'is-mine': message.mine }"
                            >
                                <p x-text="message.body"></p>
                                <time x-text="message.time || ''"></time>
                            </li>
                        </template>
                        <li class="agricart-staff-messenger__empty" x-show="messages.length === 0" x-text="labels.emptyThread"></li>
                    </ol>

                    <form class="agricart-staff-messenger__compose" @submit.prevent="send()">
                        <input
                            type="text"
                            class="agricart-staff-messenger__input"
                            x-model="draft"
                            :placeholder="labels.placeholder"
                            maxlength="5000"
                            autocomplete="off"
                            x-ref="compose"
                            :disabled="sending"
                        />
                        <button
                            type="submit"
                            class="agricart-staff-messenger__send"
                            :disabled="sending || ! draft.trim()"
                            x-text="labels.send"
                        ></button>
                    </form>
                </div>

                <div
                    class="agricart-staff-messenger__picker"
                    x-show="! peer"
                    x-bind:style="! peer ? 'display: flex' : 'display: none'"
                >
                    <div class="agricart-staff-messenger__search">
                        <label class="sr-only" for="agricart-staff-messenger-search" x-text="labels.search"></label>
                        <input
                            id="agricart-staff-messenger-search"
                            type="search"
                            class="agricart-staff-messenger__input"
                            x-model="query"
                            :placeholder="labels.search"
                            autocomplete="off"
                            x-ref="search"
                        />
                        <p class="agricart-staff-messenger__search-meta" x-text="staffMeta"></p>
                    </div>

                    <div class="agricart-staff-messenger__results">
                        <p
                            class="agricart-staff-messenger__empty agricart-staff-messenger__empty--pad"
                            x-show="filteredStaff.length === 0"
                            x-text="labels.emptyPeople"
                        ></p>
                        <ul class="agricart-staff-messenger__people" role="listbox" x-show="filteredStaff.length > 0">
                            <template x-for="row in filteredStaff" :key="row.id">
                                <li role="option">
                                    <button
                                        type="button"
                                        class="agricart-staff-messenger__person"
                                        @click.prevent="selectPeer(row)"
                                    >
                                        <span
                                            class="agricart-staff-messenger__dot"
                                            :class="{ 'is-online': row.online }"
                                        ></span>
                                        <span class="agricart-staff-messenger__person-text">
                                            <strong x-text="row.name"></strong>
                                            <span x-text="row.email"></span>
                                        </span>
                                        <span class="agricart-staff-messenger__hint" x-text="labels.start"></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="agricart-staff-messenger__fab"
                @click="toggle()"
                :title="labels.open"
                :aria-label="labels.open"
                :aria-expanded="open ? 'true' : 'false'"
            >
                <span class="agricart-staff-messenger__fab-badge" x-show="unread > 0 && ! open" x-text="unread > 9 ? '9+' : unread"></span>
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
                </svg>
                <span class="agricart-staff-messenger__fab-label" x-text="labels.fabLabel"></span>
            </button>
        </div>
    @endif
@endauth
