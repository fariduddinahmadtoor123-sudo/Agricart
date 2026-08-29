@php
    use App\Filament\Clusters\Sales\InboxPage;
    use App\Filament\Layout\TopbarActions;
    use Filament\Support\Icons\Heroicon;

    $user = TopbarActions::user();
    $serverLocale = app()->getLocale();
    $aiState = TopbarActions::aiConnectionState();
    $showInbox = InboxPage::canAccess();
    $inboxUrl = $showInbox ? InboxPage::getUrl() : null;
@endphp

<script>
    (function () {
        const serverLocale = @js($serverLocale);
        const stored = localStorage.getItem('agricart-admin-locale');

        if (stored !== serverLocale) {
            localStorage.setItem('agricart-admin-locale', serverLocale);
        }

        document.documentElement.lang = serverLocale === 'ur' ? 'ur' : 'en';
        document.documentElement.dir = serverLocale === 'ur' ? 'rtl' : 'ltr';
    })();
</script>

<div
    class="agricart-topbar-actions"
    x-on:topbar-notifications-refresh.window="refreshNotifications()"
    x-data="{
        clockDate: '',
        clockTime: '',
        clockIso: '',
        locale: @js($serverLocale),
        searchQuery: '',
        searchOpen: false,
        searchLoading: false,
        searchError: '',
        searchGroups: [],
        searchTimer: null,
        aiConnected: @js($aiState['connected']),
        aiStatus: @js($aiState['status']),
        aiModel: @js($aiState['model'] ?? ''),
        notificationsOpen: false,
        notificationsLoading: false,
        notificationTimer: null,
        notificationSummary: {
            total: 0,
            items: [],
            inbox_unread: 0,
        },
        aiLabels: {
            connected: @js(__('agricart.topbar.ai_connected')),
            disconnected: @js(__('agricart.topbar.ai_disconnected')),
            failed: @js(__('agricart.topbar.ai_failed')),
            paused: @js(__('agricart.topbar.ai_paused')),
        },
        init() {
            this.tick();
            window.setInterval(() => this.tick(), 1000);
            window.addEventListener('ai-settings-saved', () => this.refreshAiStatus());
            document.addEventListener('livewire:navigated', () => this.refreshNotifications());
            this.refreshNotifications();
            this.notificationTimer = window.setInterval(() => this.refreshNotifications(), 30000);
        },
        destroy() {
            if (this.notificationTimer) {
                window.clearInterval(this.notificationTimer);
            }
        },
        async refreshNotifications() {
            if (this.notificationsLoading) {
                return;
            }

            this.notificationsLoading = true;

            try {
                const response = await fetch(@js(route('admin.notifications.summary')), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();

                this.notificationSummary = {
                    total: Number(data.total ?? 0),
                    items: Array.isArray(data.items) ? data.items : [],
                    inbox_unread: Number(data.inbox_unread ?? 0),
                    nav_modules: data.nav_modules ?? {},
                };

                this.applyNavModuleBadges(this.notificationSummary.nav_modules);
            } catch (error) {
                //
            } finally {
                this.notificationsLoading = false;
            }
        },
        toggleNotifications() {
            this.notificationsOpen = ! this.notificationsOpen;

            if (this.notificationsOpen) {
                this.refreshNotifications();
            }
        },
        inboxUnread() {
            return Number(this.notificationSummary.inbox_unread ?? 0);
        },
        applyNavModuleBadges(modules) {
            if (! modules || typeof modules !== 'object') {
                return;
            }

            document.querySelectorAll('.fi-sidebar-item-btn[href]').forEach((link) => {
                const item = link.closest('.fi-sidebar-item');
                const label = link.querySelector('.fi-sidebar-item-label');
                const href = link.getAttribute('href') ?? '';
                let matchedKey = null;

                Object.keys(modules).forEach((key) => {
                    if (href.includes('/admin/' + key)) {
                        matchedKey = key;
                    }
                });

                if (! item || ! label) {
                    return;
                }

                const count = matchedKey ? Number(modules[matchedKey] ?? 0) : 0;

                if (count > 0) {
                    item.classList.add('agricart-nav--has-badge');
                    label.dataset.navBadge = count > 99 ? '99+' : String(count);
                } else {
                    item.classList.remove('agricart-nav--has-badge');
                    delete label.dataset.navBadge;
                }
            });
        },
        async refreshAiStatus() {
            try {
                const response = await fetch(@js(route('agricart.ai.status')), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();

                this.aiConnected = data.connected;
                this.aiStatus = data.status;
                this.aiModel = data.model ?? '';
            } catch (error) {
                //
            }
        },
        aiBtnClass() {
            if (this.aiConnected) {
                return 'is-connected';
            }

            if (this.aiStatus === 'paused') {
                return 'is-disconnected';
            }

            if (this.aiStatus === 'failed') {
                return 'is-failed';
            }

            return 'is-disconnected';
        },
        aiBtnTitle() {
            if (this.aiConnected) {
                return this.aiLabels.connected.replace(':model', this.aiModel || '');
            }

            if (this.aiStatus === 'paused') {
                return this.aiLabels.paused;
            }

            if (this.aiStatus === 'failed') {
                return this.aiLabels.failed;
            }

            return this.aiLabels.disconnected;
        },
        localeTag() {
            return this.locale === 'ur' ? 'ur-PK' : 'en-GB';
        },
        tick() {
            const now = new Date();

            this.clockDate = now.toLocaleDateString(this.localeTag(), {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });

            this.clockTime = now.toLocaleTimeString(this.localeTag(), {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
            });

            this.clockIso = now.toISOString();
        },
        setLocale(code) {
            if (this.locale === code) {
                return;
            }

            const redirect = encodeURIComponent(window.location.pathname + window.location.search);

            window.location.href = `/locale/${code}?scope=admin&redirect=${redirect}`;
        },
        openAi() {
            window.dispatchEvent(new CustomEvent('open-form-panel', {
                detail: {
                    title: @js(__('agricart.topbar.ai_assistant')),
                    resource: @js(__('agricart.topbar.ai_panel_resource')),
                },
            }));
        },
        onSearchInput() {
            if (this.searchTimer) {
                window.clearTimeout(this.searchTimer);
            }

            this.searchTimer = window.setTimeout(this.runSearch.bind(this), 280);
        },
        async runSearch() {
            const query = (this.searchQuery || '').trim();

            if (query.length < 2) {
                this.searchGroups = [];
                this.searchError = '';
                this.searchOpen = query.length > 0;
                return;
            }

            this.searchLoading = true;
            this.searchOpen = true;
            this.searchError = '';

            try {
                const url = @js(route('admin.search.suggest')) + '?q=' + encodeURIComponent(query);
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    this.searchError = @js(__('agricart.topbar.search_error'));
                    this.searchGroups = [];
                    return;
                }

                const data = await response.json();
                this.searchGroups = Array.isArray(data.groups) ? data.groups : [];
            } catch (error) {
                this.searchError = @js(__('agricart.topbar.search_error'));
                this.searchGroups = [];
            } finally {
                this.searchLoading = false;
            }
        },
        searchHasResults() {
            return this.searchGroups.some((group) => Array.isArray(group.items) && group.items.length > 0);
        },
        openSearchHit(item) {
            if (! item) {
                return;
            }

            this.searchOpen = false;

            if (item.panel && item.panel.formKey) {
                window.dispatchEvent(new CustomEvent('open-form-panel', { detail: item.panel }));
                return;
            }

            if (item.url) {
                window.location.href = item.url;
            }
        },
        openFirstResult() {
            const first = this.searchGroups.find((group) => group.items && group.items.length)?.items?.[0];
            this.openSearchHit(first);
        },
    }"
