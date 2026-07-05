@php
    use App\Filament\Layout\TopbarActions;
    use Filament\Support\Icons\Heroicon;

    $user = TopbarActions::user();
    $timezone = TopbarActions::timezone();
@endphp

<script>
    (function () {
        const locale = localStorage.getItem('agricart-locale') || 'en';

        document.documentElement.lang = locale === 'ur' ? 'ur' : 'en';
        document.documentElement.dir = locale === 'ur' ? 'rtl' : 'ltr';
    })();
</script>

<div
    class="agricart-topbar-actions"
    x-data="{
        clock: '',
        locale: localStorage.getItem('agricart-locale') || 'en',
        searchQuery: '',
        listening: false,
        voiceSupported: 'SpeechRecognition' in window || 'webkitSpeechRecognition' in window,
        timezone: @js($timezone),
        recognition: null,
        init() {
            this.applyLocale();
            this.tick();
            window.setInterval(() => this.tick(), 1000);
        },
        tick() {
            const now = new Date();

            this.clock = now.toLocaleString('en-GB', {
                timeZone: this.timezone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
            });
        },
        setLocale(code) {
            this.locale = code;
            localStorage.setItem('agricart-locale', code);
            this.applyLocale();
        },
        applyLocale() {
            document.documentElement.lang = this.locale === 'ur' ? 'ur' : 'en';
            document.documentElement.dir = this.locale === 'ur' ? 'rtl' : 'ltr';
        },
        openAi() {
            window.dispatchEvent(new CustomEvent('open-form-panel', {
                detail: {
                    title: 'AI Assistant',
                    resource: 'Ask anything about Agricart',
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
            placeholder="Search anything..."
            x-model="searchQuery"
            aria-label="Global search"
        />

        <div class="agricart-topbar-actions__search-tools">
            <button
                type="button"
                class="agricart-topbar-actions__search-tool"
                :class="{ 'is-active': listening }"
                x-on:click="toggleVoice()"
                :title="voiceSupported ? (listening ? 'Stop voice search' : 'Voice search') : 'Voice search not supported'"
                :disabled="! voiceSupported"
                aria-label="Voice search"
            >
                <x-filament::icon :icon="Heroicon::OutlinedMicrophone" class="agricart-topbar-actions__search-tool-icon" />
            </button>

            <label
                class="agricart-topbar-actions__search-tool"
                title="Search by image"
                aria-label="Upload image for search"
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
        <time
            class="agricart-topbar-actions__clock"
            :datetime="clock"
            x-text="clock"
            title="{{ $timezone }}"
            aria-live="polite"
        ></time>

        <a
            href="{{ TopbarActions::purchaseUrl() }}"
            wire:navigate
            class="agricart-topbar-actions__quick-btn agricart-topbar-actions__quick-btn--purchase"
        >
            <x-filament::icon :icon="Heroicon::OutlinedShoppingCart" class="agricart-topbar-actions__quick-icon" />
            <span>Purchase</span>
        </a>

        <a
            href="{{ TopbarActions::posUrl() }}"
            wire:navigate
            class="agricart-topbar-actions__quick-btn agricart-topbar-actions__quick-btn--pos"
        >
            <x-filament::icon :icon="Heroicon::OutlinedCreditCard" class="agricart-topbar-actions__quick-icon" />
            <span>POS</span>
        </a>

        <div class="agricart-topbar-actions__lang" role="group" aria-label="Language">
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
            x-on:click="openAi()"
            aria-label="AI Assistant"
            title="AI Assistant"
        >
            <x-filament::icon :icon="Heroicon::OutlinedSparkles" class="agricart-topbar-actions__ai-icon" />
        </button>

        @if (filament()->hasDatabaseNotifications() && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Topbar)
            <div class="agricart-topbar-actions__notifications">
                @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                    'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                ])
            </div>
        @else
            <button
                type="button"
                class="agricart-topbar-actions__icon-btn"
                aria-label="Notifications"
                title="Notifications"
            >
                <x-filament::icon :icon="Heroicon::OutlinedBell" class="agricart-topbar-actions__icon-btn-icon" />
            </button>
        @endif

        @if ($user && filament()->hasUserMenu() && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Topbar)
            <div class="agricart-topbar-actions__user">
                <x-filament-panels::user-menu />
            </div>
        @endif
    </div>
</div>
