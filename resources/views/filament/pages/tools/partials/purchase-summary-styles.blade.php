<style>
    .agricart-catalog-toolbar--tools-ps{flex-wrap:nowrap;gap:.5rem .65rem;align-items:center}
    .agricart-catalog-toolbar--tools-ps .agricart-catalog-toolbar__search{flex:0 1 14rem;max-width:16rem;min-width:9rem}
    .agricart-ps-toolbar-slot{flex:1 1 auto;min-width:0;display:flex;align-items:center}
    .agricart-ps-toolbar-slot .agricart-ro-chrome{width:100%;margin:0;padding:0;gap:.4rem;align-items:center;flex-wrap:nowrap}
    .agricart-ro-sheet-wrap{display:flex;flex-direction:column;align-items:center;gap:14px;padding:.35rem 0 1rem}
    .agricart-ro-sheet{width:210mm;max-width:100%;min-height:297mm;background:#fff;color:#111;border:1px solid rgba(15,23,42,.12);box-shadow:0 8px 24px rgba(15,23,42,.08);padding:3mm 3.5mm;font-family:Arial,sans-serif;display:flex;flex-direction:column;justify-content:flex-start;box-sizing:border-box}
    .agricart-ro-btn{appearance:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;font-size:11px;font-weight:700;padding:5px 9px;border-radius:6px;cursor:pointer}
    .agricart-ro-head{text-align:center;padding-bottom:3px;border-bottom:1px solid #e2e8f0}
    .agricart-ro-head__name{margin:0;font-size:14px;font-weight:800}
    .agricart-ro-head__addr,.agricart-ro-head__phones{margin:1px 0 0;font-size:10px;color:#475569}
    .agricart-ro-meta{display:flex;flex-wrap:wrap;margin-top:4px;padding:4px 0 5px;border-bottom:1px solid #e2e8f0}
    .agricart-ro-meta__item{display:inline-flex;align-items:baseline;gap:4px;padding:0 10px;border-inline-end:1px solid #e2e8f0}
    .agricart-ro-meta__k{font-size:8.5px;font-weight:700;color:#64748b;text-transform:uppercase}
    .agricart-ro-meta__v{font-size:11.5px;font-weight:600;color:#0f172a}
    .agricart-ro-strip{display:flex;flex-wrap:wrap;padding:2px 0 3px;border-bottom:1px solid #cbd5e1;font-size:9.5px}
    .agricart-ro-strip__shop{font-weight:700;font-size:10px;margin-inline-end:8px}
    .agricart-ro-strip__item{display:inline-flex;gap:3px;padding:0 8px;border-inline-end:1px solid #e2e8f0}
    .agricart-ro-sheet__body{flex:0 0 auto;display:flex;flex-direction:row;margin-top:2px}
    .agricart-ro-grid-col{flex:1;min-width:0;display:flex;flex-direction:column}
    .agricart-ro-grid{width:100%;border-collapse:collapse;table-layout:fixed;font-size:13px;color:#111}
    .agricart-ro-grid th,.agricart-ro-grid td{border:1px solid #111;padding:1px 3px;height:7.2mm;vertical-align:middle}
    .agricart-ro-grid thead th{background:#374151;color:#fff;font-weight:700;text-align:center;font-size:12px;height:5.4mm}
    .agricart-ro-grid__mark{width:5%;text-align:center;padding:0;overflow:hidden;position:relative}
    .agricart-ro-grid__mark--ok{background:#f0fdf4}
    .agricart-ro-grid__mark--next{background:#fef2f2}
    thead .agricart-ro-grid__mark--ok{background:#166534;color:#fff;font-size:11px}
    thead .agricart-ro-grid__mark--next{background:#b91c1c;color:#fff;font-size:11px}
    .agricart-ro-mark-box{
        display:inline-block;width:12px;height:12px;border:1.5px solid #111;
        background:#fff;vertical-align:middle;box-sizing:border-box
    }
    .agricart-ro-line-acts{
        position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
        pointer-events:auto;z-index:2;background:inherit
    }
    .agricart-ro-grid--ps-marks .agricart-ro-grid__no{width:7%}
    .agricart-ro-grid--ps-marks .agricart-ro-grid__shopkeeper{width:54%}
    .agricart-ro-grid--ps-marks .agricart-ro-grid__payment{width:16%}
    .agricart-ro-grid__no{text-align:center;font-weight:700;direction:ltr;font-size:13px}
    .agricart-ro-grid__shopkeeper{font-size:13px;overflow:hidden}
    .agricart-ro-grid__payment{font-size:13px;text-align:end}
    .agricart-ro-grid__inp{width:100%;border:0;background:transparent;outline:none;font:inherit;font-size:13px;padding:0}
    .agricart-ro-dot{width:14px;height:14px;border-radius:50%;border:2px solid #64748b;background:#fff;cursor:pointer;padding:0;display:inline-block;vertical-align:middle}
    .agricart-ro-dot--ok{border-color:#16a34a}
    .agricart-ro-dot--next{border-color:#dc2626}
    .agricart-ro-dot--ok.is-on{background:#16a34a;border-color:#15803d}
    .agricart-ro-dot--next.is-on{background:#dc2626;border-color:#b91c1c}
    .agricart-ro-row--done td{background:#f0fdf4}
    .agricart-ro-row--forward td{background:#fef2f2}
    .agricart-ro-fulfill-banner{width:210mm;max-width:100%;margin:0 auto 8px;padding:8px 10px;border-radius:8px;border:1px solid #fcd34d;background:#fffbeb;font-size:12px}
    .agricart-ro-fulfill-banner.is-ok{border-color:#86efac;background:#f0fdf4}
    .agricart-ro-fulfill-banner--info{border-color:#93c5fd;background:#eff6ff}
    .agricart-ro-fulfill-legend{display:inline-flex;gap:10px;margin-inline-start:8px;font-size:11px}
    .agricart-ro-fulfill-legend i{width:10px;height:10px;border-radius:50%;display:inline-block}
    .agricart-ro-fulfill-legend i.is-g{background:#16a34a}
    .agricart-ro-fulfill-legend i.is-r{background:#dc2626}
    .agricart-ro-foot{margin-top:2px;flex:0 0 auto;display:flex;flex-direction:column;gap:2px}
    .agricart-ps-pending{margin-top:0;font-size:13px;color:#111}
    .agricart-ps-pending__head{display:flex;align-items:baseline;justify-content:space-between;gap:8px;margin-bottom:2px;font-size:12px}
    .agricart-ps-pending__hint{font-size:10px;color:#64748b;font-weight:500}
    .agricart-ps-pending__table{width:100%;border-collapse:collapse;table-layout:fixed}
    .agricart-ps-pending__table td{border:1px solid #111;height:7.2mm;padding:1px 3px;vertical-align:middle;width:25%}
    .agricart-ps-pending__no{display:inline-block;min-width:1.1em;font-weight:800;font-size:12px;margin-inline-end:4px;color:#334155;direction:ltr}
    .agricart-ps-pending__txt{font-size:13px}
    .agricart-ps-pending__table .agricart-ps-pending__inp{display:inline-block;width:calc(100% - 2.6em);vertical-align:middle}
    .agricart-ps-pending__clear{padding:1px 5px;font-size:10px;margin-inline-start:2px;vertical-align:middle;line-height:1.2;color:#15803d;border-color:#86efac}
    .agricart-ps-cleared{margin-top:3px;padding:3px 5px;border:1px dashed #94a3b8;background:#f8fafc;font-size:11px}
    .agricart-ps-cleared__title{display:block;font-size:11px;font-weight:800;margin-bottom:2px;color:#334155}
    .agricart-ps-cleared__list{margin:0;padding-inline-start:1.1em;list-style:disc}
    .agricart-ps-cleared__list li{margin:1px 0;line-height:1.35}
    .agricart-ro-foot__write{width:100%;border-collapse:collapse;table-layout:fixed;font-size:13px;color:#111}
    .agricart-ro-foot__write th,.agricart-ro-foot__write td{border:1px solid #111;vertical-align:middle}
    .agricart-ro-foot__write th{width:12%;background:#f3f4f6;font-weight:700;text-align:center;padding:2px 3px;font-size:12px}
    .agricart-ro-foot__write td{height:7.2mm;padding:1px 3px}
    .agricart-ro-foot__inp{
        width:100%;border:0!important;border-radius:0!important;background:transparent!important;
        outline:none!important;box-shadow:none!important;-webkit-appearance:none;appearance:none;
        font:inherit;font-size:13px!important;line-height:1.25;color:#111;padding:0;margin:0;
        min-height:0;height:auto
    }
    .agricart-ro-totals,.agricart-ps-balance{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:10px 16px;margin-top:2px;padding:4px 6px;border:1px solid #111;background:#f8fafc;font-size:11px}
    .agricart-ps-balance{justify-content:space-between}
    .agricart-ps-balance>span{display:inline-flex;align-items:baseline;gap:6px}
    .agricart-ro-totals__k{font-weight:700;font-size:10px;color:#475569;text-transform:uppercase}
    .agricart-ro-totals__v{font-weight:800;font-size:13px;direction:ltr;color:#0f172a}
    .agricart-ps-sources{margin-top:2px;font-size:13px;color:#111}
    .agricart-ps-sources__head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:2px;font-size:12px}
    .agricart-ps-sources__table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:13px}
    .agricart-ps-sources__table th,.agricart-ps-sources__table td{border:1px solid #111;padding:1px 3px;height:7.2mm;vertical-align:middle;font-size:13px}
    .agricart-ps-sources__table th{background:#f3f4f6;font-weight:700;text-align:center;font-size:12px}
    .agricart-ps-sources__table .agricart-ro-foot__inp{font-size:13px!important}
    .agricart-ps-sources__acts{width:28px;text-align:center}
    .agricart-ro-entry{display:flex;align-items:center;gap:4px;border:1px solid #cbd5e1;border-radius:8px;padding:3px 6px;background:#f8fafc;width:100%}
    .agricart-ro-entry__input{flex:1;border:0;background:transparent;outline:none;font-size:12px;min-width:0}
    .agricart-ro-entry__fwd{width:42px;border:1px solid #cbd5e1;border-radius:5px;text-align:center;font-size:11px}
    .agricart-ro-iconbtn{appearance:none;border:0;background:transparent;color:#475569;width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex:0 0 auto}
    .agricart-ro-iconbtn:hover{background:#e2e8f0;color:#0f172a}
    .agricart-ro-iconbtn.is-listening{background:#fee2e2;color:#b91c1c}
    .agricart-ro-iconbtn svg{width:15px;height:15px}
    .agricart-ro-chrome{display:flex;align-items:center;gap:.5rem;width:100%}
    .agricart-ro-phrasepick{position:relative;flex:1;min-width:0;display:flex}
    .agricart-ro-phrasepick__menu{position:fixed;z-index:10050;max-height:11rem;overflow:auto;background:#fff;border:1px solid #94a3b8;border-radius:4px;box-shadow:0 8px 20px rgba(15,23,42,.18)}
    .agricart-ro-phrasepick__opt{display:block;width:100%;border:0;background:#fff;font-size:11px;padding:5px 7px;text-align:start;cursor:pointer}
    .agricart-ro-phrasepick__opt:hover,.agricart-ro-phrasepick__opt.is-active{background:#e2e8f0}
    @media print{
        @page{size:A4;margin:4mm}
        html,body{background:#fff!important}
        .fi-sidebar,.fi-sidebar-close-overlay,.fi-topbar,.fi-topbar-ctn,.fi-header,.fi-breadcrumbs,.fi-page-header,
        .fi-page-sub-navigation-tabs,.fi-page-sub-navigation-sidebar-ctn,.fi-page-sub-navigation-dropdown,
        .fi-global-search,.fi-notifications,.fi-modal,.agricart-catalog-subnav,.agricart-catalog-toolbar,
        .agricart-ps-toolbar-slot,.agricart-ro-chrome,.agricart-ro-entry,.agricart-form-panel,.agricart-topbar-actions,
        .no-print,.agricart-ro-fulfill-banner,.agricart-ro-line-acts,.agricart-ro-dot,.agricart-ps-sources__acts,
        .agricart-ps-sources__head .agricart-ro-btn,.agricart-ps-pending__hint{display:none!important}
        .agricart-ro-mark-box{display:inline-block!important;border-color:#111!important}
        .fi-main-ctn,.fi-main,.fi-page,.fi-page-main,.fi-page-content,.fi-wi-widget,.fi-section,
        .fi-section-content-ctn,.fi-section-content{
            margin:0!important;padding:0!important;border:0!important;box-shadow:none!important;
            background:transparent!important;max-width:none!important;width:100%!important;overflow:visible!important
        }
        .agricart-ro-sheet-wrap{padding:0!important;gap:0!important;align-items:stretch!important;display:block!important}
        .agricart-ro-sheet{
            box-shadow:none!important;border:0!important;width:100%!important;max-width:none!important;
            min-height:285mm!important;height:auto!important;padding:2mm!important;
            page-break-after:always;break-after:page;
            print-color-adjust:exact;-webkit-print-color-adjust:exact;overflow:visible!important
        }
        .agricart-ro-sheet:last-child{page-break-after:auto;break-after:auto}
        .agricart-ro-sheet__body{flex:0 0 auto!important}
        .agricart-ro-foot,.agricart-ps-sources,.agricart-ps-pending,.agricart-ps-cleared,.agricart-ps-balance{
            display:block!important;visibility:visible!important;opacity:1!important
        }
        .agricart-ps-cleared__list{display:block!important}
        .agricart-ps-pending__table,.agricart-ps-sources__table{display:table!important;width:100%!important}
        .agricart-ps-balance{display:flex!important;justify-content:space-between!important}
        .agricart-ro-phrasepick__menu{display:none!important}
        .agricart-ro-grid th,.agricart-ro-grid td,.agricart-ro-grid tbody tr{height:7.2mm!important}
        .agricart-ro-grid thead th{
            height:5.4mm!important;background:#374151!important;color:#fff!important;
            print-color-adjust:exact;-webkit-print-color-adjust:exact
        }
        .agricart-ro-grid__inp,.agricart-ro-foot__inp{
            border:0!important;border-radius:0!important;background:transparent!important;
            -webkit-appearance:none;appearance:none;color:#111!important;box-shadow:none!important;
            font-size:13px!important
        }
        .agricart-ro-grid__mark{
            display:table-cell!important;width:5%!important;border:1px solid #111!important;
            background:#fff!important;color:#111!important;padding:0!important;overflow:hidden!important
        }
        .agricart-ro-grid thead th.agricart-ro-grid__mark{background:#374151!important;color:#fff!important;font-size:10px!important}
        .agricart-ro-row--done td,.agricart-ro-row--forward td{background:#fff!important}
        .agricart-ro-grid--ps-marks .agricart-ro-grid__shopkeeper{width:54%!important}
        .agricart-ro-grid--ps-marks .agricart-ro-grid__payment{width:16%!important}
        .agricart-ro-grid--ps-marks .agricart-ro-grid__no{width:7%!important}
    }
</style>