>
    <div
        class="agricart-topbar-actions__search"
        x-on:keydown.escape.window="searchOpen = false"
        x-on:click.outside="searchOpen = false"
    >
        <x-filament::icon
            :icon="Heroicon::OutlinedMagnifyingGlass"
            class="agricart-topbar-actions__search-icon"
        />

        <input
            type="search"
            class="agricart-topbar-actions__search-input"
            placeholder="{{ __('agricart.topbar.search_placeholder') }}"
            title="{{ __('agricart.topbar.search_hint') }}"
            x-model="searchQuery"
            x-on:input="onSearchInput()"
            x-on:focus="if ((searchQuery || '').trim().length >= 2) { searchOpen = true; runSearch(); }"
            x-on:keydown.enter.prevent="openFirstResult()"
            autocomplete="off"
            spellcheck="false"
            maxlength="80"
            aria-label="{{ __('agricart.topbar.search_aria') }}"
            aria-autocomplete="list"
            :aria-expanded="searchOpen"
        />

        <div
            class="agricart-topbar-actions__search-panel"
            x-show="searchOpen"
            x-cloak
            role="listbox"
        >
            <p class="agricart-topbar-actions__search-status" x-show="searchLoading" x-text="@js(__('agricart.topbar.search_loading'))"></p>
            <p class="agricart-topbar-actions__search-status is-error" x-show="searchError && ! searchLoading" x-text="searchError"></p>
            <p
                class="agricart-topbar-actions__search-status"
                x-show="! searchLoading && ! searchError && ! searchHasResults() && (searchQuery || '').trim().length >= 2"
                x-text="@js(__('agricart.topbar.search_no_results')).replace(':query', searchQuery)"
            ></p>
            <template x-for="group in searchGroups" :key="group.key">
                <div class="agricart-topbar-actions__search-group" x-show="group.items?.length">
                    <p class="agricart-topbar-actions__search-group-label" x-text="group.label"></p>
                    <template x-for="(item, index) in group.items" :key="group.key + '-' + index">
                        <a
                            :href="item.url"
                            class="agricart-topbar-actions__search-hit"
                            role="option"
                            x-on:click.prevent="openSearchHit(item)"
                        >
                            <img
                                x-show="item.image"
                                x-cloak
                                :src="item.image"
                                alt=""
                                class="agricart-topbar-actions__search-thumb"
                            />
                            <span>
                                <strong x-text="item.label"></strong>
                                <small x-show="item.meta" x-text="item.meta"></small>
                            </span>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div class="agricart-topbar-actions__cluster">
        <div class="agricart-topbar-actions__clock">
            <time
                class="agricart-topbar-actions__clock-date"
                :datetime="clockIso"
                x-text="clockDate"
            ></time>
            <time
                class="agricart-topbar-actions__clock-time"
                :datetime="clockIso"
                x-text="clockTime"
            ></time>
        </div>

        @if ($showInbox && $inboxUrl)
            <a
                href="{{ $inboxUrl }}"
                wire:navigate
                class="agricart-topbar-actions__icon-btn agricart-topbar-actions__inbox-btn"
                x-bind:class="{ 'is-unread': inboxUnread() > 0 }"
                aria-label="{{ __('agricart.topbar.inbox') }}"
                title="{{ __('agricart.topbar.inbox') }}"
            >
                <x-filament::icon :icon="Heroicon::OutlinedInbox" class="agricart-topbar-actions__icon-btn-icon" />
                <span
                    class="agricart-topbar-actions__notification-badge"
                    x-show="inboxUnread() > 0"
                    x-text="inboxUnread() > 99 ? '99+' : inboxUnread()"
                    x-cloak
                ></span>
            </a>
        @endif

        @if (TopbarActions::showPurchaseButton())
            <a
                href="{{ TopbarActions::purchaseUrl() }}"
                wire:navigate
                class="agricart-topbar-actions__quick-btn agricart-topbar-actions__quick-btn--purchase"
                @if (TopbarActions::userCanQuickOpenPurchase())
                    x-on:click="sessionStorage.setItem('agricart-quick-form-open', 'purchase')"
                @endif
            >
                <x-filament::icon :icon="Heroicon::OutlinedShoppingCart" class="agricart-topbar-actions__quick-icon" />
                <span>{{ __('agricart.topbar.purchase') }}</span>
            </a>
        @endif

        @if (TopbarActions::showPosButton())
            <a
                href="{{ TopbarActions::posUrl() }}"
                wire:navigate
                class="agricart-topbar-actions__quick-btn agricart-topbar-actions__quick-btn--pos"
                @if (TopbarActions::userCanQuickOpenPos())
                    x-on:click="sessionStorage.setItem('agricart-quick-form-open', 'sale')"
                @endif
            >
                <x-filament::icon :icon="Heroicon::OutlinedCreditCard" class="agricart-topbar-actions__quick-icon" />
                <span>{{ __('agricart.topbar.pos') }}</span>
            </a>
        @endif

        <div class="agricart-topbar-actions__lang" role="group" aria-label="{{ __('agricart.topbar.language') }}">
            <button
                type="button"
                class="agricart-topbar-actions__lang-btn"
                :class="{ 'is-active': locale === 'en' }"
                x-on:click="setLocale('en')"
            >
                EN
            </button>
            <button
                type="button"
                class="agricart-topbar-actions__lang-btn"
                :class="{ 'is-active': locale === 'ur' }"
                x-on:click="setLocale('ur')"
            >
                UR
            </button>
        </div>

        <button
            type="button"
            class="agricart-topbar-actions__ai-btn"
            x-bind:class="aiBtnClass()"
            x-on:click="openAi()"
            x-bind:title="aiBtnTitle()"
            x-bind:aria-label="aiBtnTitle()"
        >
            <x-filament::icon :icon="Heroicon::OutlinedSparkles" class="agricart-topbar-actions__ai-icon" />
            <span class="agricart-topbar-actions__ai-signal" x-show="aiConnected" x-cloak aria-hidden="true"></span>
        </button>

        <div
            class="agricart-topbar-actions__notifications"
            x-on:click.outside="notificationsOpen = false"
            x-on:keydown.escape.window="notificationsOpen = false"
        >
            <button
                type="button"
                class="agricart-topbar-actions__icon-btn"
                x-on:click="toggleNotifications()"
                x-bind:aria-expanded="notificationsOpen"
                aria-label="{{ __('agricart.topbar.notifications') }}"
                title="{{ __('agricart.topbar.notifications') }}"
            >
                <x-filament::icon :icon="Heroicon::OutlinedBell" class="agricart-topbar-actions__icon-btn-icon" />
                <span
                    class="agricart-topbar-actions__notification-badge"
                    x-show="notificationSummary.total > 0"
                    x-text="notificationSummary.total > 99 ? '99+' : notificationSummary.total"
                    x-cloak
                ></span>
            </button>

            <div
                class="agricart-topbar-actions__notification-panel"
                x-show="notificationsOpen"
                x-transition.opacity.duration.150ms
                x-cloak
            >
                <div class="agricart-topbar-actions__notification-header">
                    <div>
                        <strong>{{ __('agricart.topbar.online_orders_pending') }}</strong>
                        <span x-text="@js(__('agricart.topbar.online_orders_total')).replace(':count', notificationSummary.total)"></span>
                    </div>
                    <button
                        type="button"
                        class="agricart-topbar-actions__notification-refresh"
                        x-on:click="refreshNotifications()"
                        x-bind:disabled="notificationsLoading"
                        title="{{ __('agricart.topbar.refresh_notifications') }}"
                        aria-label="{{ __('agricart.topbar.refresh_notifications') }}"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedArrowPath" />
                    </button>
                </div>

                <div class="agricart-topbar-actions__notification-list">
                    <template x-for="item in notificationSummary.items" :key="item.key">
                        <a
                            class="agricart-topbar-actions__notification-item"
                            x-bind:href="item.url"
                            x-on:click="notificationsOpen = false"
                        >
                            <span class="agricart-topbar-actions__notification-dot" aria-hidden="true"></span>
                            <span class="agricart-topbar-actions__notification-copy">
                                <strong x-text="item.label"></strong>
                                <small x-text="item.meta"></small>
                            </span>
                            <span class="agricart-topbar-actions__notification-count" x-text="item.count"></span>
                        </a>
                    </template>

                    <div
                        class="agricart-topbar-actions__notification-empty"
                        x-show="! notificationsLoading && notificationSummary.items.length === 0"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedCheckCircle" />
                        <span>{{ __('agricart.topbar.no_online_orders_pending') }}</span>
                    </div>

                    <div
                        class="agricart-topbar-actions__notification-loading"
                        x-show="notificationsLoading && notificationSummary.items.length === 0"
                    >
                        {{ __('agricart.topbar.loading_notifications') }}
                    </div>
                </div>
            </div>
        </div>

        @if ($user && filament()->hasUserMenu() && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Topbar)
            <div class="agricart-topbar-actions__user">
                <x-filament-panels::user-menu />
            </div>
        @endif
    </div>
</div>
