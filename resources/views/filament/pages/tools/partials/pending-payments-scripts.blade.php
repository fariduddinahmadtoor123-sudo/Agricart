@script
<script>
    Alpine.data('toolsPpDraft', (config = {}) => ({
        key: 'agricart-pp-draft-u' + String(config.userId || 0),
        timer: null,
        onUnload: null,
        init() {
            this.onUnload = () => this.persist(true);
            window.addEventListener('beforeunload', this.onUnload);
            this.timer = setInterval(() => this.persist(true), 15000);
        },
        destroy() {
            if (this.timer) clearInterval(this.timer);
            if (this.onUnload) window.removeEventListener('beforeunload', this.onUnload);
        },
        persist(serverToo = false) {
            try {
                const cells = $wire.cells || {};
                const cellKeys = Object.keys(cells).filter((k) => !String(k).startsWith('_'));
                const empty = !(String($wire.entryShopkeeper || '').trim()) && !(String($wire.entryOwed || '').trim()) && cellKeys.length === 0;
                if (! empty) {
                    localStorage.setItem(this.key, JSON.stringify({
                        formLang: $wire.formLang,
                        sheetNo: $wire.sheetNo,
                        sheetDate: $wire.sheetDate,
                        preparedBy: $wire.preparedBy,
                        cells,
                        pageCount: $wire.pageCount,
                        at: Date.now(),
                    }));
                }
            } catch (e) {}
            if (serverToo) { try { $wire.autoSaveDraft(); } catch (e) {} }
        },
    }));

    Alpine.data('toolsPpEntry', (config = {}) => ({
        listening: false,
        recognition: null,
        lang: config.lang === 'ur' ? 'ur-PK' : 'en-US',
        phraseOpen: false,
        phraseActive: 0,
        phraseBlurTimer: null,
        entryQuery: '',
        setLang(code) {
            this.lang = code === 'ur' ? 'ur-PK' : 'en-US';
            if (this.recognition) this.recognition.lang = this.lang;
        },
        phraseNorm(text) { return String(text || '').toLowerCase().trim(); },
        get entryFiltered() {
            const s = this.phraseNorm(this.entryQuery || $wire.entryShopkeeper || '');
            if (s.length < 2) return [];
            const list = Array.isArray(window.__agricartPpShopkeepers) ? window.__agricartPpShopkeepers : [];
            return list.filter((p) => this.phraseNorm(p).includes(s)).slice(0, 25);
        },
        entryMenuStyle() {
            const el = this.$refs.entry;
            if (! el || ! this.phraseOpen) return { display: 'none' };
            const r = el.getBoundingClientRect();
            return { position: 'fixed', left: Math.max(4, r.left) + 'px', top: (r.bottom + 2) + 'px', width: Math.max(r.width, 160) + 'px', zIndex: 10050 };
        },
        onEntryType(event) {
            clearTimeout(this.phraseBlurTimer);
            this.entryQuery = event?.target?.value ?? ($wire.entryShopkeeper || '');
            this.phraseActive = 0;
            this.phraseOpen = this.phraseNorm(this.entryQuery).length >= 2 && this.entryFiltered.length > 0;
        },
        onEntryBlur() { this.phraseBlurTimer = setTimeout(() => { this.phraseOpen = false; }, 180); },
        pickEntryPhrase(phrase) {
            if (! phrase) return;
            clearTimeout(this.phraseBlurTimer);
            this.phraseOpen = false;
            this.entryQuery = phrase;
            $wire.set('entryShopkeeper', phrase);
        },
        movePhrase(delta) {
            const list = this.entryFiltered;
            if (! list.length) return;
            this.phraseOpen = true;
            this.phraseActive = (this.phraseActive + delta + list.length) % list.length;
        },
        onEntryEnter() {
            if (this.phraseOpen && this.entryFiltered.length) {
                this.pickEntryPhrase(this.entryFiltered[Math.min(this.phraseActive, this.entryFiltered.length - 1)]);
                return;
            }
            const box = (this.$refs.fwd?.value ?? $wire.forwardTo ?? '').trim();
            if (box) this.doForward();
        },
        async doForward() {
            const shop = String(this.$refs.entry?.value ?? $wire.entryShopkeeper ?? '').trim();
            const owed = String(this.$refs.owed?.value ?? $wire.entryOwed ?? '').trim();
            const box = String(this.$refs.fwd?.value ?? $wire.forwardTo ?? '').trim();
            this.phraseOpen = false;
            if (! shop) { alert(@js(__('agricart.pages.tools.pp_forward_need_shopkeeper'))); return; }
            if (! box) { alert(@js(__('agricart.pages.tools.pp_forward_need_box'))); return; }
            await $wire.forwardEntryFromUi(shop, owed, box);
            if (this.$refs.entry) this.$refs.entry.value = '';
            if (this.$refs.owed) this.$refs.owed.value = '';
            if (this.$refs.fwd) this.$refs.fwd.value = '';
            this.entryQuery = '';
            this.$nextTick(() => this.$refs.entry?.focus());
        },
        copyEntry() {
            const text = String(this.$refs.entry?.value ?? $wire.entryShopkeeper ?? '').trim();
            if (! text) return;
            try { navigator.clipboard.writeText(text); } catch (e) {}
        },
        toggleMic() {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (! SR) { alert(@js(__('agricart.pages.tools.pp_entry_mic_unsupported'))); return; }
            if (this.listening && this.recognition) {
                try { this.recognition.stop(); } catch (e) {}
                this.listening = false;
                return;
            }
            const recognition = new SR();
            recognition.lang = this.lang;
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            recognition.onresult = (event) => {
                let text = '';
                try { text = event.results[0][0].transcript || ''; } catch (e) {}
                if (! text) return;
                const cur = String(this.$refs.entry?.value ?? $wire.entryShopkeeper ?? '').trim();
                const next = (cur + ' ' + text).trim();
                if (this.$refs.entry) this.$refs.entry.value = next;
                this.entryQuery = next;
                $wire.set('entryShopkeeper', next);
                this.onEntryType({ target: { value: next } });
            };
            recognition.onend = () => { this.listening = false; this.recognition = null; };
            recognition.onerror = () => { this.listening = false; this.recognition = null; };
            this.recognition = recognition;
            this.listening = true;
            try { recognition.start(); } catch (e) { this.listening = false; this.recognition = null; }
        },
        init() {
            window.addEventListener('tools-pending-payments-entry-cleared', () => {
                if (this.$refs.entry) this.$refs.entry.value = '';
                if (this.$refs.owed) this.$refs.owed.value = '';
                if (this.$refs.fwd) this.$refs.fwd.value = '';
                this.entryQuery = '';
                this.phraseOpen = false;
            });
            window.addEventListener('tools-pending-payments-shopkeepers-updated', () => {
                this.onEntryType({ target: { value: this.$refs.entry?.value || '' } });
            });
        },
    }));
</script>
@endscript
