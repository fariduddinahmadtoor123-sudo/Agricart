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
                        tab: 'people',
                        peer: null,
                        group: null,
                        staff: [],
                        groups: [],
                        messages: [],
                        receipts: {
                            peer_last_read_message_id: null,
                            others: [],
                            others_count: 0,
                        },
                        draft: '',
                        query: '',
                        error: '',
                        unread: 0,
                        loading: false,
                        sending: false,
                        creatingGroup: false,
                        groupTitle: '',
                        selectedMembers: [],
                        pollTimer: null,
                        pollTick: 0,
                        pollHidden: false,
                        labels: config.labels,
                        staffUrl: config.staffUrl,
                        messagesBaseUrl: config.messagesBaseUrl,
                        sendUrl: config.sendUrl,
                        groupsStoreUrl: config.groupsStoreUrl,
                        groupMessagesBaseUrl: config.groupMessagesBaseUrl,
                        groupSendBaseUrl: config.groupSendBaseUrl,

                        init() {
                            this.refreshUnread(false);
                            this.pollTimer = window.setInterval(() => this.tickPoll(), 3000);
                            document.addEventListener('visibilitychange', () => {
                                this.pollHidden = document.hidden;
                                if (! this.pollHidden) {
                                    this.refreshUnread(this.open);
                                }
                            });
                            this.pollHidden = document.hidden;
                        },

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

                        get selectedCount() {
                            return this.selectedMembers.length;
                        },

                        get canCreateGroup() {
                            const title = String(this.groupTitle || '').trim();
                            return title.length > 0 && this.selectedCount >= 2 && this.selectedCount <= 5 && ! this.sending;
                        },

                        csrf() {
                            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        },

                        async tickPoll() {
                            if (this.pollHidden || document.hidden) {
                                return;
                            }

                            this.pollTick += 1;

                            if (this.peer) {
                                await this.pollPeerMessages();
                                return;
                            }

                            if (this.group) {
                                await this.pollGroupMessages();
                                return;
                            }

                            // Picker open: ~9s. Closed FAB: ~24s.
                            const every = this.open ? 3 : 8;
                            if (this.pollTick % every === 0) {
                                await this.refreshUnread(this.open);
                            }
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
                            this.clearChat();
                        },

                        clearChat() {
                            this.peer = null;
                            this.group = null;
                            this.messages = [];
                            this.resetReceipts();
                            this.draft = '';
                            this.error = '';
                            this.creatingGroup = false;
                            this.loadStaff();
                            this.$nextTick(() => this.$refs.search?.focus());
                        },

                        applyStaffPayload(data, replaceList) {
                            this.unread = Number(data.unread || 0);
                            const rows = Array.isArray(data.staff) ? data.staff : [];
                            const groupRows = Array.isArray(data.groups) ? data.groups : [];

                            if (replaceList) {
                                this.staff = rows;
                                this.groups = groupRows;
                                return;
                            }

                            if (rows.length > 0 && this.staff.length > 0) {
                                const map = {};
                                rows.forEach((row) => {
                                    map[Number(row.id)] = Number(row.unread || 0);
                                });
                                this.staff = this.staff.map((row) => ({
                                    ...row,
                                    unread: map[Number(row.id)] ?? Number(row.unread || 0),
                                }));
                            }

                            if (groupRows.length > 0) {
                                const gmap = {};
                                groupRows.forEach((row) => {
                                    gmap[Number(row.id)] = Number(row.unread || 0);
                                });
                                if (this.groups.length > 0) {
                                    this.groups = this.groups.map((row) => ({
                                        ...row,
                                        unread: gmap[Number(row.id)] ?? Number(row.unread || 0),
                                    }));
                                } else {
                                    this.groups = groupRows;
                                }
                            }
                        },

                        async refreshUnread(replaceList = false) {
                            try {
                                const response = await fetch(this.staffUrl, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    return;
                                }

                                const data = await response.json();
                                this.applyStaffPayload(data, replaceList);
                            } catch (e) {
                                // Silent while polling.
                            }
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
                                this.applyStaffPayload(data, true);
                            } catch (e) {
                                this.error = this.labels.forbidden;
                                this.staff = [];
                                this.groups = [];
                            } finally {
                                this.loading = false;
                            }
                        },

                        mergeMessages(incoming) {
                            const rows = Array.isArray(incoming) ? incoming : [];
                            if (rows.length === 0) {
                                return false;
                            }

                            const known = new Set(this.messages.map((m) => Number(m.id)));
                            let added = false;
                            rows.forEach((row) => {
                                const id = Number(row.id);
                                if (! known.has(id)) {
                                    this.messages.push(row);
                                    known.add(id);
                                    added = true;
                                }
                            });

                            if (added) {
                                this.messages.sort((a, b) => Number(a.id) - Number(b.id));
                                this.$nextTick(() => this.scrollMessages());
                            }

                            return added;
                        },

                        applyReceipts(payload) {
                            if (! payload || typeof payload !== 'object') {
                                return;
                            }

                            if (Object.prototype.hasOwnProperty.call(payload, 'peer_last_read_message_id')) {
                                this.receipts.peer_last_read_message_id = payload.peer_last_read_message_id === null
                                    ? null
                                    : Number(payload.peer_last_read_message_id);
                            }

                            if (Array.isArray(payload.others)) {
                                this.receipts.others = payload.others.map((row) => ({
                                    id: Number(row.id),
                                    name: row.name || '',
                                    last_read_message_id: row.last_read_message_id === null || row.last_read_message_id === undefined
                                        ? null
                                        : Number(row.last_read_message_id),
                                }));
                                this.receipts.others_count = Number(payload.others_count || this.receipts.others.length);
                            }
                        },

                        resetReceipts() {
                            this.receipts = {
                                peer_last_read_message_id: null,
                                others: [],
                                others_count: 0,
                            };
                        },

                        messageReceipt(message) {
                            if (! message || ! message.mine) {
                                return { state: 'none', label: '' };
                            }

                            const id = Number(message.id);

                            if (this.peer) {
                                const peerRead = this.receipts.peer_last_read_message_id;
                                if (peerRead !== null && peerRead >= id) {
                                    return { state: 'seen', label: this.labels.seen || 'Seen' };
                                }

                                return { state: 'sent', label: this.labels.sent || 'Sent' };
                            }

                            const others = Array.isArray(this.receipts.others) ? this.receipts.others : [];
                            const total = others.length || Number(this.receipts.others_count || 0);
                            if (total === 0) {
                                return { state: 'sent', label: this.labels.sent || 'Sent' };
                            }

                            const seenBy = others.filter((row) => {
                                return row.last_read_message_id !== null && Number(row.last_read_message_id) >= id;
                            }).length;

                            if (seenBy >= total) {
                                return { state: 'seen', label: this.labels.seen || 'Seen' };
                            }

                            if (seenBy > 0) {
                                const label = String(this.labels.seenBy || 'Seen :count/:total')
                                    .replace(':count', String(seenBy))
                                    .replace(':total', String(total));

                                return { state: 'partial', label };
                            }

                            return { state: 'sent', label: this.labels.sent || 'Sent' };
                        },

                        async selectPeer(row) {
                            this.group = null;
                            this.creatingGroup = false;
                            this.peer = {
                                id: Number(row.id),
                                name: row.name || '',
                                email: row.email || '',
                                online: !! row.online,
                            };
                            this.messages = [];
                            this.resetReceipts();
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
                                this.applyReceipts(data.receipts);
                                if (typeof data.unread !== 'undefined') {
                                    this.unread = Number(data.unread || 0);
                                }
                                this.staff = this.staff.map((item) => (
                                    Number(item.id) === Number(this.peer.id)
                                        ? { ...item, unread: 0 }
                                        : item
                                ));
                                this.$nextTick(() => this.scrollMessages());
                            } catch (e) {
                                this.error = this.labels.peerError;
                            }
                        },

                        async pollPeerMessages() {
                            if (! this.peer?.id) {
                                return;
                            }

                            try {
                                const response = await fetch(`${this.messagesBaseUrl}/${this.peer.id}/messages`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    return;
                                }

                                const data = await response.json();
                                this.mergeMessages(data.messages);
                                this.applyReceipts(data.receipts);
                                if (typeof data.unread !== 'undefined') {
                                    this.unread = Number(data.unread || 0);
                                }
                            } catch (e) {
                                // Keep open chat stable.
                            }
                        },

                        async selectGroup(row) {
                            this.peer = null;
                            this.creatingGroup = false;
                            this.group = {
                                id: Number(row.id),
                                title: row.title || '',
                                members: Array.isArray(row.members) ? row.members : [],
                            };
                            this.messages = [];
                            this.resetReceipts();
                            this.draft = '';
                            this.error = '';
                            this.$nextTick(() => this.$refs.compose?.focus());

                            try {
                                const response = await fetch(`${this.groupMessagesBaseUrl}/${this.group.id}/messages`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    const payload = await response.json().catch(() => ({}));
                                    this.error = payload.message || this.labels.groupError;
                                    return;
                                }

                                const data = await response.json();
                                if (data.group) {
                                    this.group = data.group;
                                }
                                this.messages = Array.isArray(data.messages) ? data.messages : [];
                                this.applyReceipts(data.receipts);
                                if (typeof data.unread !== 'undefined') {
                                    this.unread = Number(data.unread || 0);
                                }
                                this.groups = this.groups.map((item) => (
                                    Number(item.id) === Number(this.group.id)
                                        ? { ...item, unread: 0 }
                                        : item
                                ));
                                this.$nextTick(() => this.scrollMessages());
                            } catch (e) {
                                this.error = this.labels.groupError;
                            }
                        },

                        async pollGroupMessages() {
                            if (! this.group?.id) {
                                return;
                            }

                            try {
                                const response = await fetch(`${this.groupMessagesBaseUrl}/${this.group.id}/messages`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    return;
                                }

                                const data = await response.json();
                                this.mergeMessages(data.messages);
                                this.applyReceipts(data.receipts);
                                if (typeof data.unread !== 'undefined') {
                                    this.unread = Number(data.unread || 0);
                                }
                            } catch (e) {
                                // Keep open chat stable.
                            }
                        },

                        toggleMember(id) {
                            const value = Number(id);
                            if (this.selectedMembers.includes(value)) {
                                this.selectedMembers = this.selectedMembers.filter((item) => item !== value);
                                return;
                            }

                            if (this.selectedMembers.length >= 5) {
                                return;
                            }

                            this.selectedMembers = [...this.selectedMembers, value];
                        },

                        isSelected(id) {
                            return this.selectedMembers.includes(Number(id));
                        },

                        async createGroup() {
                            if (! this.canCreateGroup) {
                                return;
                            }

                            this.sending = true;
                            this.error = '';

                            try {
                                const response = await fetch(this.groupsStoreUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': this.csrf(),
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({
                                        title: String(this.groupTitle || '').trim(),
                                        member_ids: this.selectedMembers,
                                    }),
                                });

                                const data = await response.json().catch(() => ({}));

                                if (! response.ok) {
                                    this.error = data.message || this.labels.groupMembersError;
                                    return;
                                }

                                this.groupTitle = '';
                                this.selectedMembers = [];
                                this.creatingGroup = false;
                                await this.loadStaff();
                                if (data.group) {
                                    await this.selectGroup(data.group);
                                }
                            } catch (e) {
                                this.error = this.labels.forbidden;
                            } finally {
                                this.sending = false;
                            }
                        },

                        async send() {
                            const body = String(this.draft || '').trim();
                            if ((! this.peer && ! this.group) || ! body || this.sending) {
                                return;
                            }

                            this.sending = true;
                            this.error = '';

                            try {
                                const url = this.group
                                    ? `${this.groupSendBaseUrl}/${this.group.id}/send`
                                    : this.sendUrl;
                                const payload = this.group
                                    ? { body }
                                    : { peer_id: this.peer.id, body };

                                const response = await fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': this.csrf(),
                                    },
                                    credentials: 'same-origin',
                                    body: JSON.stringify(payload),
                                });

                                const data = await response.json().catch(() => ({}));

                                if (! response.ok) {
                                    this.error = data.message || this.labels.bodyError;
                                    return;
                                }

                                if (data.message) {
                                    this.messages.push(data.message);
                                }
                                this.applyReceipts(data.receipts);
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
                groupsStoreUrl: @js(route('admin.messenger.groups.store')),
                groupMessagesBaseUrl: @js(url('/admin/messenger/groups')),
                groupSendBaseUrl: @js(url('/admin/messenger/groups')),
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
                    tabPeople: @js(__('communication.messenger.tab_people')),
                    tabGroups: @js(__('communication.messenger.tab_groups')),
                    groupsEmpty: @js(__('communication.messenger.groups_empty')),
                    groupNew: @js(__('communication.messenger.group_new')),
                    groupCreate: @js(__('communication.messenger.group_create')),
                    groupTitle: @js(__('communication.messenger.group_title')),
                    groupTitlePlaceholder: @js(__('communication.messenger.group_title_placeholder')),
                    groupPick: @js(__('communication.messenger.group_pick')),
                    groupMembers: @js(__('communication.messenger.group_members', ['count' => ':count'])),
                    groupOpen: @js(__('communication.messenger.group_open')),
                    groupError: @js(__('communication.messenger.errors.group')),
                    groupMembersError: @js(__('communication.messenger.errors.group_members')),
                    sent: @js(__('communication.messenger.sent')),
                    seen: @js(__('communication.messenger.seen')),
                    seenBy: @js(__('communication.messenger.seen_by')),
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

                <div
                    class="agricart-staff-messenger__chat"
                    x-show="peer || group"
                    x-bind:style="(peer || group) ? 'display: flex' : 'display: none'"
                >
                    <div class="agricart-staff-messenger__peer-bar">
                        <div class="agricart-staff-messenger__peer">
                            <strong x-text="peer ? (peer.name || '') : (group?.title || '')"></strong>
                            <span x-show="peer">
                                <span x-text="peer?.online ? labels.online : labels.offline"></span>
                                <span x-show="peer?.email"> · <span x-text="peer?.email"></span></span>
                            </span>
                            <span x-show="group">
                                <span x-text="String(labels.groupMembers || '').replace(':count', String((group?.members || []).length || group?.member_count || 0))"></span>
                            </span>
                        </div>
                        <button type="button" class="agricart-staff-messenger__change" @click="clearChat()">
                            <span x-text="labels.change"></span>
                        </button>
                    </div>

                    <ol class="agricart-staff-messenger__messages" x-ref="messages">
                        <template x-for="message in messages" :key="message.id">
                            <li
                                class="agricart-staff-messenger__bubble"
                                :class="{ 'is-mine': message.mine }"
                            >
                                <span
                                    class="agricart-staff-messenger__sender"
                                    x-show="! message.mine && message.sender"
                                    x-text="message.sender"
                                ></span>
                                <p x-text="message.body"></p>
                                <div class="agricart-staff-messenger__meta">
                                    <time x-text="message.time || ''"></time>
                                    <span
                                        class="agricart-staff-messenger__receipt"
                                        x-show="message.mine"
                                        :class="{
                                            'is-seen': messageReceipt(message).state === 'seen',
                                            'is-partial': messageReceipt(message).state === 'partial',
                                        }"
                                        :title="messageReceipt(message).label"
                                        x-text="messageReceipt(message).state === 'seen' ? '✓✓' : (messageReceipt(message).state === 'partial' ? '✓✓' : '✓')"
                                    ></span>
                                </div>
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
                    x-show="! peer && ! group"
                    x-bind:style="(! peer && ! group) ? 'display: flex' : 'display: none'"
                >
                    <div class="agricart-staff-messenger__tabs" role="tablist">
                        <button
                            type="button"
                            class="agricart-staff-messenger__tab"
                            :class="{ 'is-active': tab === 'people' }"
                            @click="tab = 'people'; creatingGroup = false"
                            x-text="labels.tabPeople"
                        ></button>
                        <button
                            type="button"
                            class="agricart-staff-messenger__tab"
                            :class="{ 'is-active': tab === 'groups' }"
                            @click="tab = 'groups'"
                            x-text="labels.tabGroups"
                        ></button>
                    </div>

                    <div class="agricart-staff-messenger__search" x-show="tab === 'people' || creatingGroup">
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
                        <p class="agricart-staff-messenger__search-meta" x-show="tab === 'people'" x-text="staffMeta"></p>
                    </div>

                    <div class="agricart-staff-messenger__results" x-show="tab === 'people'">
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
                                        <span
                                            class="agricart-staff-messenger__badge"
                                            x-show="Number(row.unread || 0) > 0"
                                            x-text="Number(row.unread) > 9 ? '9+' : row.unread"
                                        ></span>
                                        <span
                                            class="agricart-staff-messenger__hint"
                                            x-show="! Number(row.unread || 0)"
                                            x-text="labels.start"
                                        ></span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="agricart-staff-messenger__results" x-show="tab === 'groups'">
                        <div class="agricart-staff-messenger__group-toolbar">
                            <button
                                type="button"
                                class="agricart-staff-messenger__group-new"
                                @click="creatingGroup = ! creatingGroup"
                                x-text="labels.groupNew"
                            ></button>
                        </div>

                        <div class="agricart-staff-messenger__group-form" x-show="creatingGroup" x-cloak>
                            <label class="agricart-staff-messenger__field">
                                <span x-text="labels.groupTitle"></span>
                                <input
                                    type="text"
                                    class="agricart-staff-messenger__input"
                                    x-model="groupTitle"
                                    :placeholder="labels.groupTitlePlaceholder"
                                    maxlength="80"
                                />
                            </label>
                            <p class="agricart-staff-messenger__search-meta" x-text="labels.groupPick + ' (' + selectedCount + '/5)'"></p>
                            <ul class="agricart-staff-messenger__people agricart-staff-messenger__people--pick">
                                <template x-for="row in filteredStaff" :key="'pick-' + row.id">
                                    <li>
                                        <button
                                            type="button"
                                            class="agricart-staff-messenger__person"
                                            :class="{ 'is-selected': isSelected(row.id) }"
                                            @click.prevent="toggleMember(row.id)"
                                        >
                                            <span
                                                class="agricart-staff-messenger__dot"
                                                :class="{ 'is-online': row.online }"
                                            ></span>
                                            <span class="agricart-staff-messenger__person-text">
                                                <strong x-text="row.name"></strong>
                                                <span x-text="row.email"></span>
                                            </span>
                                            <span class="agricart-staff-messenger__hint" x-text="isSelected(row.id) ? '✓' : '+'"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <button
                                type="button"
                                class="agricart-staff-messenger__send agricart-staff-messenger__group-create"
                                :disabled="! canCreateGroup"
                                @click="createGroup()"
                                x-text="labels.groupCreate"
                            ></button>
                        </div>

                        <p
                            class="agricart-staff-messenger__empty agricart-staff-messenger__empty--pad"
                            x-show="! creatingGroup && groups.length === 0"
                            x-text="labels.groupsEmpty"
                        ></p>
                        <ul class="agricart-staff-messenger__people" x-show="! creatingGroup && groups.length > 0">
                            <template x-for="row in groups" :key="'g-' + row.id">
                                <li>
                                    <button
                                        type="button"
                                        class="agricart-staff-messenger__person"
                                        @click.prevent="selectGroup(row)"
                                    >
                                        <span class="agricart-staff-messenger__dot is-group"></span>
                                        <span class="agricart-staff-messenger__person-text">
                                            <strong x-text="row.title"></strong>
                                            <span x-text="String(labels.groupMembers || '').replace(':count', String(row.member_count || 0))"></span>
                                        </span>
                                        <span
                                            class="agricart-staff-messenger__badge"
                                            x-show="Number(row.unread || 0) > 0"
                                            x-text="Number(row.unread) > 9 ? '9+' : row.unread"
                                        ></span>
                                        <span
                                            class="agricart-staff-messenger__hint"
                                            x-show="! Number(row.unread || 0)"
                                            x-text="labels.groupOpen"
                                        ></span>
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
                <span
                    class="agricart-staff-messenger__fab-badge"
                    x-show="unread > 0 && ! open"
                    x-cloak
                    x-text="unread > 9 ? '9+' : unread"
                ></span>
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
                </svg>
                <span class="agricart-staff-messenger__fab-label" x-text="labels.fabLabel"></span>
            </button>
        </div>
    @endif
@endauth
