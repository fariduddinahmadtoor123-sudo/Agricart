<x-filament-panels::page>
    <style>
        .agricart-catalog-toolbar--tools-bc{flex-wrap:wrap;gap:.5rem .75rem}
        .agricart-catalog-toolbar--tools-bc .agricart-catalog-toolbar__search{flex:0 1 9.5rem;max-width:10.5rem;min-width:7rem}
        .agricart-catalog-toolbar--tools-bc .agricart-catalog-toolbar__search .fi-input-wrp{width:100%;max-width:10.5rem}
        .agricart-bc-toolbar-slot{flex:0 1 11rem;min-width:8.5rem;max-width:13rem;display:flex;align-items:center}
        .agricart-catalog-toolbar--tools-bc .agricart-catalog-toolbar__actions{flex:1 1 auto;flex-wrap:wrap;justify-content:flex-end;gap:.35rem}
        .agricart-bc__size-select{
            width:100%;border:1px solid #cbd5e1;border-radius:.5rem;padding:.42rem .55rem;
            font-size:.8125rem;font-weight:600;color:#0f172a;background:#fff;direction:ltr;
            font-family:Arial,sans-serif
        }
        .agricart-bc{display:flex;flex-direction:column;gap:1rem}
        .agricart-bc__field{display:flex;flex-direction:column;gap:.22rem;flex:1;min-width:6.5rem}
        .agricart-bc__field--qty{flex:0 0 7rem;min-width:6rem}
        .agricart-bc__field--name{flex:1 1 100%}
        .agricart-bc__field span{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase}
        .agricart-bc__field input,.agricart-bc__field select{
            border:1px solid #cbd5e1;border-radius:.45rem;padding:.5rem .55rem;
            font-size:.875rem;width:100%;background:#fff
        }
        .agricart-bc__field input:disabled,.agricart-bc__field select:disabled{background:#f8fafc;color:#475569}
        .agricart-bc__field input{direction:ltr}
        .agricart-bc__row{display:flex;flex-wrap:wrap;gap:.55rem;align-items:end}
        .agricart-bc__lines{display:flex;flex-direction:column;gap:.45rem}
        .agricart-bc__line-actions{display:flex;flex-wrap:wrap;gap:.4rem}
        .agricart-bc__preview-wrap{display:flex;justify-content:flex-start;padding-top:.25rem}
        .agricart-bc__sticker{
            border:1px solid #cbd5e1;background:#fff;border-radius:.15rem;
            display:flex;align-items:center;justify-content:center;flex-direction:column;
            gap:0;box-sizing:border-box;padding:0.04cm 0.05cm;overflow:hidden
        }
        .agricart-bc__sticker-line{
            font-family:Arial,sans-serif;font-weight:800;text-align:center;color:#0f172a;
            line-height:1;max-width:100%;white-space:nowrap;overflow:hidden;
            flex:0 0 auto;padding:0;margin:0
        }
        .agricart-bc__sticker-empty{font-size:.7rem;font-weight:700;color:#94a3b8;direction:ltr}
        .agricart-bc__btn{
            appearance:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;
            font-size:.8125rem;font-weight:700;padding:.45rem .9rem;border-radius:.55rem;cursor:pointer
        }
        .agricart-bc__btn--ghost{border-style:dashed}
        .agricart-bc__btn--primary{background:#0f172a;border-color:#0f172a;color:#fff}
        .agricart-bc__btn--primary:hover{background:#1e293b}
        .agricart-bc__print-root{display:none}
        .agricart-ro-list{display:flex;flex-direction:column;gap:.75rem}
        .agricart-ro-list__hint{font-size:.8125rem;color:#64748b}
        .agricart-ro-list__table-wrap{overflow:auto;border-radius:.75rem;background:#fff;border:1px solid rgba(15,23,42,.08)}
        .agricart-ro-list__table{width:100%;border-collapse:collapse;font-size:.8125rem}
        .agricart-ro-list__table th{text-align:start;padding:.65rem .85rem;font-size:.6875rem;font-weight:700;text-transform:uppercase;color:#64748b;border-bottom:1px solid rgba(15,23,42,.08);background:rgba(148,163,184,.08)}
        .agricart-ro-list__table td{padding:.7rem .85rem;border-bottom:1px solid rgba(15,23,42,.06);vertical-align:middle}
        .agricart-ro-list__order{font-weight:700}
        .agricart-ro-list__actions{display:flex;gap:.35rem;justify-content:flex-end;flex-wrap:wrap}
        .agricart-ro-list__empty{padding:1.5rem;text-align:center;color:#64748b}
        .agricart-bc__folder-ico{display:inline-flex;align-items:center;gap:.45rem}
        .agricart-bc__folder-ico svg{width:1.15rem;height:1.15rem;color:#0369a1;flex:0 0 auto}
        .agricart-bc__modal{
            position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;
            padding:1rem
        }
        .agricart-bc__modal-backdrop{
            position:absolute;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)
        }
        .agricart-bc__modal-card{
            position:relative;z-index:1;width:min(100%,26rem);max-height:min(92vh,40rem);
            display:flex;flex-direction:column;border-radius:1rem;background:#fff;
            box-shadow:0 20px 50px rgba(15,23,42,.28);overflow:hidden
        }
        .agricart-bc__modal-card--sticker{width:min(100%,30rem)}
        .agricart-bc__modal-head{
            display:flex;align-items:center;justify-content:space-between;gap:.5rem;
            padding:.85rem 1rem;border-bottom:1px solid rgba(15,23,42,.08);flex:0 0 auto
        }
        .agricart-bc__modal-title{margin:0;font-size:.9375rem;font-weight:800;color:#0f172a}
        .agricart-bc__modal-close{
            appearance:none;border:0;background:rgba(148,163,184,.15);color:#475569;
            width:1.75rem;height:1.75rem;border-radius:.5rem;cursor:pointer;font-size:1.1rem;
            font-weight:800;line-height:1
        }
        .agricart-bc__modal-body{
            padding:1rem;display:flex;flex-direction:column;gap:.85rem;overflow:auto;flex:1 1 auto
        }
        .agricart-bc__size-fields{display:flex;flex-wrap:wrap;gap:.55rem}
        .agricart-bc__modal-foot{
            padding:.75rem 1rem;border-top:1px solid rgba(15,23,42,.08);
            display:flex;justify-content:flex-end;gap:.5rem;background:#f8fafc;flex:0 0 auto
        }
        .agricart-bc__saved-block{display:flex;flex-direction:column;gap:.4rem;margin-top:.15rem}
        .agricart-bc__saved-title{margin:0;font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase}
        .agricart-bc__saved-list{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:.35rem}
        .agricart-bc__saved-row{
            display:flex;align-items:center;justify-content:space-between;gap:.5rem;
            padding:.4rem .55rem;border-radius:.55rem;background:rgba(148,163,184,.12);
            border:1px solid rgba(148,163,184,.25);font-size:.8125rem;font-weight:700;direction:ltr
        }
        .agricart-bc__saved-row button{
            appearance:none;border:0;background:#fee2e2;color:#b91c1c;width:1.4rem;height:1.4rem;
            border-radius:999px;cursor:pointer;font-weight:800;line-height:1;flex:0 0 auto
        }
        .agricart-bc__saved-empty{margin:0;font-size:.75rem;color:#94a3b8}
        @media print{
            @page{margin:0}
            html,body{background:#fff!important;margin:0!important;padding:0!important}
            .fi-sidebar,.fi-sidebar-close-overlay,.fi-topbar,.fi-topbar-ctn,.fi-header,.fi-breadcrumbs,.fi-page-header,
            .fi-page-sub-navigation-tabs,.fi-global-search,.fi-notifications,.fi-modal,.agricart-catalog-subnav,
            .agricart-catalog-toolbar,.agricart-topbar-actions,.agricart-ro-list,.agricart-bc__modal,.no-print{display:none!important}
            .fi-main-ctn,.fi-main,.fi-page,.fi-page-main,.fi-page-content,.fi-wi-widget,.fi-section,
            .fi-section-content-ctn,.fi-section-content,.agricart-bc{
                margin:0!important;padding:0!important;border:0!important;box-shadow:none!important;
                background:transparent!important;max-width:none!important;width:100%!important;overflow:visible!important
            }
            .agricart-bc__print-root{display:block!important}
            .agricart-bc__sheet{
                box-sizing:border-box;page-break-after:always;break-after:page;
                display:flex;flex-direction:column;align-items:center;justify-content:center;
                gap:0;padding:0.03cm 0.04cm;overflow:hidden;background:#fff
            }
            .agricart-bc__sheet:last-child{page-break-after:auto;break-after:auto}
            .agricart-bc__sheet-line{
                font-family:Arial,sans-serif;font-weight:800;text-align:center;line-height:1;
                color:#000;max-width:100%;white-space:nowrap;overflow:hidden;margin:0;padding:0
            }
        }
    </style>

    <script>
        window.agricartBcFitBox = function (box) {
            if (! box) return;
            const lineSel = box.classList.contains('agricart-bc__sheet')
                ? '.agricart-bc__sheet-line'
                : '.agricart-bc__sticker-line';
            const lines = Array.from(box.querySelectorAll(lineSel)).filter((el) => {
                return (el.textContent || '').trim() !== '' && (el.textContent || '').trim() !== '\u00a0';
            });
            if (! lines.length) return;

            const padX = 2;
            const padY = 2;
            const gap = lines.length > 1 ? 1 : 0;
            const width = Math.max(1, box.clientWidth - padX * 2);
            const height = Math.max(1, box.clientHeight - padY * 2);
            const slot = Math.max(4, (height - gap * (lines.length - 1)) / lines.length);
            const maxCap = lines.length === 1 ? height * 0.82 : slot * 0.96;

            lines.forEach((el) => {
                el.style.whiteSpace = 'nowrap';
                el.style.overflow = 'hidden';
                el.style.lineHeight = '1';
                el.style.maxWidth = width + 'px';
                el.style.height = 'auto';

                let lo = 4;
                let hi = Math.max(4, maxCap);
                let best = lo;

                for (let i = 0; i < 18; i++) {
                    const mid = (lo + hi) / 2;
                    el.style.fontSize = mid + 'px';
                    const fits = el.scrollWidth <= width + 0.5 && el.scrollHeight <= slot + 0.5;
                    if (fits) {
                        best = mid;
                        lo = mid;
                    } else {
                        hi = mid;
                    }
                }

                el.style.fontSize = best + 'px';
            });
        };

        window.agricartBcFitPreview = function () {
            document.querySelectorAll('.agricart-bc__sticker').forEach((box) => window.agricartBcFitBox(box));
        };

        window.agricartBcClearPrintClones = function () {
            document.querySelectorAll('.agricart-bc__sheet--clone').forEach((node) => node.remove());
        };

        window.agricartBcPreparePrint = function () {
            const root = document.querySelector('.agricart-bc__print-root');
            if (! root) return 0;

            window.agricartBcClearPrintClones();

            // Keep off-screen but measurable until afterprint — do not hide again before print.
            root.style.cssText = 'display:block!important;position:fixed;left:-12000px;top:0;visibility:hidden;pointer-events:none;';

            let total = 0;
            const templates = Array.from(root.querySelectorAll('.agricart-bc__sheet[data-qty]'));

            templates.forEach((sheet) => {
                window.agricartBcFitBox(sheet);
                const qty = Math.max(1, parseInt(sheet.getAttribute('data-qty') || '1', 10) || 1);
                total += qty;
                for (let i = 1; i < qty; i++) {
                    const clone = sheet.cloneNode(true);
                    clone.classList.add('agricart-bc__sheet--clone');
                    clone.removeAttribute('data-qty');
                    root.appendChild(clone);
                }
            });

            return total;
        };

        window.agricartBcFinishPrint = function () {
            window.agricartBcClearPrintClones();
            const root = document.querySelector('.agricart-bc__print-root');
            if (root) {
                root.style.cssText = '';
            }
        };

        if (! window.__agricartBcAfterPrint) {
            window.__agricartBcAfterPrint = true;
            window.addEventListener('afterprint', () => window.agricartBcFinishPrint());
        }
    </script>

    @php
        $viewMode = $this->isViewMode();
        $folders = $this->listedFolders();
    @endphp

    <div
        class="agricart-bc"
        x-data
        x-init="$nextTick(() => { $wire.handleChromeRequest(); window.agricartBcFitPreview(); })"
        x-on:tools-barcode-printing-print.window="
            const total = window.agricartBcPreparePrint();
            if (! total) { return; }
            setTimeout(() => window.print(), 50);
        "
    >
        <div class="agricart-ro-list no-print">
            <p class="agricart-ro-list__hint">{{ __('agricart.pages.tools.bc_list_hint') }}</p>
            <div class="agricart-ro-list__table-wrap">
                @if ($folders->isEmpty())
                    <div class="agricart-ro-list__empty">{{ __('agricart.pages.tools.bc_list_empty') }}</div>
                @else
                    <table class="agricart-ro-list__table">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.tools.bc_list_col_name') }}</th>
                                <th>{{ __('agricart.pages.tools.bc_list_col_qty') }}</th>
                                <th>{{ __('agricart.pages.tools.bc_list_col_prints') }}</th>
                                <th>{{ __('agricart.pages.tools.bc_list_col_updated') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($folders as $folder)
                                @php
                                    $first = is_array($folder->stickers) ? ($folder->stickers[0] ?? []) : [];
                                    $qty = (int) ($first['qty'] ?? 1);
                                @endphp
                                <tr wire:key="bc-folder-{{ $folder->id }}">
                                    <td class="agricart-ro-list__order">
                                        <span class="agricart-bc__folder-ico">
                                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z"/></svg>
                                            {{ $folder->sticker_name }}
                                        </span>
                                    </td>
                                    <td dir="ltr">{{ $qty }}</td>
                                    <td dir="ltr">{{ (int) $folder->print_count }}</td>
                                    <td dir="ltr">{{ optional($folder->updated_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="agricart-ro-list__actions">
                                            <x-filament::button size="sm" color="gray" outlined wire:click="openFolder({{ $folder->id }}, 'view')">
                                                {{ __('agricart.pages.tools.bc_list_view') }}
                                            </x-filament::button>
                                            <x-filament::button size="sm" color="gray" outlined wire:click="openFolder({{ $folder->id }}, 'edit')">
                                                {{ __('agricart.pages.tools.bc_list_edit') }}
                                            </x-filament::button>
                                            <x-filament::button size="sm" color="primary" wire:click="reprintFolder({{ $folder->id }})">
                                                {{ __('agricart.pages.tools.bc_list_reprint') }}
                                            </x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        @if ($showStickerForm && count($stickers))
            @php $sheets = $this->printSheets(); @endphp
            <div class="agricart-bc__print-root" aria-hidden="true">
                @foreach ($sheets as $sheet)
                    <div
                        class="agricart-bc__sheet"
                        data-lines="{{ $sheet['line_count'] }}"
                        data-qty="{{ $sheet['qty'] }}"
                        style="width:{{ $sheet['css_w'] }};height:{{ $sheet['css_h'] }}"
                    >
                        @forelse ($sheet['lines'] as $text)
                            <div class="agricart-bc__sheet-line">{{ $text }}</div>
                        @empty
                            <div class="agricart-bc__sheet-line">&nbsp;</div>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <div
                class="agricart-bc__modal no-print"
                wire:key="bc-sticker-modal-{{ $editingFolderId ?? 0 }}-{{ $formMode }}"
                x-data
                x-on:keydown.escape.window="$wire.closeStickerForm()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="bc-sticker-modal-title"
            >
                <div class="agricart-bc__modal-backdrop" wire:click="closeStickerForm"></div>
                <div class="agricart-bc__modal-card agricart-bc__modal-card--sticker">
                    <div class="agricart-bc__modal-head">
                        <h3 id="bc-sticker-modal-title" class="agricart-bc__modal-title">
                            {{ $viewMode ? __('agricart.pages.tools.bc_list_view') : __('agricart.pages.tools.bc_add_sticker') }}
                        </h3>
                        <button
                            type="button"
                            class="agricart-bc__modal-close"
                            wire:click="closeStickerForm"
                            title="{{ __('agricart.pages.tools.bc_close') }}"
                        >×</button>
                    </div>

                    <div class="agricart-bc__modal-body">
                        <label class="agricart-bc__field agricart-bc__field--name">
                            <span>{{ __('agricart.pages.tools.bc_sticker_name') }}</span>
                            <input
                                type="text"
                                wire:model.blur="stickerName"
                                maxlength="160"
                                @disabled($viewMode)
                                placeholder="{{ __('agricart.pages.tools.bc_sticker_name_placeholder') }}"
                            />
                        </label>

                        @foreach ($stickers as $index => $sticker)
                            @php
                                $filledLines = array_values(array_filter(
                                    array_map(static fn ($line) => trim((string) $line), $sticker['lines'] ?? []),
                                    static fn ($line) => $line !== ''
                                ));
                                $lineCount = max(1, count($filledLines));
                                $lines = $sticker['lines'] ?? [''];
                            @endphp
                            <div wire:key="bc-sticker-card-{{ $index }}-{{ $sticker['size_key'] }}-{{ count($lines) }}">
                                <div class="agricart-bc__row">
                                    <label class="agricart-bc__field">
                                        <span>{{ __('agricart.pages.tools.bc_pick_size') }}</span>
                                        <select
                                            @disabled($viewMode)
                                            x-on:change="$wire.updateStickerSize({{ $index }}, $event.target.value)"
                                        >
                                            @foreach ($savedSizes as $size)
                                                <option
                                                    value="{{ $size['width_cm'] }}x{{ $size['height_cm'] }}"
                                                    @selected(($sticker['size_key'] ?? '') === ($size['width_cm'].'x'.$size['height_cm']))
                                                >{{ $size['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="agricart-bc__field agricart-bc__field--qty">
                                        <span>{{ __('agricart.pages.tools.bc_qty') }}</span>
                                        <input
                                            type="number"
                                            min="1"
                                            max="{{ \App\Filament\Clusters\Tools\BarcodePrintingPage::MAX_QTY }}"
                                            step="1"
                                            inputmode="numeric"
                                            wire:model.live.debounce.200ms="stickers.{{ $index }}.qty"
                                            @disabled($viewMode)
                                        />
                                    </label>
                                </div>

                                <div class="agricart-bc__lines" style="margin-top:.65rem">
                                    @foreach ($lines as $li => $line)
                                        <label class="agricart-bc__field">
                                            <span>{{ __('agricart.pages.tools.bc_line', ['n' => $li + 1]) }}</span>
                                            <input
                                                type="text"
                                                wire:model.live.debounce.300ms="stickers.{{ $index }}.lines.{{ $li }}"
                                                placeholder="{{ __('agricart.pages.tools.bc_line_placeholder') }}"
                                                @disabled($viewMode)
                                            />
                                        </label>
                                    @endforeach
                                    @unless ($viewMode)
                                        <div class="agricart-bc__line-actions">
                                            @if (count($lines) < \App\Filament\Clusters\Tools\BarcodePrintingPage::MAX_LINES)
                                                <button type="button" class="agricart-bc__btn agricart-bc__btn--ghost" wire:click="addStickerLine({{ $index }})">
                                                    {{ __('agricart.pages.tools.bc_add_line') }}
                                                </button>
                                            @endif
                                            @if (count($lines) > 1)
                                                <button type="button" class="agricart-bc__btn" wire:click="removeStickerLine({{ $index }})">
                                                    {{ __('agricart.pages.tools.bc_remove_line') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endunless
                                </div>

                                <div class="agricart-bc__preview-wrap">
                                    <div
                                        class="agricart-bc__sticker"
                                        data-lines="{{ $lineCount }}"
                                        wire:key="bc-preview-{{ $index }}-{{ $sticker['size_key'] }}-{{ md5(implode('|', $filledLines)) }}"
                                        style="width:{{ $sticker['width_cm'] }}cm;height:{{ $sticker['height_cm'] }}cm;max-width:100%"
                                        x-effect="$nextTick(() => window.agricartBcFitBox($el))"
                                    >
                                        @if (count($filledLines))
                                            @foreach ($filledLines as $text)
                                                <div class="agricart-bc__sticker-line">{{ $text }}</div>
                                            @endforeach
                                        @else
                                            <span class="agricart-bc__sticker-empty">{{ $sticker['label'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="agricart-bc__modal-foot">
                        <button type="button" class="agricart-bc__btn" wire:click="closeStickerForm">
                            {{ __('agricart.pages.tools.bc_close') }}
                        </button>
                        @if ($viewMode)
                            <button type="button" class="agricart-bc__btn agricart-bc__btn--primary" wire:click="printStickers">
                                {{ __('agricart.pages.tools.bc_print') }}
                            </button>
                        @else
                            <button type="button" class="agricart-bc__btn agricart-bc__btn--primary" wire:click="saveAndPrint">
                                {{ __('agricart.pages.tools.bc_save_print') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($showSizeForm)
        <div
            class="agricart-bc__modal no-print"
            wire:key="bc-size-modal"
            x-data
            x-on:keydown.escape.window="$wire.closeSizeForm()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="bc-size-modal-title"
        >
            <div class="agricart-bc__modal-backdrop" wire:click="closeSizeForm"></div>
            <div class="agricart-bc__modal-card">
                <div class="agricart-bc__modal-head">
                    <h3 id="bc-size-modal-title" class="agricart-bc__modal-title">
                        {{ __('agricart.pages.tools.bc_add_sticker_size') }}
                    </h3>
                    <button
                        type="button"
                        class="agricart-bc__modal-close"
                        wire:click="closeSizeForm"
                        title="{{ __('agricart.pages.tools.bc_close') }}"
                    >×</button>
                </div>

                <div class="agricart-bc__modal-body">
                    <div class="agricart-bc__size-fields">
                        <label class="agricart-bc__field">
                            <span>{{ __('agricart.pages.tools.bc_width') }}</span>
                            <input
                                type="number"
                                min="0.1"
                                max="100"
                                step="0.1"
                                inputmode="decimal"
                                wire:model="sizeWidthCm"
                                wire:keydown.enter="saveStickerSize"
                                placeholder="e.g. 5"
                                autofocus
                            />
                        </label>
                        <label class="agricart-bc__field">
                            <span>{{ __('agricart.pages.tools.bc_height') }}</span>
                            <input
                                type="number"
                                min="0.1"
                                max="100"
                                step="0.1"
                                inputmode="decimal"
                                wire:model="sizeHeightCm"
                                wire:keydown.enter="saveStickerSize"
                                placeholder="e.g. 3"
                            />
                        </label>
                    </div>

                    <div class="agricart-bc__saved-block">
                        <h4 class="agricart-bc__saved-title">{{ __('agricart.pages.tools.bc_saved_sizes') }}</h4>
                        @if (count($savedSizes))
                            <ul class="agricart-bc__saved-list">
                                @foreach ($savedSizes as $i => $size)
                                    <li class="agricart-bc__saved-row">
                                        <span>{{ $size['label'] }}</span>
                                        <button
                                            type="button"
                                            wire:click="removeSavedSize({{ $i }})"
                                            title="{{ __('agricart.pages.tools.bc_remove_size') }}"
                                        >×</button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="agricart-bc__saved-empty">{{ __('agricart.pages.tools.bc_no_saved_sizes') }}</p>
                        @endif
                    </div>
                </div>

                <div class="agricart-bc__modal-foot">
                    <button type="button" class="agricart-bc__btn" wire:click="closeSizeForm">
                        {{ __('agricart.pages.tools.bc_close') }}
                    </button>
                    <button type="button" class="agricart-bc__btn agricart-bc__btn--primary" wire:click="saveStickerSize">
                        {{ __('agricart.pages.tools.bc_save_size') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
