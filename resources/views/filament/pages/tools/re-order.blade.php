<x-filament-panels::page>
    <style>
        .agricart-ro-sheet-wrap{display:flex;flex-direction:column;align-items:center;gap:14px;padding:.35rem 0 1rem}
        .agricart-ro-sheet{
            width:210mm;max-width:100%;min-height:297mm;
            background:#fff;color:#111;
            border:1px solid rgba(15,23,42,.12);
            box-shadow:0 8px 24px rgba(15,23,42,.08);
            padding:7mm 9mm 8mm;
            font-family:Arial,sans-serif;
            display:flex;flex-direction:column;
            box-sizing:border-box;
            position:relative;
        }
        .agricart-ro-sheet__toolbar{
            display:flex;align-items:center;justify-content:space-between;gap:.5rem;
            margin-bottom:5px;flex-wrap:wrap;
        }
        .agricart-ro-sheet__actions{display:inline-flex;align-items:center;gap:.35rem;flex-wrap:wrap}
        .agricart-ro-btn{
            appearance:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;
            font-size:11px;font-weight:700;line-height:1;padding:5px 9px;border-radius:6px;cursor:pointer;
        }
        .agricart-ro-btn:hover{background:#f8fafc}
        .agricart-ro-btn--primary{background:#0f172a;border-color:#0f172a;color:#fff}
        .agricart-ro-btn--primary:hover{background:#1e293b}
        .agricart-ro-btn--ghost{border-color:transparent;color:#64748b;padding-inline:6px}
        .agricart-ro-btn--ghost:hover{color:#0f172a;background:#f1f5f9}
        .agricart-ro-btn:disabled{opacity:.4;cursor:not-allowed}
        .agricart-ro-lang{display:inline-flex;gap:0;border:1px solid #cbd5e1;border-radius:6px;overflow:hidden}
        .agricart-ro-lang button{
            appearance:none;border:0;background:#fff;color:#334155;
            font-size:11px;font-weight:700;letter-spacing:.02em;
            padding:5px 10px;cursor:pointer;line-height:1;
        }
        .agricart-ro-lang button.is-active{background:#0f172a;color:#fff}
        .agricart-ro-head{text-align:center;padding-bottom:5px;border-bottom:1px solid #e2e8f0}
        .agricart-ro-head__name{margin:0;font-size:15px;font-weight:800;line-height:1.2}
        .agricart-ro-head__addr{margin:2px 0 0;font-size:10.5px;line-height:1.3;color:#334155}
        .agricart-ro-head__phones{margin:2px 0 0;font-size:10.5px;color:#475569;direction:ltr}
        .agricart-ro-meta{
            display:grid;grid-template-columns:1.1fr 1fr 1.3fr 1.1fr;
            gap:4px 8px;margin-top:6px;align-items:end;
        }
        .agricart-ro-meta__field label{
            display:block;font-size:9px;font-weight:700;color:#64748b;
            text-transform:uppercase;letter-spacing:.04em;margin-bottom:1px;
        }
        .agricart-ro-meta__field input,
        .agricart-ro-meta__field select{
            width:100%;border:0;border-bottom:1px solid #94a3b8;border-radius:0;
            background:transparent;font-size:11.5px;line-height:1.3;padding:1px 0 2px;
            color:#0f172a;outline:none;
        }
        .agricart-ro-meta__field .is-static{
            font-size:11.5px;line-height:1.3;padding:1px 0 2px;border-bottom:1px solid #e2e8f0;min-height:1.2em;
        }
        .agricart-ro-entry{
            margin-top:8px;display:flex;align-items:center;gap:4px;
            border:1px solid #cbd5e1;border-radius:8px;padding:3px 4px 3px 6px;background:#f8fafc;
        }
        .agricart-ro-entry__input{
            flex:1;min-width:0;border:0;background:transparent;outline:none;
            font-size:12px;line-height:1.3;padding:4px 2px;color:#0f172a;
        }
        .agricart-ro-entry__fwd{
            width:42px;border:1px solid #cbd5e1;border-radius:5px;background:#fff;
            font-size:11px;text-align:center;padding:4px 2px;outline:none;direction:ltr;
        }
        .agricart-ro-iconbtn{
            appearance:none;border:0;background:transparent;color:#475569;
            width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;
        }
        .agricart-ro-iconbtn:hover{background:#e2e8f0;color:#0f172a}
        .agricart-ro-iconbtn.is-listening{background:#fee2e2;color:#b91c1c}
        .agricart-ro-iconbtn svg{width:15px;height:15px}
        .agricart-ro-sheet__body{flex:1;min-height:40mm;margin-top:8px}
        .agricart-ro-sheet__simple-mark{
            font-size:10px;color:#94a3b8;text-align:center;padding-top:4px;margin-bottom:6px;
        }
        .agricart-ro-foot{margin-top:auto;padding-top:8px}
        .agricart-ro-foot__line{
            border-top:1px solid #111;padding-top:3px;margin-top:10px;
            font-size:11px;font-weight:700;line-height:1.2;color:#111;
        }
        .agricart-ro-foot__line:first-child{margin-top:0}
        @media (max-width:820px){
            .agricart-ro-meta{grid-template-columns:1fr 1fr}
            .agricart-ro-entry{flex-wrap:wrap}
            .agricart-ro-entry__input{flex:1 1 100%}
            .agricart-ro-sheet{min-height:0}
        }
        @media print{
            .agricart-ro-sheet-wrap{padding:0;gap:0}
            .agricart-ro-sheet{
                box-shadow:none;border:0;width:100%;
                min-height:297mm;page-break-after:always;break-after:page;
            }
            .agricart-ro-sheet:last-child{page-break-after:auto;break-after:auto}
            .agricart-ro-sheet__toolbar,.agricart-ro-entry{display:none !important}
        }
    </style>

    @if ($screen === 'form')
        <div
            class="agricart-ro-sheet-wrap"
            x-data="toolsReorderEntry(@js(['lang' => $formLang]))"
            x-on:tools-reorder-lang-changed.window="setLang($event.detail.lang)"
        >
            @for ($page = 1; $page <= $pageCount; $page++)
                <div class="agricart-ro-sheet" wire:key="ro-page-{{ $page }}" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                    @if ($page === 1)
                        <div class="agricart-ro-sheet__toolbar" dir="ltr">
                            <div class="agricart-ro-lang" role="group" aria-label="{{ __('agricart.pages.tools.re_order_form_lang') }}">
                                <button type="button" @class(['is-active' => $formLang === 'en']) wire:click="setFormLang('en')" x-on:click="window.dispatchEvent(new CustomEvent('tools-reorder-lang-changed',{detail:{lang:'en'}}))">EN</button>
                                <button type="button" @class(['is-active' => $formLang === 'ur']) wire:click="setFormLang('ur')" x-on:click="window.dispatchEvent(new CustomEvent('tools-reorder-lang-changed',{detail:{lang:'ur'}}))">UR</button>
                            </div>
                            <div class="agricart-ro-sheet__actions">
                                <button type="button" class="agricart-ro-btn" wire:click="addOrderPage">{{ __('agricart.pages.tools.re_order_form_add_page') }}</button>
                                <button type="button" class="agricart-ro-btn" wire:click="removeOrderPage" @disabled($pageCount <= 1)>{{ __('agricart.pages.tools.re_order_form_remove_page') }}</button>
                                <button type="button" class="agricart-ro-btn" x-on:click="window.print()">{{ __('agricart.pages.tools.re_order_form_print') }}</button>
                                <button type="button" class="agricart-ro-btn agricart-ro-btn--primary" wire:click="saveAndClose">{{ __('agricart.pages.tools.re_order_form_save_close') }}</button>
                                <button type="button" class="agricart-ro-btn agricart-ro-btn--ghost" wire:click="closeOrderForm">{{ __('agricart.pages.tools.re_order_form_close') }}</button>
                            </div>
                        </div>

                        <header class="agricart-ro-head">
                            <h1 class="agricart-ro-head__name">{{ $this->shopName() }}</h1>
                            @if (filled($this->shopAddress()))
                                <p class="agricart-ro-head__addr">{{ $this->shopAddress() }}</p>
                            @endif
                            @if (count($this->shopPhones()) > 0)
                                <p class="agricart-ro-head__phones">{{ implode(' · ', $this->shopPhones()) }}</p>
                            @endif
                        </header>

                        <div class="agricart-ro-meta">
                            <div class="agricart-ro-meta__field">
                                <label>{{ __('agricart.pages.tools.re_order_form_date') }}</label>
                                <input type="text" wire:model.live="orderDate" dir="ltr" />
                            </div>
                            <div class="agricart-ro-meta__field">
                                <label>{{ __('agricart.pages.tools.re_order_form_order_no') }}</label>
                                <input type="text" wire:model.live="orderNo" dir="ltr" />
                            </div>
                            <div class="agricart-ro-meta__field">
                                <label>{{ __('agricart.pages.tools.re_order_form_purchaser') }}</label>
                                <select wire:model.live="purchaserKey">
                                    <option value="">{{ __('agricart.pages.tools.re_order_form_purchaser_select') }}</option>
                                    @foreach ($this->purchaserOptions() as $option)
                                        <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="agricart-ro-meta__field">
                                <label>{{ __('agricart.pages.tools.re_order_form_prepared_by') }}</label>
                                <div class="is-static">{{ $preparedBy }}</div>
                            </div>
                        </div>

                        <div class="agricart-ro-entry" dir="ltr">
                            <input
                                type="text"
                                class="agricart-ro-entry__input"
                                wire:model.live="entryText"
                                x-ref="entry"
                                placeholder="{{ __('agricart.pages.tools.re_order_entry_placeholder') }}"
                                @if ($formLang === 'ur') dir="rtl" @endif
                                x-on:keydown.enter.prevent="if ($wire.forwardTo) { $wire.forwardEntry() }"
                            />
                            <button type="button" class="agricart-ro-iconbtn" :class="{ 'is-listening': listening }" x-on:click="toggleMic()" title="{{ __('agricart.pages.tools.re_order_entry_mic') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                            </button>
                            <button type="button" class="agricart-ro-iconbtn" x-on:click="copyEntry()" title="{{ __('agricart.pages.tools.re_order_entry_copy') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                            <button type="button" class="agricart-ro-iconbtn" wire:click="clearEntry" title="{{ __('agricart.pages.tools.re_order_entry_clear') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                            </button>
                            <input
                                type="text"
                                class="agricart-ro-entry__fwd"
                                wire:model.live="forwardTo"
                                inputmode="numeric"
                                placeholder="#"
                                title="{{ __('agricart.pages.tools.re_order_entry_forward_to') }}"
                            />
                            <button type="button" class="agricart-ro-btn" wire:click="forwardEntry" title="{{ __('agricart.pages.tools.re_order_entry_forward') }}">
                                {{ __('agricart.pages.tools.re_order_entry_forward') }}
                            </button>
                        </div>
                    @else
                        <div class="agricart-ro-sheet__simple-mark" dir="ltr">
                            {{ __('agricart.pages.tools.re_order_form_page_n', ['n' => $page]) }}
                        </div>
                    @endif

                    <div class="agricart-ro-sheet__body">
                        {{-- grid comes here later --}}
                    </div>

                    <footer class="agricart-ro-foot">
                        <div class="agricart-ro-foot__line">
                            {{ $formLang === 'ur' ? __('agricart.pages.tools.re_order_foot_pending_ur') : __('agricart.pages.tools.re_order_foot_pending_en') }}
                        </div>
                        <div class="agricart-ro-foot__line">
                            {{ $formLang === 'ur' ? __('agricart.pages.tools.re_order_foot_returns_ur') : __('agricart.pages.tools.re_order_foot_returns_en') }}
                        </div>
                    </footer>
                </div>
            @endfor
        </div>

        @script
        <script>
            Alpine.data('toolsReorderEntry', (config = {}) => ({
                listening: false,
                recognition: null,
                lang: config.lang === 'ur' ? 'ur-PK' : 'en-US',

                setLang(code) {
                    this.lang = code === 'ur' ? 'ur-PK' : 'en-US';
                    if (this.recognition) {
                        this.recognition.lang = this.lang;
                    }
                },

                toggleMic() {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognition) {
                        alert(@js(__('agricart.pages.tools.re_order_entry_mic_unsupported')));
                        return;
                    }

                    if (this.listening && this.recognition) {
                        this.recognition.stop();
                        this.listening = false;
                        return;
                    }

                    this.recognition = new SpeechRecognition();
                    this.recognition.lang = this.lang;
                    this.recognition.interimResults = false;
                    this.recognition.continuous = false;

                    this.recognition.onresult = (event) => {
                        const text = Array.from(event.results).map((r) => r[0].transcript).join(' ').trim();
                        if (!text) return;
                        const current = ($wire.entryText || '').trim();
                        $wire.set('entryText', current ? (current + ' ' + text) : text);
                    };
                    this.recognition.onerror = () => { this.listening = false; };
                    this.recognition.onend = () => { this.listening = false; };

                    this.listening = true;
                    this.recognition.start();
                },

                async copyEntry() {
                    const text = $wire.entryText || '';
                    if (!text) return;
                    try {
                        await navigator.clipboard.writeText(text);
                    } catch (e) {
                        const input = this.$refs.entry;
                        if (input) {
                            input.select();
                            document.execCommand('copy');
                        }
                    }
                },
            }));
        </script>
        @endscript
    @else
        <div class="rounded-xl bg-white p-6 text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10">
            {{ __('agricart.pages.tools.re_order_list_hint') }}
        </div>
    @endif
</x-filament-panels::page>
