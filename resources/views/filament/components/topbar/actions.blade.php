@php
    use App\Filament\Layout\TopbarActions;
    use Filament\Support\Icons\Heroicon;

    $user = TopbarActions::user();
    $serverLocale = app()->getLocale();
    $aiState = TopbarActions::aiConnectionState();
@endphp

<script>
    (function () {
        const serverLocale = @js($serverLocale);
        const stored = localStorage.getItem('agricart-locale');

        if (stored !== serverLocale) {
            localStorage.setItem('agricart-locale', serverLocale);
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
        listening: false,
        voiceSupported: 'SpeechRecognition' in window || 'webkitSpeechRecognition' in window,
        recognition: null,
        aiConnected: @js($aiState['connected']),
        aiStatus: @js($aiState['status']),
        aiModel: @js($aiState['model'] ?? ''),
        notificationsOpen: false,
        notificationsLoading: false,
        notificationTimer: null,
        notificationSummary: {
            total: 0,
            items: [],
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
            this.refreshNotifications();
            this.notificationTimer = window.setInterval(() => this.refreshNotifications(), 60000);
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
                };
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

            window.location.href = `/locale/${code}?redirect=${redirect}`;
        },
        openAi() {
            window.dispatchEvent(new CustomEvent('open-form-panel', {
                detail: {
                    title: @js(__('agricart.topbar.ai_assistant')),
                    resource: @js(__('agricart.topbar.ai_panel_resource')),
                },
            }));
        },
        toggleVoice() {
            if (! this.voiceSupported) {
                return;
            }

            if (this.listening && this.recognition) {
                this.recognition.stop();

                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.lang = this.locale === 'ur' ? 'ur-PK' : 'en-US';
            this.recognition.interimResults = false;
            this.recognition.maxAlternatives = 1;

            this.recognition.onstart = () => {
                this.listening = true;
            };

            this.recognition.onend = () => {
                this.listening = false;
            };

            this.recognition.onerror = () => {
                this.listening = false;
            };

            this.recognition.onresult = (event) => {
                const transcript = event.results?.[0]?.[0]?.transcript;

                if (transcript) {
                    this.searchQuery = transcript;
                }
            };

            this.recognition.start();
        },
        onImageSelected(event) {
            const file = event.target.files?.[0];

            if (! file) {
                return;
            }

            this.searchQuery = file.name;
            event.target.value = '';
        },
    }"
>
    <div class="agricart-topbar-actions__search">
        <x-filament::icon
            :icon="Heroicon::OutlinedMagnifyingGlass"
            class="agricart-topbar-actions__search-icon"
        />

        <input
            type="search"
            class="agricart-topbar-actions__search-input"
            placeholder="{{ __('agricart.topbar.search_placeholder') }}"
            x-model="searchQuery"
            aria-label="{{ __('agricart.topbar.search_aria') }}"
        />

        <div class="agricart-topbar-actions__search-tools">
            <button
                type="button"
                class="agricart-topbar-actions__search-tool"
                :class="{ 'is-active': listening }"
                x-on:click="toggleVoice()"
                :title="voiceSupported ? (listening ? @js(__('agricart.topbar.voice_stop')) : @js(__('agricart.topbar.voice_search'))) : @js(__('agricart.topbar.voice_unsupported'))"
                :disabled="! voiceSupported"
                aria-label="{{ __('agricart.topbar.voice_search') }}"
            >
                <x-filament::icon :icon="Heroicon::OutlinedMicrophone" class="agricart-topbar-actions__search-tool-icon" />
            </button>

            <label
                class="agricart-topbar-actions__search-tool"
                title="{{ __('agricart.topbar.image_search') }}"
                aria-label="{{ __('agricart.topbar.image_upload_aria') }}"
            >
                <input
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    x-on:change="onImageSelected($event)"
                />
                <x-filament::icon :icon="Heroicon::OutlinedPhoto" class="agricart-topbar-actions__search-tool-icon" />
            </label>
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
                        <strong>{{ __('agricart.topbar.pending_requests') }}</strong>
                        <span x-text="@js(__('agricart.topbar.pending_total')).replace(':count', notificationSummary.total)"></span>
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
                        <span>{{ __('agricart.topbar.no_pending_requests') }}</span>
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
