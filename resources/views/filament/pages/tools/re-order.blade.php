<x-filament-panels::page>
    <style>
        /* Toolbar (works without Vite rebuild) */
        .agricart-catalog-toolbar--tools-reorder{flex-wrap:nowrap;gap:.5rem .65rem;align-items:center}
        .agricart-catalog-toolbar--tools-reorder .agricart-catalog-toolbar__search{flex:0 1 14rem;max-width:16rem;min-width:9rem}
        .agricart-catalog-toolbar--tools-reorder .agricart-catalog-toolbar__search .fi-input-wrp{width:100%;max-width:16rem}
        .agricart-catalog-toolbar--tools-reorder .agricart-catalog-toolbar__actions{flex:0 0 auto;flex-wrap:wrap;justify-content:flex-end;gap:.35rem}
        .agricart-ro-toolbar-slot{flex:1 1 auto;min-width:0;display:flex;align-items:center}
        .agricart-ro-toolbar-slot .agricart-ro-chrome{
            width:100%;margin:0;padding:0;gap:.4rem;align-items:center;flex-wrap:nowrap;
        }
        .agricart-ro-toolbar-slot .agricart-ro-chrome__purchaser{flex:0 0 9.5rem;max-width:9.5rem;min-width:8rem}
        .agricart-ro-toolbar-slot .agricart-ro-chrome__purchaser label{display:none}
        .agricart-ro-toolbar-slot .agricart-ro-chrome__purchaser select{padding:4px 6px;font-size:11px;height:2.45rem}
        .agricart-ro-toolbar-slot .agricart-ro-entry{
            flex:1 1 auto;min-width:16rem;max-width:none;width:100%;margin-top:0;
            padding:5px 6px;min-height:2.55rem;align-items:center;
        }
        .agricart-ro-toolbar-slot .agricart-ro-entry__input{font-size:13px;padding:6px 4px;line-height:1.35}
        .agricart-ro-toolbar-slot .agricart-ro-entry__fwd{width:38px;padding:5px 2px;font-size:11px;height:2rem}
        .agricart-ro-toolbar-slot .agricart-ro-btn{padding:6px 10px;font-size:11px}
        .agricart-ro-toolbar-slot .agricart-ro-iconbtn{width:28px;height:28px}
        .agricart-ro-draft-flash{
            flex:0 0 auto;font-size:10px;font-weight:600;color:#64748b;white-space:nowrap;padding-inline:4px;
        }
        .agricart-ro-draft-flash.is-saved{color:#15803d}
        .agricart-ro-draft-flash.is-saving{color:#b45309}

        .agricart-ro-sheet-wrap{display:flex;flex-direction:column;align-items:center;gap:14px;padding:.35rem 0 1rem}
        .agricart-ro-sheet{
            width:210mm;max-width:100%;min-height:297mm;
            background:#fff;color:#111;
            border:1px solid rgba(15,23,42,.12);
            box-shadow:0 8px 24px rgba(15,23,42,.08);
            padding:3mm 3.5mm 3mm;
            font-family:Arial,sans-serif;
            display:flex;flex-direction:column;
            box-sizing:border-box;
            position:relative;
        }
        .agricart-ro-btn{
            appearance:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;
            font-size:11px;font-weight:700;line-height:1;padding:5px 9px;border-radius:6px;cursor:pointer;
        }
        .agricart-ro-btn:hover{background:#f8fafc}
        .agricart-ro-chrome{
            display:flex;align-items:center;gap:.5rem;flex-wrap:nowrap;
            box-sizing:border-box;
        }
        .agricart-ro-chrome__purchaser{min-width:0}
        .agricart-ro-chrome__purchaser select{
            width:100%;border:1px solid #cbd5e1;border-radius:6px;background:#fff;
            color:#0f172a;outline:none;
        }
        .agricart-ro-head{text-align:center;padding-bottom:3px;border-bottom:1px solid #e2e8f0}
        .agricart-ro-head__name{margin:0;font-size:14px;font-weight:800;line-height:1.15}
        .agricart-ro-head__addr{margin:1px 0 0;font-size:10px;line-height:1.25;color:#334155}
        .agricart-ro-head__phones{margin:1px 0 0;font-size:10px;color:#475569;direction:ltr}
        .agricart-ro-meta{
            display:flex;flex-wrap:wrap;align-items:center;gap:0;
            margin-top:4px;padding:4px 0 5px;
            border-bottom:1px solid #e2e8f0;
        }
        .agricart-ro-meta__item{
            display:inline-flex;align-items:baseline;gap:4px;
            padding:0 10px;min-width:0;
            border-inline-end:1px solid #e2e8f0;
            line-height:1.25;
        }
        .agricart-ro-meta__item:first-child{padding-inline-start:0}
        .agricart-ro-meta__item:last-child{
            border-inline-end:0;padding-inline-end:0;margin-inline-start:auto;text-align:end;
        }
        .agricart-ro-meta__item--purchaser{flex:1 1 10rem}
        .agricart-ro-meta__k{
            font-size:8.5px;font-weight:700;color:#64748b;
            text-transform:uppercase;letter-spacing:.03em;white-space:nowrap;
        }
        .agricart-ro-meta__v{
            font-size:11.5px;font-weight:600;color:#0f172a;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
        }
        .agricart-ro-meta__item--purchaser .agricart-ro-meta__v{font-weight:700;font-size:12px}
        .agricart-ro-meta__phones{
            display:inline;font-size:10px;font-weight:600;color:#475569;
            direction:ltr;unicode-bidi:isolate;margin-inline-start:4px;
        }
        .agricart-ro-meta__item--prepared .agricart-ro-meta__v{
            font-size:10px;font-weight:600;color:#475569;max-width:9rem;
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
        .agricart-ro-sheet__body{
            flex:1;min-height:0;margin-top:3px;
            display:flex;flex-direction:row;align-items:stretch;gap:0;
        }
        .agricart-ro-sheet__body--cont{margin-top:0;flex:1 1 auto}
        .agricart-ro-grid--cont th,.agricart-ro-grid--cont td,
        .agricart-ro-grid--cont tbody tr{height:8.2mm}
        .agricart-ro-grid--cont thead th{height:5.5mm}
        .agricart-ro-sheet__simple-mark{
            font-size:10px;color:#94a3b8;text-align:center;padding-top:4px;margin-bottom:6px;
        }
        .agricart-ro-strip{
            display:flex;flex-wrap:wrap;align-items:center;gap:2px 0;
            padding:2px 0 3px;margin-bottom:2px;
            border-bottom:1px solid #cbd5e1;
            font-size:9.5px;line-height:1.25;color:#0f172a;
        }
        .agricart-ro-strip__shop{
            font-weight:700;font-size:10px;margin-inline-end:8px;max-width:38%;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
        }
        .agricart-ro-strip__item{
            display:inline-flex;align-items:baseline;gap:3px;
            padding:0 8px;border-inline-end:1px solid #e2e8f0;white-space:nowrap;
        }
        .agricart-ro-strip__item:last-child{border-inline-end:0;padding-inline-end:0}
        .agricart-ro-strip__k{font-size:8px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
        .agricart-ro-strip__v{font-weight:700;font-size:10px}
        .agricart-ro-strip__v--ltr{direction:ltr;unicode-bidi:isolate}
        .agricart-ro-strip__phones{font-weight:600;font-size:9px;color:#475569;direction:ltr;unicode-bidi:isolate;margin-inline-start:3px}
        .agricart-ro-grid-col{flex:1 1 0;min-width:0;display:flex;flex-direction:column;min-height:0}
        .agricart-ro-grid{
            width:100%;border-collapse:collapse;table-layout:fixed;
            font-size:12px;line-height:1.2;color:#111;flex:1 1 auto;height:100%;
        }
        .agricart-ro-grid th,.agricart-ro-grid td{
            border:1px solid #111;padding:1px 2px;vertical-align:middle;
            height:8.4mm;box-sizing:border-box;
        }
        .agricart-ro-grid thead th{
            background:#374151;color:#fff;font-weight:700;text-align:center;
            font-size:11px;letter-spacing:.01em;height:6mm;padding:2px 2px;
        }
        .agricart-ro-grid tbody tr{height:8.4mm}
        .agricart-ro-grid__mark{
            width:5%;padding:0;border:1px solid #111;vertical-align:middle;text-align:center;
            overflow:hidden;
        }
        .agricart-ro-grid__mark--ok{background:#f0fdf4}
        .agricart-ro-grid__mark--next{background:#fef2f2}
        thead .agricart-ro-grid__mark--ok{background:#166534;color:#fff;font-size:9px}
        thead .agricart-ro-grid__mark--next{background:#b91c1c;color:#fff;font-size:9px}
        .agricart-ro-grid--marks .agricart-ro-grid__no{width:7%}
        .agricart-ro-grid--marks .agricart-ro-grid__detail{width:45%}
        .agricart-ro-grid--marks .agricart-ro-grid__unit{width:14%}
        .agricart-ro-grid--marks .agricart-ro-grid__qty{width:10%}
        .agricart-ro-grid--marks .agricart-ro-grid__rate{width:14%}
        .agricart-ro-grid__no{width:8%;text-align:center;font-weight:600;font-variant-numeric:tabular-nums;direction:ltr;unicode-bidi:isolate}
        .agricart-ro-grid__detail{width:52%;text-align:start}
        .agricart-ro-grid__unit{
            width:16%;text-align:center;overflow:hidden;position:relative;
            max-width:0; /* table-layout:fixed — keep content inside cell */
        }
        .agricart-ro-grid__qty{width:11%;text-align:center;overflow:hidden}
        .agricart-ro-grid__rate{width:13%;text-align:center;overflow:hidden}
        .agricart-ro-line-acts{display:flex;align-items:center;justify-content:center}
        .agricart-ro-dot{
            appearance:none;width:13px;height:13px;min-width:13px;padding:0;margin:0;
            border-radius:50%;border:2px solid #94a3b8;background:#fff;cursor:pointer;
            line-height:0;box-sizing:border-box;flex:0 0 auto;
        }
        .agricart-ro-dot:hover{transform:scale(1.12)}
        .agricart-ro-dot--ok{border-color:#16a34a}
        .agricart-ro-dot--ok.is-on{background:#16a34a;border-color:#15803d;box-shadow:0 0 0 2px rgba(22,163,74,.25)}
        .agricart-ro-dot--next{border-color:#dc2626}
        .agricart-ro-dot--next.is-on{background:#dc2626;border-color:#b91c1c;box-shadow:0 0 0 2px rgba(220,38,38,.25)}
        .agricart-ro-dot.is-locked{cursor:default;opacity:.95}
        .agricart-ro-row--done td{background:#f0fdf4}
        .agricart-ro-row--forward td{background:#fef2f2}
        .agricart-ro-fulfill-banner{
            width:210mm;max-width:100%;margin:0 auto 8px;padding:8px 10px;border-radius:8px;
            border:1px solid #fcd34d;background:#fffbeb;color:#78350f;
            font-size:12px;line-height:1.35;box-sizing:border-box;
        }
        .agricart-ro-fulfill-banner.is-ok{border-color:#86efac;background:#f0fdf4;color:#166534}
        .agricart-ro-fulfill-banner--info{border-color:#93c5fd;background:#eff6ff;color:#1e3a8a}
        .agricart-ro-fulfill-legend{display:inline-flex;align-items:center;gap:10px;margin-inline-start:8px;font-size:11px;font-weight:600}
        .agricart-ro-fulfill-legend span{display:inline-flex;align-items:center;gap:4px}
        .agricart-ro-fulfill-legend i{width:10px;height:10px;border-radius:50%;display:inline-block;border:2px solid}
        .agricart-ro-fulfill-legend i.is-g{border-color:#16a34a;background:#16a34a}
        .agricart-ro-fulfill-legend i.is-r{border-color:#dc2626;background:#dc2626}
        .agricart-ro-grid td.agricart-ro-grid__detail{padding-inline:2px}
        .agricart-ro-grid__inp{
            width:100%;max-width:100%;border:0;background:transparent;outline:none;
            font:inherit;font-size:12px;line-height:1.2;color:inherit;
            padding:0;margin:0;box-sizing:border-box;min-height:1.2em;
        }
        .agricart-ro-grid__detail .agricart-ro-grid__inp{text-align:start}
        .agricart-ro-grid__qty .agricart-ro-grid__inp,
        .agricart-ro-grid__rate .agricart-ro-grid__inp{text-align:center}
        .agricart-ro-unitpick{position:relative;width:100%;max-width:100%;min-width:0;overflow:hidden}
        .agricart-ro-unitpick__inp{
            width:100%;max-width:100%;border:0;background:transparent;outline:none;
            font:inherit;font-size:10px;line-height:1.1;color:inherit;
            text-align:center;padding:0;margin:0;min-height:1.1em;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
            box-sizing:border-box;
        }
        .agricart-ro-grid thead th.agricart-ro-grid__unit{font-size:11px}
        .agricart-ro-unitpick__menu{
            position:fixed;z-index:10050;min-width:8rem;max-width:16rem;
            max-height:11rem;overflow:auto;background:#fff;border:1px solid #94a3b8;
            border-radius:4px;box-shadow:0 8px 20px rgba(15,23,42,.18);text-align:start;
        }
        .agricart-ro-unitpick__opt{
            display:block;width:100%;border:0;background:#fff;color:#0f172a;
            font-size:11px;line-height:1.3;padding:5px 7px;cursor:pointer;text-align:inherit;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
        }
        .agricart-ro-unitpick__opt:hover,.agricart-ro-unitpick__opt.is-active{background:#e2e8f0}
        .agricart-ro-phrasepick{position:relative;width:100%;min-width:0;overflow:visible}
        .agricart-ro-phrasepick__menu{
            position:fixed;z-index:10050;min-width:10rem;max-width:22rem;
            max-height:11rem;overflow:auto;background:#fff;border:1px solid #94a3b8;
            border-radius:4px;box-shadow:0 8px 20px rgba(15,23,42,.18);text-align:start;
        }
        .agricart-ro-phrasepick__opt{
            display:block;width:100%;border:0;background:#fff;color:#0f172a;
            font-size:11px;line-height:1.3;padding:5px 7px;cursor:pointer;text-align:inherit;
            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
        }
        .agricart-ro-phrasepick__opt:hover,.agricart-ro-phrasepick__opt.is-active{background:#e2e8f0}
        .agricart-ro-entry{position:relative}
        .agricart-ro-entry .agricart-ro-phrasepick{flex:1 1 auto;min-width:16rem;display:flex}
        .agricart-ro-foot{margin-top:2px;padding-top:2px;flex:0 0 auto;display:flex;flex-direction:column;gap:2px}
        .agricart-ro-totals{
            display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px 14px;
            margin-top:3px;padding:4px 6px;border:1px solid #111;background:#f8fafc;
            font-size:11px;line-height:1.3;color:#0f172a;
        }
        .agricart-ro-totals__k{font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.03em;font-size:9px}
        .agricart-ro-totals__v{font-weight:800;font-size:13px;direction:ltr;unicode-bidi:isolate}
        .agricart-ro-totals__meta{font-weight:600;color:#64748b;font-size:10px}
        .agricart-ro-totals--cont{margin-top:4px}
        .agricart-ro-foot__write{
            width:100%;border-collapse:collapse;table-layout:fixed;
            font-size:11px;line-height:1.3;color:#111;
        }
        .agricart-ro-foot__write th,.agricart-ro-foot__write td{
            border:1px solid #111;box-sizing:border-box;vertical-align:middle;
        }
        .agricart-ro-foot__write th{
            width:22%;max-width:38mm;
            background:#f3f4f6;font-weight:700;text-align:center;
            padding:5px 6px;white-space:nowrap;font-size:11px;
        }
        .agricart-ro-foot__write td{
            height:8mm;padding:2px 4px;background:#fff;
        }
        .agricart-ro-foot__inp{
            width:100%;border:0;background:transparent;outline:none;
            font:inherit;font-size:11px;line-height:1.3;color:#111;
            padding:2px 0;margin:0;min-height:1.4em;box-sizing:border-box;
        }
        @media (max-width:820px){
            .agricart-ro-meta{gap:4px 0}
            .agricart-ro-meta__item{border-inline-end:0;padding:0 8px 0 0}
            .agricart-ro-meta__item:last-child{margin-inline-start:0;text-align:start}
            .agricart-ro-entry{flex-wrap:wrap}
            .agricart-ro-entry__input{flex:1 1 100%}
            .agricart-ro-sheet{min-height:0}
        }
        @media print{
            @page{size:A4;margin:4mm}
            html,body{background:#fff !important}
            .fi-sidebar,
            .fi-sidebar-close-overlay,
            .fi-topbar,
            .fi-topbar-ctn,
            .fi-header,
            .fi-breadcrumbs,
            .fi-page-header,
            .fi-page-sub-navigation-tabs,
            .fi-page-sub-navigation-sidebar-ctn,
            .fi-page-sub-navigation-dropdown,
            .fi-global-search,
            .fi-notifications,
            .fi-modal,
            .agricart-catalog-subnav,
            .agricart-catalog-toolbar,
            .agricart-ro-toolbar-slot,
            .agricart-ro-chrome,
            .agricart-ro-entry,
            .agricart-form-panel,
            .agricart-topbar-actions,
            .no-print{
                display:none !important;
            }
            .fi-main-ctn,
            .fi-main,
            .fi-page,
            .fi-page-main,
            .fi-page-content,
            .fi-wi-widget,
            .fi-section,
            .fi-section-content-ctn,
            .fi-section-content{
                margin:0 !important;
                padding:0 !important;
                border:0 !important;
                box-shadow:none !important;
                background:transparent !important;
                max-width:none !important;
                width:100% !important;
            }
            .agricart-ro-sheet-wrap{
                padding:0 !important;gap:0 !important;align-items:stretch !important;
                display:block !important;
            }
            .agricart-ro-sheet{
                box-shadow:none !important;border:0 !important;width:100% !important;
                max-width:none !important;min-height:285mm !important;
                height:285mm !important;padding:2mm !important;
                page-break-after:always;break-after:page;
                print-color-adjust:exact;-webkit-print-color-adjust:exact;
            }
            .agricart-ro-sheet:last-child{page-break-after:auto;break-after:auto}
            .agricart-ro-sheet__body{flex:1 1 auto !important;min-height:0 !important}
            .agricart-ro-grid th,.agricart-ro-grid td,
            .agricart-ro-grid tbody tr{height:8.4mm !important}
            .agricart-ro-grid thead th{height:6mm !important}
            .agricart-ro-unitpick__menu,
            .agricart-ro-phrasepick__menu{display:none !important}
            .agricart-ro-grid thead th{
                background:#374151 !important;color:#fff !important;
                print-color-adjust:exact;-webkit-print-color-adjust:exact;
            }
            .agricart-ro-grid__inp,.agricart-ro-unitpick__inp,.agricart-ro-foot__inp{
                border:0 !important;background:transparent !important;-webkit-appearance:none;appearance:none;
                color:#111 !important;box-shadow:none !important;
            }
            /* Pencil boxes stay on print; colored dots/buttons stay screen-only */
            .agricart-ro-fulfill-banner,
            .agricart-ro-line-acts,
            .agricart-ro-dot{
                display:none !important;
            }
            .agricart-ro-grid__mark{
                display:table-cell !important;
                width:5% !important;
                border:1px solid #111 !important;
                background:#fff !important;
                color:#111 !important;
                padding:0 !important;
                overflow:hidden !important;
            }
            .agricart-ro-grid thead th.agricart-ro-grid__mark{
                background:#374151 !important;color:#fff !important;
                font-size:8px !important;
            }
            .agricart-ro-row--done td,
            .agricart-ro-row--forward td{background:#fff !important}
            .agricart-ro-grid__unit,
            .agricart-ro-unitpick{
                overflow:hidden !important;max-width:100% !important;position:static !important;
            }
            .agricart-ro-unitpick__inp{
                font-size:9px !important;max-width:100% !important;
                overflow:hidden !important;text-overflow:ellipsis !important;
                white-space:nowrap !important;background:transparent !important;
            }
            .agricart-ro-grid--marks .agricart-ro-grid__no{width:7% !important}
            .agricart-ro-grid--marks .agricart-ro-grid__detail{width:45% !important}
            .agricart-ro-grid--marks .agricart-ro-grid__unit{width:14% !important}
            .agricart-ro-grid--marks .agricart-ro-grid__qty{width:10% !important}
            .agricart-ro-grid--marks .agricart-ro-grid__rate{width:14% !important}
        }
    </style>

    @if ($screen === 'form')
        @php
            $allUnitOptions = $this->unitOptions();
            $canMarkLines = $this->canMarkLineStatus();
            // Always keep ✓ / → pencil columns (print + screen); interactive dots only when saved.
            $showMarkCols = true;
            $unresolvedLines = $canMarkLines ? $this->unresolvedLineCount() : 0;
        @endphp
        @if ($canMarkLines)
            <div @class(['agricart-ro-fulfill-banner', 'no-print', 'is-ok' => $unresolvedLines === 0])>
                @if ($unresolvedLines > 0)
                    {{ __('agricart.pages.tools.re_order_line_status_hint') }}
                    — {{ __('agricart.pages.tools.re_order_line_unresolved', ['count' => $unresolvedLines]) }}
                @else
                    {{ __('agricart.pages.tools.re_order_line_all_marked') }}
                @endif
                <span class="agricart-ro-fulfill-legend">
                    <span><i class="is-g"></i> {{ __('agricart.pages.tools.re_order_line_done') }}</span>
                    <span><i class="is-r"></i> {{ __('agricart.pages.tools.re_order_line_forward') }}</span>
                </span>
            </div>
        @elseif ($persistStatus === 'draft' && ! $this->isViewMode())
            <div class="agricart-ro-fulfill-banner agricart-ro-fulfill-banner--info no-print">
                {{ __('agricart.pages.tools.re_order_fulfill_draft_hint') }}
            </div>
        @endif
        <div
            @class(['agricart-ro-sheet-wrap', 'agricart-ro-fulfill' => $canMarkLines])
            data-ro-units='@json($allUnitOptions)'
            data-ro-phrases='@json($this->phraseOptions())'
            @if (! $this->isViewMode())
                x-data="toolsReorderDraft(@js(['userId' => (int) auth()->id()]))"
            @else
                x-data
            @endif
            x-init="
                window.__agricartRoUnits = JSON.parse($el.getAttribute('data-ro-units') || '[]');
                window.__agricartRoPhrases = JSON.parse($el.getAttribute('data-ro-phrases') || '[]');
                window.dispatchEvent(new CustomEvent('tools-reorder-screen',{detail:{screen:'form',formMode:@js($this->isViewMode() ? 'view' : 'edit')}}));
            "
            wire:key="ro-form-{{ $unitsTick }}-{{ $formMode }}-{{ $persistStatus }}-{{ $editingOrderId ?? 0 }}"
        >
            @if (! $this->isViewMode())
            @teleport('#agricart-ro-toolbar-slot')
                <div class="agricart-ro-chrome print:hidden" dir="ltr" x-data="toolsReorderEntry(@js(['lang' => $formLang]))" x-on:tools-reorder-lang-changed.window="setLang($event.detail.lang)">
                    <div class="agricart-ro-chrome__purchaser">
                        <label class="fi-sr-only" for="agricart-ro-purchaser">{{ __('agricart.pages.tools.re_order_form_purchaser') }}</label>
                        <select id="agricart-ro-purchaser" wire:model.live="purchaserKey" title="{{ __('agricart.pages.tools.re_order_form_purchaser') }}">
                            <option value="">{{ __('agricart.pages.tools.re_order_form_purchaser_select') }}</option>
                            @foreach ($this->purchaserOptions() as $option)
                                <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="agricart-ro-entry" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                        <div class="agricart-ro-phrasepick agricart-ro-phrasepick--entry">
                            <input
                                type="text"
                                class="agricart-ro-entry__input"
                                wire:model.live="entryText"
                                x-ref="entry"
                                placeholder="{{ __('agricart.pages.tools.re_order_entry_placeholder') }}"
                                autocomplete="off"
                                @if ($formLang === 'ur') dir="rtl" @endif
                                x-on:input="onEntryType($event)"
                                x-on:focus="onEntryType($event)"
                                x-on:blur="onEntryBlur()"
                                x-on:keydown.escape.prevent="phraseOpen = false"
                                x-on:keydown.enter.prevent="onEntryEnter()"
                                x-on:keydown.arrow-down.prevent="movePhrase(1)"
                                x-on:keydown.arrow-up.prevent="movePhrase(-1)"
                            />
                            <template x-teleport="body">
                                <div
                                    class="agricart-ro-phrasepick__menu"
                                    x-show="phraseOpen"
                                    x-cloak
                                    x-bind:style="entryMenuStyle()"
                                >
                                    <template x-for="(p, i) in entryFiltered" :key="'ep-'+i+'-'+p">
                                        <button
                                            type="button"
                                            class="agricart-ro-phrasepick__opt"
                                            :class="{ 'is-active': i === phraseActive }"
                                            x-on:mousedown.prevent="pickEntryPhrase(p)"
                                            x-text="p"
                                        ></button>
                                    </template>
                                </div>
                            </template>
                        </div>
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
                            x-ref="fwd"
                            wire:model.live="forwardTo"
                            inputmode="numeric"
                            placeholder="#"
                            title="{{ __('agricart.pages.tools.re_order_entry_forward_to') }}"
                            dir="ltr"
                            x-on:keydown.enter.prevent="doForward()"
                        />
                        <button type="button" class="agricart-ro-btn" x-on:click.prevent="doForward()" title="{{ __('agricart.pages.tools.re_order_entry_forward') }}">
                            {{ __('agricart.pages.tools.re_order_entry_forward') }}
                        </button>
                    </div>
                </div>
            @endteleport
            @endif

            @php $amountSummary = $this->orderAmountSummary(); @endphp
            @for ($page = 1; $page <= $pageCount; $page++)
                <div class="agricart-ro-sheet" wire:key="ro-page-{{ $page }}" @if ($formLang === 'ur') dir="rtl" @else dir="ltr" @endif>
                    @php
                        $purchaserName = $this->purchaserLabel();
                        $purchaserPhones = $this->purchaserPhones();
                    @endphp
                    @if ($page === 1)
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
                            <div class="agricart-ro-meta__item">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.re_order_form_date') }}</span>
                                <span class="agricart-ro-meta__v" dir="ltr">{{ $orderDate }}</span>
                            </div>
                            <div class="agricart-ro-meta__item">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.re_order_form_order_no') }}</span>
                                <span class="agricart-ro-meta__v" dir="ltr">{{ $orderNo }}</span>
                            </div>
                            <div class="agricart-ro-meta__item">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.re_order_form_page') }}</span>
                                <span class="agricart-ro-meta__v" dir="ltr">{{ $page }} / {{ $pageCount }}</span>
                            </div>
                            <div class="agricart-ro-meta__item agricart-ro-meta__item--purchaser">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.re_order_form_purchaser') }}</span>
                                <span class="agricart-ro-meta__v">
                                    {{ $purchaserName !== '' ? $purchaserName : '—' }}
                                    @if ($purchaserPhones !== [])
                                        <span class="agricart-ro-meta__phones">{{ implode(' · ', $purchaserPhones) }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="agricart-ro-meta__item agricart-ro-meta__item--prepared">
                                <span class="agricart-ro-meta__k">{{ __('agricart.pages.tools.re_order_form_prepared_by') }}</span>
                                <span class="agricart-ro-meta__v" title="{{ $preparedBy }}">{{ $preparedBy }}</span>
                            </div>
                        </div>
                    @else
                        <div class="agricart-ro-strip">
                            @if (filled($this->shopName()))
                                <span class="agricart-ro-strip__shop">{{ $this->shopName() }}</span>
                            @endif
                            <span class="agricart-ro-strip__item">
                                <span class="agricart-ro-strip__k">{{ __('agricart.pages.tools.re_order_form_order_no') }}</span>
                                <span class="agricart-ro-strip__v agricart-ro-strip__v--ltr">{{ $orderNo }}</span>
                            </span>
                            <span class="agricart-ro-strip__item">
                                <span class="agricart-ro-strip__k">{{ __('agricart.pages.tools.re_order_form_page') }}</span>
                                <span class="agricart-ro-strip__v agricart-ro-strip__v--ltr">{{ $page }} / {{ $pageCount }}</span>
                            </span>
                            <span class="agricart-ro-strip__item">
                                <span class="agricart-ro-strip__k">{{ __('agricart.pages.tools.re_order_form_purchaser') }}</span>
                                <span class="agricart-ro-strip__v">
                                    {{ $purchaserName !== '' ? $purchaserName : '—' }}
                                    @if ($purchaserPhones !== [])
                                        <span class="agricart-ro-strip__phones">{{ implode(' · ', $purchaserPhones) }}</span>
                                    @endif
                                </span>
                            </span>
                        </div>
                    @endif

                    @php
                        $rowsPerCol = $this->rowsPerColForPage($page);
                        $gridCols = \App\Filament\Clusters\Tools\ReOrderPage::GRID_COLS;
                        $pageBase = $this->pageBaseForPage($page);
                        $unitOptions = $allUnitOptions;
                        $hNo = $formLang === 'ur' ? __('agricart.pages.tools.re_order_grid_no_ur') : __('agricart.pages.tools.re_order_grid_no_en');
                        $hDetail = $formLang === 'ur' ? __('agricart.pages.tools.re_order_grid_detail_ur') : __('agricart.pages.tools.re_order_grid_detail_en');
                        $hUnit = $formLang === 'ur' ? __('agricart.pages.tools.re_order_grid_unit_ur') : __('agricart.pages.tools.re_order_grid_unit_en');
                        $hQty = $formLang === 'ur' ? __('agricart.pages.tools.re_order_grid_qty_ur') : __('agricart.pages.tools.re_order_grid_qty_en');
                        $hRate = $formLang === 'ur' ? __('agricart.pages.tools.re_order_grid_rate_ur') : __('agricart.pages.tools.re_order_grid_rate_en');
                        $pendingLabel = $formLang === 'ur' ? __('agricart.pages.tools.re_order_foot_pending_ur') : __('agricart.pages.tools.re_order_foot_pending_en');
                        $returnsLabel = $formLang === 'ur' ? __('agricart.pages.tools.re_order_foot_returns_ur') : __('agricart.pages.tools.re_order_foot_returns_en');
                        $unitSearchPh = __('agricart.pages.tools.re_order_unit_search');
                        $phraseSearchPh = __('agricart.pages.tools.re_order_phrase_search');
                        $isContPage = $page > 1;
                    @endphp
                    <div @class(['agricart-ro-sheet__body', 'agricart-ro-sheet__body--cont' => $isContPage])>
                        @for ($col = 0; $col < $gridCols; $col++)
                            @php $rowStart = $pageBase + ($col * $rowsPerCol) + 1; @endphp
                            <div class="agricart-ro-grid-col">
                                <table @class(['agricart-ro-grid', 'agricart-ro-grid--marks' => $showMarkCols, 'agricart-ro-grid--cont' => $isContPage])>
                                    <thead>
                                        <tr>
                                            @if ($showMarkCols)
                                                <th class="agricart-ro-grid__mark agricart-ro-grid__mark--ok" title="{{ __('agricart.pages.tools.re_order_line_done') }}">✓</th>
                                            @endif
                                            <th class="agricart-ro-grid__no">{{ $hNo }}</th>
                                            <th class="agricart-ro-grid__detail">{{ $hDetail }}</th>
                                            <th class="agricart-ro-grid__unit">{{ $hUnit }}</th>
                                            <th class="agricart-ro-grid__qty">{{ $hQty }}</th>
                                            <th class="agricart-ro-grid__rate">{{ $hRate }}</th>
                                            @if ($showMarkCols)
                                                <th class="agricart-ro-grid__mark agricart-ro-grid__mark--next" title="{{ __('agricart.pages.tools.re_order_line_forward') }}">→</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @for ($r = 0; $r < $rowsPerCol; $r++)
                                            @php
                                                $rowNo = $rowStart + $r;
                                                $rowKey = (string) $rowNo;
                                                $cell = $this->cellRow($rowKey);
                                                $lineFilled = $cell['detail'] !== '' || $cell['unit'] !== '' || $cell['qty'] !== '' || $cell['rate'] !== '';
                                                $lineStatus = $cell['status'] ?? '';
                                            @endphp
                                            <tr
                                                wire:key="ro-row-{{ $page }}-{{ $rowNo }}"
                                                @class([
                                                    'agricart-ro-row--done' => $lineStatus === 'done',
                                                    'agricart-ro-row--forward' => $lineStatus === 'forward',
                                                ])
                                            >
                                                @if ($showMarkCols)
                                                    <td class="agricart-ro-grid__mark agricart-ro-grid__mark--ok">
                                                        @if ($lineFilled && $canMarkLines)
                                                            <div class="agricart-ro-line-acts">
                                                                <button
                                                                    type="button"
                                                                    class="agricart-ro-dot agricart-ro-dot--ok {{ $lineStatus === 'done' ? 'is-on' : '' }}"
                                                                    wire:click="markLineStatus('{{ $rowKey }}', '{{ $lineStatus === 'done' ? '' : 'done' }}')"
                                                                    title="{{ __('agricart.pages.tools.re_order_line_done') }}"
                                                                    aria-label="{{ __('agricart.pages.tools.re_order_line_done') }}"
                                                                ></button>
                                                            </div>
                                                        @elseif ($lineFilled && $lineStatus === 'done')
                                                            <div class="agricart-ro-line-acts">
                                                                <span class="agricart-ro-dot agricart-ro-dot--ok is-on is-locked" title="{{ __('agricart.pages.tools.re_order_line_done') }}"></span>
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="agricart-ro-grid__no">{{ $rowNo }}</td>
                                                @if ($this->isViewMode())
                                                    <td class="agricart-ro-grid__detail" @if ($formLang === 'ur') dir="rtl" @endif>{{ $cell['detail'] }}</td>
                                                    <td class="agricart-ro-grid__unit" @if ($formLang === 'ur') dir="rtl" @endif>{{ $this->unitLabelFor($cell['unit']) }}</td>
                                                    <td class="agricart-ro-grid__qty" dir="ltr">{{ $cell['qty'] }}</td>
                                                    <td class="agricart-ro-grid__rate" dir="ltr">{{ $cell['rate'] }}</td>
                                                    @if ($showMarkCols)
                                                        <td class="agricart-ro-grid__mark agricart-ro-grid__mark--next">
                                                            @if ($lineFilled && $canMarkLines)
                                                                <div class="agricart-ro-line-acts">
                                                                    <button
                                                                        type="button"
                                                                        class="agricart-ro-dot agricart-ro-dot--next {{ $lineStatus === 'forward' ? 'is-on' : '' }}"
                                                                        wire:click="markLineStatus('{{ $rowKey }}', '{{ $lineStatus === 'forward' ? '' : 'forward' }}')"
                                                                        title="{{ __('agricart.pages.tools.re_order_line_forward') }}"
                                                                        aria-label="{{ __('agricart.pages.tools.re_order_line_forward') }}"
                                                                    ></button>
                                                                </div>
                                                            @elseif ($lineFilled && $lineStatus === 'forward')
                                                                <div class="agricart-ro-line-acts">
                                                                    <span class="agricart-ro-dot agricart-ro-dot--next is-on is-locked" title="{{ __('agricart.pages.tools.re_order_line_forward') }}"></span>
                                                                </div>
                                                            @endif
                                                        </td>
                                                    @endif
                                                @else
                                                    <td
                                                        class="agricart-ro-grid__detail"
                                                        wire:key="ro-detail-{{ $rowKey }}-{{ md5($cell['detail']) }}"
                                                        wire:ignore.self
                                                        x-data="roPhrasePick({
                                                            mode: 'cell',
                                                            row: @js($rowKey),
                                                            value: @js($cell['detail']),
                                                            placeholder: @js($phraseSearchPh),
                                                        })"
                                                        x-on:tools-reorder-phrases-updated.window="open = open && filtered.length > 0"
                                                    >
                                                        <div class="agricart-ro-phrasepick">
                                                            <input
                                                                type="text"
                                                                class="agricart-ro-grid__inp"
                                                                x-ref="inp"
                                                                x-model="q"
                                                                x-on:input="onType($event)"
                                                                x-on:focus="onFocus()"
                                                                x-on:blur="onBlur()"
                                                                x-on:keydown.escape.prevent="closeMenu()"
                                                                x-on:keydown.enter.prevent="pickFirst()"
                                                                x-on:keydown.arrow-down.prevent="move(1)"
                                                                x-on:keydown.arrow-up.prevent="move(-1)"
                                                                autocomplete="off"
                                                                @if ($formLang === 'ur') dir="rtl" @endif
                                                            />
                                                            <template x-teleport="body">
                                                                <div
                                                                    class="agricart-ro-phrasepick__menu"
                                                                    x-show="open"
                                                                    x-cloak
                                                                    x-bind:style="menuStyle()"
                                                                >
                                                                    <template x-for="(p, i) in filtered" :key="'dp-'+i+'-'+p">
                                                                        <button
                                                                            type="button"
                                                                            class="agricart-ro-phrasepick__opt"
                                                                            :class="{ 'is-active': i === active }"
                                                                            x-on:mousedown.prevent="pick(p)"
                                                                            x-text="p"
                                                                        ></button>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="agricart-ro-grid__unit"
                                                        wire:key="ro-unit-{{ $rowKey }}-{{ $cell['unit'] !== '' ? $cell['unit'] : 'e' }}"
                                                        wire:ignore.self
                                                        x-data="roUnitPick({
                                                            row: @js($rowKey),
                                                            selected: @js($cell['unit']),
                                                            placeholder: @js($unitSearchPh),
                                                        })"
                                                        x-on:tools-reorder-lang-changed.window="refreshLabel()"
                                                    >
                                                        <div class="agricart-ro-unitpick">
                                                            <input
                                                                type="text"
                                                                class="agricart-ro-unitpick__inp"
                                                                x-ref="inp"
                                                                x-model="q"
                                                                x-on:focus="startSearch()"
                                                                x-on:input="onType($event)"
                                                                x-on:blur="onBlur()"
                                                                x-on:keydown.escape.prevent="cancelSearch()"
                                                                x-on:keydown.enter.prevent="pickFirst()"
                                                                x-on:keydown.arrow-down.prevent="move(1)"
                                                                x-on:keydown.arrow-up.prevent="move(-1)"
                                                                :placeholder="typing ? placeholder : ''"
                                                                autocomplete="off"
                                                                @if ($formLang === 'ur') dir="rtl" @endif
                                                            />
                                                            <template x-teleport="body">
                                                                <div
                                                                    class="agricart-ro-unitpick__menu"
                                                                    x-show="open"
                                                                    x-cloak
                                                                    x-bind:style="menuStyle()"
                                                                >
                                                                    <template x-for="(u, i) in filtered" :key="'u-'+u.key">
                                                                        <button
                                                                            type="button"
                                                                            class="agricart-ro-unitpick__opt"
                                                                            :class="{ 'is-active': i === active }"
                                                                            x-on:mousedown.prevent="pick(u)"
                                                                            x-text="u.label"
                                                                        ></button>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </td>
                                                    <td class="agricart-ro-grid__qty">
                                                        <input
                                                            type="text"
                                                            class="agricart-ro-grid__inp"
                                                            wire:model.blur="cells.{{ $rowKey }}.qty"
                                                            inputmode="decimal"
                                                            dir="ltr"
                                                        />
                                                    </td>
                                                    <td class="agricart-ro-grid__rate">
                                                        <input
                                                            type="text"
                                                            class="agricart-ro-grid__inp"
                                                            wire:model.blur="cells.{{ $rowKey }}.rate"
                                                            dir="ltr"
                                                        />
                                                    </td>
                                                    @if ($showMarkCols)
                                                        <td class="agricart-ro-grid__mark agricart-ro-grid__mark--next">
                                                            @if ($lineFilled && $canMarkLines)
                                                                <div class="agricart-ro-line-acts">
                                                                    <button
                                                                        type="button"
                                                                        class="agricart-ro-dot agricart-ro-dot--next {{ $lineStatus === 'forward' ? 'is-on' : '' }}"
                                                                        wire:click="markLineStatus('{{ $rowKey }}', '{{ $lineStatus === 'forward' ? '' : 'forward' }}')"
                                                                        title="{{ __('agricart.pages.tools.re_order_line_forward') }}"
                                                                        aria-label="{{ __('agricart.pages.tools.re_order_line_forward') }}"
                                                                    ></button>
                                                                </div>
                                                            @elseif ($lineFilled && $lineStatus === 'forward')
                                                                <div class="agricart-ro-line-acts">
                                                                    <span class="agricart-ro-dot agricart-ro-dot--next is-on is-locked" title="{{ __('agricart.pages.tools.re_order_line_forward') }}"></span>
                                                                </div>
                                                            @endif
                                                        </td>
                                                    @endif
                                                @endif
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>
                            </div>
                        @endfor
                    </div>

                    @if ($amountSummary['priced'] > 0)
                        <div @class(['agricart-ro-totals', 'agricart-ro-totals--cont' => $page > 1])>
                            <span class="agricart-ro-totals__meta">
                                {{ __('agricart.pages.tools.re_order_amount_lines', ['count' => $amountSummary['priced']]) }}
                            </span>
                            <span>
                                <span class="agricart-ro-totals__k">{{ __('agricart.pages.tools.re_order_amount_total') }}</span>
                                <span class="agricart-ro-totals__v">{{ $amountSummary['encoded'] !== '' ? $amountSummary['encoded'] : $amountSummary['plain'] }}</span>
                            </span>
                        </div>
                    @endif

                    @if ($page === 1)
                        <footer class="agricart-ro-foot">
                            <table class="agricart-ro-foot__write">
                                <tr>
                                    <th>{{ $pendingLabel }}</th>
                                    @for ($fi = 0; $fi < 4; $fi++)
                                        <td>
                                            @if ($this->isViewMode())
                                                <span @if ($formLang === 'ur') dir="rtl" @endif>{{ $pendingNotes[$page][$fi] ?? '' }}</span>
                                            @else
                                                <input
                                                    type="text"
                                                    class="agricart-ro-foot__inp"
                                                    wire:model.blur="pendingNotes.{{ $page }}.{{ $fi }}"
                                                    @if ($formLang === 'ur') dir="rtl" @endif
                                                />
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            </table>
                            <table class="agricart-ro-foot__write">
                                <tr>
                                    <th>{{ $returnsLabel }}</th>
                                    @for ($fi = 0; $fi < 4; $fi++)
                                        <td>
                                            @if ($this->isViewMode())
                                                <span @if ($formLang === 'ur') dir="rtl" @endif>{{ $returnNotes[$page][$fi] ?? '' }}</span>
                                            @else
                                                <input
                                                    type="text"
                                                    class="agricart-ro-foot__inp"
                                                    wire:model.blur="returnNotes.{{ $page }}.{{ $fi }}"
                                                    @if ($formLang === 'ur') dir="rtl" @endif
                                                />
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            </table>
                        </footer>
                    @endif
                </div>
            @endfor
        </div>

        @script
        <script>
            Alpine.data('toolsReorderDraft', (config = {}) => ({
                key: 'agricart-ro-draft-u' + String(config.userId || 0),
                timer: null,
                onClear: null,
                onTry: null,
                onUnload: null,

                init() {
                    this.onClear = () => this.clear();
                    this.onTry = () => this.tryRestore();
                    this.onUnload = () => this.persist(true);
                    window.addEventListener('tools-reorder-clear-local-draft', this.onClear);
                    window.addEventListener('tools-reorder-try-local-draft', this.onTry);
                    window.addEventListener('beforeunload', this.onUnload);
                    this.timer = setInterval(() => this.persist(true), 15000);
                },

                destroy() {
                    if (this.timer) clearInterval(this.timer);
                    if (this.onClear) window.removeEventListener('tools-reorder-clear-local-draft', this.onClear);
                    if (this.onTry) window.removeEventListener('tools-reorder-try-local-draft', this.onTry);
                    if (this.onUnload) window.removeEventListener('beforeunload', this.onUnload);
                },

                snapshot() {
                    return {
                        formLang: $wire.formLang,
                        orderNo: $wire.orderNo,
                        orderDate: $wire.orderDate,
                        purchaserKey: $wire.purchaserKey,
                        preparedBy: $wire.preparedBy,
                        entryText: $wire.entryText,
                        forwardTo: $wire.forwardTo,
                        cells: $wire.cells,
                        pendingNotes: $wire.pendingNotes,
                        returnNotes: $wire.returnNotes,
                        pageCount: $wire.pageCount,
                        at: Date.now(),
                    };
                },

                persist(serverToo = false) {
                    try {
                        const data = this.snapshot();
                        const cells = data.cells || {};
                        const cellKeys = Object.keys(cells).filter((k) => !String(k).startsWith('_'));
                        const empty = !data.purchaserKey
                            && !(data.entryText || '').trim()
                            && cellKeys.length === 0;
                        if (! empty) {
                            localStorage.setItem(this.key, JSON.stringify(data));
                        }
                    } catch (e) {}
                    if (serverToo) {
                        try {
                            if ($wire.screen === 'form' && ! $wire.isViewMode()) {
                                $wire.autoSaveDraft();
                            }
                        } catch (e) {}
                    }
                },

                clear() {
                    try { localStorage.removeItem(this.key); } catch (e) {}
                },

                tryRestore() {
                    try {
                        const raw = localStorage.getItem(this.key);
                        if (!raw) return;
                        const data = JSON.parse(raw);
                        if (!data || typeof data !== 'object') return;
                        $wire.restoreLocalDraft(data);
                    } catch (e) {}
                },
            }));

            Alpine.data('toolsReorderEntry', (config = {}) => ({
                listening: false,
                recognition: null,
                lang: config.lang === 'ur' ? 'ur-PK' : 'en-US',
                phraseOpen: false,
                phraseActive: 0,
                phraseBlurTimer: null,
                entryQuery: '',

                setLang(code) {
                    this.lang = code === 'ur' ? 'ur-PK' : 'en-US';
                    if (this.recognition) {
                        this.recognition.lang = this.lang;
                    }
                },

                phraseNorm(text) {
                    return String(text || '').toLowerCase().trim();
                },

                get entryFiltered() {
                    const s = this.phraseNorm(this.entryQuery || $wire.entryText || '');
                    if (s.length < 2) {
                        return [];
                    }
                    const phrases = Array.isArray(window.__agricartRoPhrases) ? window.__agricartRoPhrases : [];
                    return phrases.filter((p) => this.phraseNorm(p).includes(s)).slice(0, 25);
                },

                entryMenuStyle() {
                    const el = this.$refs.entry;
                    if (! el || ! this.phraseOpen) {
                        return { display: 'none' };
                    }
                    const r = el.getBoundingClientRect();
                    return {
                        position: 'fixed',
                        left: Math.max(4, r.left) + 'px',
                        top: (r.bottom + 2) + 'px',
                        width: Math.max(r.width, 160) + 'px',
                        zIndex: 10050,
                    };
                },

                onEntryType(event) {
                    clearTimeout(this.phraseBlurTimer);
                    this.entryQuery = event?.target?.value ?? ($wire.entryText || '');
                    this.phraseActive = 0;
                    this.phraseOpen = this.phraseNorm(this.entryQuery).length >= 2 && this.entryFiltered.length > 0;
                },

                onEntryBlur() {
                    this.phraseBlurTimer = setTimeout(() => { this.phraseOpen = false; }, 180);
                },

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
                        const list = this.entryFiltered;
                        this.pickEntryPhrase(list[Math.min(Math.max(this.phraseActive, 0), list.length - 1)]);
                        return;
                    }
                    const box = (this.$refs.fwd?.value ?? $wire.forwardTo ?? '').trim();
                    if (box) {
                        this.doForward();
                    }
                },

                async doForward() {
                    const entryEl = this.$refs.entry;
                    const fwdEl = this.$refs.fwd;
                    const text = String(entryEl?.value ?? $wire.entryText ?? '').trim();
                    const box = String(fwdEl?.value ?? $wire.forwardTo ?? '').trim();
                    this.phraseOpen = false;
                    this.entryQuery = '';
                    if (! text) {
                        alert(@js(__('agricart.pages.tools.re_order_forward_need_text')));
                        entryEl?.focus();
                        return;
                    }
                    if (! box) {
                        alert(@js(__('agricart.pages.tools.re_order_forward_need_box')));
                        fwdEl?.focus();
                        return;
                    }
                    try {
                        await $wire.forwardEntryFromUi(text, box);
                    } catch (e) {
                        console.error(e);
                    }
                },

                toggleMic() {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognition) {
                        alert(@js(__('agricart.pages.tools.re_order_entry_mic_unsupported')));
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
                        if (!text) return;
                        const current = ($wire.entryText || '').trim();
                        $wire.set('entryText', current ? (current + ' ' + text) : text);
                        this.phraseOpen = false;
                    };
                    this.recognition.onerror = () => { this.listening = false; };
                    this.recognition.onend = () => { this.listening = false; };

                    this.listening = true;
                    try {
                        this.recognition.start();
                    } catch (e) {
                        this.listening = false;
                    }
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

                init() {
                    window.addEventListener('tools-reorder-entry-cleared', () => {
                        this.entryQuery = '';
                        this.phraseOpen = false;
                        if (this.$refs.entry) {
                            this.$refs.entry.value = '';
                        }
                        if (this.$refs.fwd) {
                            this.$refs.fwd.value = '';
                        }
                    });
                },
            }));

            if (! window.__agricartRoUnitPickRegistered) {
                window.__agricartRoUnitPickRegistered = true;
                Alpine.data('roUnitPick', (config = {}) => ({
                    row: String(config.row ?? ''),
                    placeholder: config.placeholder || '',
                    selectedKey: config.selected != null && config.selected !== '' ? String(config.selected) : '',
                    q: '',
                    open: false,
                    active: 0,
                    typing: false,
                    blurTimer: null,

                    get units() {
                        return Array.isArray(window.__agricartRoUnits) ? window.__agricartRoUnits : [];
                    },

                    init() {
                        this.showSelected();
                    },

                    selected() {
                        return this.units.find((u) => String(u.key) === String(this.selectedKey)) || null;
                    },

                    showSelected() {
                        const sel = this.selected();
                        this.q = sel ? sel.label : '';
                        this.typing = false;
                        this.open = false;
                    },

                    norm(text) {
                        return String(text || '').toLowerCase().trim();
                    },

                    get filtered() {
                        const s = this.norm(this.q);
                        if (! this.typing || s.length < 2) {
                            return [];
                        }
                        return this.units
                            .filter((u) => {
                                const hay = String(u.search || '') + ' ' + String(u.label || '');
                                return this.norm(hay).includes(s);
                            })
                            .slice(0, 25);
                    },

                    menuStyle() {
                        const el = this.$refs.inp;
                        if (! el || ! this.open) {
                            return { display: 'none' };
                        }
                        const r = el.getBoundingClientRect();
                        return {
                            position: 'fixed',
                            left: Math.max(4, r.left) + 'px',
                            top: (r.bottom + 2) + 'px',
                            width: Math.max(r.width, 128) + 'px',
                            zIndex: 10050,
                        };
                    },

                    startSearch() {
                        clearTimeout(this.blurTimer);
                        this.typing = true;
                        this.q = '';
                        this.open = false;
                        this.active = 0;
                    },

                    onType(event) {
                        clearTimeout(this.blurTimer);
                        this.typing = true;
                        if (event && event.target) {
                            this.q = event.target.value;
                        }
                        this.active = 0;
                        const s = this.norm(this.q);
                        this.open = s.length >= 2 && this.filtered.length > 0;
                    },

                    onBlur() {
                        this.blurTimer = setTimeout(() => this.cancelSearch(), 180);
                    },

                    cancelSearch() {
                        this.open = false;
                        this.showSelected();
                    },

                    pick(u) {
                        if (! u) return;
                        clearTimeout(this.blurTimer);
                        this.selectedKey = String(u.key);
                        $wire.set('cells.' + this.row + '.unit', this.selectedKey);
                        this.typing = false;
                        this.open = false;
                        this.q = u.label || '';
                    },

                    pickFirst() {
                        const list = this.filtered;
                        if (! list.length) return;
                        this.pick(list[Math.min(Math.max(this.active, 0), list.length - 1)]);
                    },

                    move(delta) {
                        const list = this.filtered;
                        if (! list.length) return;
                        this.open = true;
                        this.active = (this.active + delta + list.length) % list.length;
                    },

                    refreshLabel() {
                        this.showSelected();
                    },
                }));
            }

            if (! window.__agricartRoPhrasePickRegistered) {
                window.__agricartRoPhrasePickRegistered = true;
                Alpine.data('roPhrasePick', (config = {}) => ({
                    mode: config.mode === 'entry' ? 'entry' : 'cell',
                    row: String(config.row ?? ''),
                    placeholder: config.placeholder || '',
                    q: String(config.value ?? ''),
                    open: false,
                    active: 0,
                    blurTimer: null,
                    skipRememberOnce: false,

                    get phrases() {
                        return Array.isArray(window.__agricartRoPhrases) ? window.__agricartRoPhrases : [];
                    },

                    norm(text) {
                        return String(text || '').toLowerCase().trim();
                    },

                    get filtered() {
                        const s = this.norm(this.q);
                        if (s.length < 2) {
                            return [];
                        }
                        return this.phrases
                            .filter((p) => this.norm(p).includes(s))
                            .slice(0, 25);
                    },

                    menuStyle() {
                        const el = this.$refs.inp;
                        if (! el || ! this.open) {
                            return { display: 'none' };
                        }
                        const r = el.getBoundingClientRect();
                        return {
                            position: 'fixed',
                            left: Math.max(4, r.left) + 'px',
                            top: (r.bottom + 2) + 'px',
                            width: Math.max(r.width, 160) + 'px',
                            zIndex: 10050,
                        };
                    },

                    onFocus() {
                        clearTimeout(this.blurTimer);
                        const s = this.norm(this.q);
                        this.open = s.length >= 2 && this.filtered.length > 0;
                    },

                    onType(event) {
                        clearTimeout(this.blurTimer);
                        if (event && event.target) {
                            this.q = event.target.value;
                        }
                        this.active = 0;
                        const s = this.norm(this.q);
                        this.open = s.length >= 2 && this.filtered.length > 0;
                    },

                    onBlur() {
                        this.blurTimer = setTimeout(() => {
                            this.open = false;
                            this.commit();
                        }, 180);
                    },

                    closeMenu() {
                        this.open = false;
                    },

                    commit() {
                        const text = String(this.q || '').trim();
                        if (this.mode === 'entry') {
                            $wire.set('entryText', text);
                            return;
                        }
                        if (! this.row) return;
                        $wire.set('cells.' + this.row + '.detail', text);
                    },

                    pick(phrase) {
                        if (! phrase) return;
                        clearTimeout(this.blurTimer);
                        this.q = phrase;
                        this.open = false;
                        this.active = 0;
                        if (this.mode === 'entry') {
                            $wire.set('entryText', phrase);
                            return;
                        }
                        $wire.set('cells.' + this.row + '.detail', phrase);
                    },

                    pickFirst() {
                        const list = this.filtered;
                        if (list.length) {
                            this.pick(list[Math.min(Math.max(this.active, 0), list.length - 1)]);
                            return;
                        }
                        this.commit();
                    },

                    onEnterEntry() {
                        if (this.open && this.filtered.length) {
                            this.pickFirst();
                            return;
                        }
                        this.commit();
                        if ($wire.forwardTo) {
                            $wire.forwardEntry();
                        }
                    },

                    move(delta) {
                        const list = this.filtered;
                        if (! list.length) return;
                        this.open = true;
                        this.active = (this.active + delta + list.length) % list.length;
                    },
                }));
            }
        </script>
        @endscript
    @else
        @php $orders = $this->listedOrders(); @endphp
        <div
            class="agricart-ro-list"
            x-data
            x-init="window.dispatchEvent(new CustomEvent('tools-reorder-screen',{detail:{screen:'list'}}))"
        >
            <style>
                .agricart-ro-list{display:flex;flex-direction:column;gap:.75rem}
                .agricart-ro-list__hint{font-size:.8125rem;color:#64748b}
                .agricart-ro-list__table-wrap{
                    overflow:auto;border-radius:.75rem;background:#fff;
                    box-shadow:0 1px 2px rgba(15,23,42,.06);ring:1px solid rgba(15,23,42,.08);
                    border:1px solid rgba(15,23,42,.08);
                }
                .dark .agricart-ro-list__table-wrap{background:rgba(17,24,39,.9);border-color:rgba(255,255,255,.1)}
                .agricart-ro-list__table{width:100%;border-collapse:collapse;font-size:.8125rem}
                .agricart-ro-list__table th{
                    text-align:start;padding:.65rem .85rem;font-size:.6875rem;font-weight:700;
                    letter-spacing:.04em;text-transform:uppercase;color:#64748b;
                    border-bottom:1px solid rgba(15,23,42,.08);background:rgba(148,163,184,.08);
                }
                .agricart-ro-list__table td{
                    padding:.7rem .85rem;border-bottom:1px solid rgba(15,23,42,.06);color:#0f172a;vertical-align:middle;
                }
                .dark .agricart-ro-list__table td{color:#e5e7eb;border-bottom-color:rgba(255,255,255,.08)}
                .agricart-ro-list__table tr:last-child td{border-bottom:0}
                .agricart-ro-list__table tr:hover td{background:rgba(148,163,184,.08)}
                .agricart-ro-list__order{font-weight:700;font-variant-numeric:tabular-nums;direction:ltr}
                .agricart-ro-list__actions{display:flex;gap:.35rem;justify-content:flex-end;flex-wrap:wrap}
                .agricart-ro-list__empty{padding:1.5rem;text-align:center;color:#64748b;font-size:.875rem}
            </style>

            <p class="agricart-ro-list__hint">{{ __('agricart.pages.tools.re_order_list_hint') }}</p>

            <div class="agricart-ro-list__table-wrap">
                @if ($orders->isEmpty())
                    <div class="agricart-ro-list__empty">{{ __('agricart.pages.tools.re_order_list_empty') }}</div>
                @else
                    <table class="agricart-ro-list__table">
                        <thead>
                            <tr>
                                <th>{{ __('agricart.pages.tools.re_order_list_col_order') }}</th>
                                <th>{{ __('agricart.pages.tools.re_order_list_col_date') }}</th>
                                <th>{{ __('agricart.pages.tools.re_order_list_col_purchaser') }}</th>
                                <th>{{ __('agricart.pages.tools.re_order_list_col_prepared') }}</th>
                                <th>{{ __('agricart.pages.tools.re_order_list_col_status') }}</th>
                                <th>{{ __('agricart.pages.tools.re_order_list_col_updated') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                @php
                                    $isClosed = (string) $order->status === 'closed';
                                    $unresolved = $isClosed ? 0 : $this->unresolvedFilledLineCount(is_array($order->cells) ? $order->cells : []);
                                @endphp
                                <tr wire:key="ro-list-{{ $order->id }}">
                                    <td class="agricart-ro-list__order">{{ $order->order_no }}</td>
                                    <td dir="ltr">{{ $order->order_date }}</td>
                                    <td>{{ $order->purchaser_name ?: '—' }}</td>
                                    <td>{{ $order->prepared_by ?: '—' }}</td>
                                    <td>
                                        @if ($isClosed)
                                            {{ __('agricart.pages.tools.re_order_status_closed') }}
                                        @elseif ($unresolved > 0)
                                            {{ __('agricart.pages.tools.re_order_status_saved') }}
                                            <span style="display:block;font-size:11px;color:#b45309;font-weight:600;">
                                                {{ __('agricart.pages.tools.re_order_line_unresolved', ['count' => $unresolved]) }}
                                            </span>
                                        @else
                                            {{ __('agricart.pages.tools.re_order_status_saved') }}
                                            <span style="display:block;font-size:11px;color:#15803d;font-weight:600;">
                                                {{ __('agricart.pages.tools.re_order_line_all_marked') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td dir="ltr">{{ optional($order->updated_at)->timezone(config('app.display_timezone', config('app.timezone')))->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="agricart-ro-list__actions">
                                            <x-filament::button size="sm" color="gray" outlined wire:click="openOrder({{ $order->id }})">
                                                {{ __('agricart.pages.tools.re_order_list_open') }}
                                            </x-filament::button>
                                            @unless ($isClosed)
                                                <x-filament::button size="sm" color="primary" wire:click="editOrder({{ $order->id }})">
                                                    {{ __('agricart.pages.tools.re_order_list_edit') }}
                                                </x-filament::button>
                                                <x-filament::button
                                                    size="sm"
                                                    color="warning"
                                                    outlined
                                                    wire:click="closeListedOrder({{ $order->id }})"
                                                    wire:confirm="{{ __('agricart.pages.tools.re_order_list_close_confirm') }}"
                                                >
                                                    {{ __('agricart.pages.tools.re_order_list_close') }}
                                                </x-filament::button>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
