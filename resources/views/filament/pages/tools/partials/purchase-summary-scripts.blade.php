@script
<script>
    Alpine.data('toolsPsDraft', (config = {}) => ({
        key: 'agricart-ps-draft-u' + String(config.userId || 0),
        timer: null,
        onClear: null,
        onTry: null,
        onUnload: null,
        init() {
            this.onClear = () => this.clear();
            this.onTry = () => this.tryRestore();
            this.onUnload = () => this.persist(true);
            window.addEventListener('tools-purchase-summary-clear-local-draft', this.onClear);
            window.addEventListener('tools-purchase-summary-try-local-draft', this.onTry);
            window.addEventListener('beforeunload', this.onUnload);
            this.timer = setInterval(() => this.persist(true), 15000);
        },
        destroy() {
            if (this.timer) clearInterval(this.timer);
            if (this.onClear) window.removeEventListener('tools-purchase-summary-clear-local-draft', this.onClear);
            if (this.onTry) window.removeEventListener('tools-purchase-summary-try-local-draft', this.onTry);
            if (this.onUnload) window.removeEventListener('beforeunload', this.onUnload);
        },
        snapshot() {
            return {
                formLang: $wire.formLang,
                summaryNo: $wire.summaryNo,
                summaryDate: $wire.summaryDate,
                preparedBy: $wire.preparedBy,
                entryText: $wire.entryText,
                forwardTo: $wire.forwardTo,
                cells: $wire.cells,
                paymentSources: $wire.paymentSources,
                pendingBills: $wire.pendingBills,
                clearedPendingBills: $wire.clearedPendingBills,
                pageCount: $wire.pageCount,
                at: Date.now(),
            };
        },
        persist(serverToo = false) {
            try {
                const data = this.snapshot();
                const cells = data.cells || {};
                const cellKeys = Object.keys(cells).filter((k) => !String(k).startsWith('_'));
                const empty = !(data.entryText || '').trim() && cellKeys.length === 0;
                if (! empty) localStorage.setItem(this.key, JSON.stringify(data));
            } catch (e) {}
            if (serverToo) { try { $wire.autoSaveDraft(); } catch (e) {} }
        },
        clear() { try { localStorage.removeItem(this.key); } catch (e) {} },
        tryRestore() {
            try {
                const raw = localStorage.getItem(this.key);
                if (!raw) return;
                const data = JSON.parse(raw);
                if (data && typeof data === 'object') $wire.restoreLocalDraft(data);
            } catch (e) {}
        },
    }));

    Alpine.data('toolsPsEntry', (config = {}) => ({
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
            const s = this.phraseNorm(this.entryQuery || $wire.entryText || '');
            if (s.length < 2) return [];
            const list = Array.isArray(window.__agricartPsShopkeepers) ? window.__agricartPsShopkeepers : [];
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
            this.entryQuery = event?.target?.value ?? ($wire.entryText || '');
            this.phraseActive = 0;
            this.phraseOpen = this.phraseNorm(this.entryQuery).length >= 2 && this.entryFiltered.length > 0;
        },
        onEntryBlur() { this.phraseBlurTimer = setTimeout(() => { this.phraseOpen = false; }, 180); },
        pickEntryPhrase(phrase) {
            if (! phrase) return;
            clearTimeout(this.phraseBlurTimer);
            this.phraseOpen = false;
            this.entryQuery = phrase;
            $wire.set('entryText', phrase);
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
            const text = String(this.$refs.entry?.value ?? $wire.entryText ?? '').trim();
            const box = String(this.$refs.fwd?.value ?? $wire.forwardTo ?? '').trim();
            this.phraseOpen = false;
            if (! text) { alert(@js(__('agricart.pages.tools.ps_forward_need_text'))); return; }
            if (! box) { alert(@js(__('agricart.pages.tools.ps_forward_need_box'))); return; }
            await $wire.forwardEntryFromUi(text, box);
        },
        toggleMic() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (! SpeechRecognition) {
                alert(@js(__('agricart.pages.tools.ps_entry_mic_unsupported')));
                return;
            }
            if (this.listening && this.recognition) {
                try { this.recognition.stop(); } catch (e) {}
                this.listening = false;
                return;
            }
            this.recognition = new SpeechRecognition();
            this.recognition.lang = this.lang;
            this.recognition.interimResults = false;
            this.recognition.continuous = false;
            this.recognition.onresult = (event) => {
                const text = Array.from(event.results).map((r) => r[0].transcript).join(' ').trim();
                if (! text) return;
                const current = ($wire.entryText || '').trim();
                $wire.set('entryText', current ? (current + ' ' + text) : text);
                this.phraseOpen = false;
            };
            this.recognition.onerror = () => { this.listening = false; };
            this.recognition.onend = () => { this.listening = false; };
            this.listening = true;
            try { this.recognition.start(); } catch (e) { this.listening = false; }
        },
        async copyEntry() {
            const text = $wire.entryText || '';
            if (! text) return;
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                const input = this.$refs.entry;
                if (input) { input.select(); document.execCommand('copy'); }
            }
        },
    }));

    Alpine.data('psShopkeeperPick', (config = {}) => ({
        row: config.row,
        q: config.value || '',
        open: false,
        active: 0,
        blurTimer: null,
        placeholder: config.placeholder || '',
        phraseNorm(text) { return String(text || '').toLowerCase().trim(); },
        get filtered() {
            const s = this.phraseNorm(this.q);
            if (s.length < 2) return [];
            const list = Array.isArray(window.__agricartPsShopkeepers) ? window.__agricartPsShopkeepers : [];
            return list.filter((p) => this.phraseNorm(p).includes(s)).slice(0, 25);
        },
        menuStyle() {
            const el = this.$refs.inp;
            if (! el || ! this.open) return { display: 'none' };
            const r = el.getBoundingClientRect();
            return { position: 'fixed', left: Math.max(4, r.left) + 'px', top: (r.bottom + 2) + 'px', width: Math.max(r.width, 120) + 'px', zIndex: 10050 };
        },
        onType(event) {
            clearTimeout(this.blurTimer);
            this.q = event?.target?.value ?? this.q;
            this.active = 0;
            this.open = this.phraseNorm(this.q).length >= 2 && this.filtered.length > 0;
        },
        onFocus() { this.onType({ target: this.$refs.inp }); },
        onBlur() { this.blurTimer = setTimeout(() => { this.open = false; this.commit(); }, 180); },
        closeMenu() { this.open = false; },
        commit() {
            if (! this.row) return;
            $wire.set('cells.' + this.row + '.shopkeeper', String(this.q || '').trim());
        },
        pick(phrase) {
            if (! phrase) return;
            clearTimeout(this.blurTimer);
            this.q = phrase;
            this.open = false;
            $wire.set('cells.' + this.row + '.shopkeeper', phrase);
        },
        pickFirst() {
            const list = this.filtered;
            if (list.length) { this.pick(list[Math.min(Math.max(this.active, 0), list.length - 1)]); return; }
            this.commit();
        },
        move(delta) {
            const list = this.filtered;
            if (! list.length) return;
            this.open = true;
            this.active = (this.active + delta + list.length) % list.length;
        },
    }));
</script>
@endscript
