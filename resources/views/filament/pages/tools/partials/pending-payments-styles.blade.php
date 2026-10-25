<style>
    .agricart-catalog-toolbar--tools-pp{flex-wrap:nowrap;gap:.5rem .65rem;align-items:center}
    .agricart-catalog-toolbar--tools-pp .agricart-catalog-toolbar__search{flex:0 1 14rem;max-width:16rem;min-width:9rem}
    .agricart-pp-toolbar-slot{flex:1 1 auto;min-width:0;display:flex;align-items:center}
    .agricart-pp-toolbar-slot .agricart-ro-chrome{width:100%;margin:0;padding:0;gap:.4rem;align-items:center;flex-wrap:nowrap}
    .agricart-pp-sheet-wrap{display:flex;flex-direction:column;align-items:center;gap:14px;padding:.35rem 0 1rem}
    .agricart-pp-sheet{width:210mm;max-width:100%;min-height:297mm;background:#fff;color:#111;border:1px solid rgba(15,23,42,.12);box-shadow:0 8px 24px rgba(15,23,42,.08);padding:3mm 3.5mm;font-family:Arial,sans-serif;display:flex;flex-direction:column;justify-content:flex-start;box-sizing:border-box}
    .agricart-pp-btn{appearance:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;font-size:11px;font-weight:700;padding:4px 8px;border-radius:6px;cursor:pointer}
    .agricart-pp-head{text-align:center;padding-bottom:3px;border-bottom:1px solid #e2e8f0}
    .agricart-pp-head__name{margin:0;font-size:14px;font-weight:800}
    .agricart-pp-head__addr,.agricart-pp-head__phones{margin:1px 0 0;font-size:10px;color:#475569}
    .agricart-pp-meta{display:flex;flex-wrap:wrap;margin-top:4px;padding:4px 0 5px;border-bottom:1px solid #e2e8f0}
    .agricart-pp-meta__item{display:inline-flex;align-items:baseline;gap:4px;padding:0 10px;border-inline-end:1px solid #e2e8f0}
    .agricart-pp-meta__k{font-size:8.5px;font-weight:700;color:#64748b;text-transform:uppercase}
    .agricart-pp-meta__v{font-size:11.5px;font-weight:600;color:#0f172a}
    .agricart-pp-body{flex:0 0 auto;margin-top:2px}
    .agricart-pp-grid{width:100%;border-collapse:collapse;table-layout:fixed;font-size:12px;color:#111}
    .agricart-pp-grid th,.agricart-pp-grid td{border:1px solid #111;padding:2px 4px;height:8mm;vertical-align:middle}
    .agricart-pp-grid thead th{background:#374151;color:#fff;font-weight:700;text-align:center;font-size:11px;height:6mm}
    .agricart-pp-grid__no{width:7%;text-align:center;font-weight:700;direction:ltr}
    .agricart-pp-grid__shop{width:28%}
    .agricart-pp-grid__owed{width:14%;text-align:end}
    .agricart-pp-grid__pays{width:28%;font-size:11px}
    .agricart-pp-grid__rem{width:14%;text-align:end;font-weight:700}
    .agricart-pp-grid__act{width:9%;text-align:center}
    .agricart-pp-inp{width:100%;border:0;background:transparent;outline:none;font:inherit;font-size:12px;padding:0}
    .agricart-pp-totals{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px 16px;margin-top:4px;padding:4px 6px;border:1px solid #111;background:#f8fafc;font-size:11px}
    .agricart-pp-totals__k{font-weight:700;font-size:10px;color:#475569;text-transform:uppercase}
    .agricart-pp-totals__v{font-weight:800;font-size:13px;direction:ltr;color:#0f172a}
    .agricart-pp-entry{display:flex;align-items:center;gap:4px;border:1px solid #cbd5e1;border-radius:8px;padding:3px 6px;background:#f8fafc;width:100%}
    .agricart-pp-entry__input{flex:1;border:0;background:transparent;outline:none;font-size:12px;min-width:0}
    .agricart-pp-entry__owed{width:88px;border:1px solid #cbd5e1;border-radius:5px;text-align:center;font-size:11px}
    .agricart-pp-entry__fwd{width:42px;border:1px solid #cbd5e1;border-radius:5px;text-align:center;font-size:11px}
    .agricart-pp-iconbtn{appearance:none;border:0;background:transparent;color:#475569;width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex:0 0 auto}
    .agricart-pp-iconbtn:hover{background:#e2e8f0;color:#0f172a}
    .agricart-pp-iconbtn.is-listening{background:#fee2e2;color:#b91c1c}
    .agricart-pp-iconbtn svg{width:15px;height:15px}
    .agricart-pp-chrome{display:flex;align-items:center;gap:.5rem;width:100%}
    .agricart-pp-phrasepick{position:relative;flex:1;min-width:0;display:flex}
    .agricart-pp-phrasepick__menu{position:fixed;z-index:10050;max-height:11rem;overflow:auto;background:#fff;border:1px solid #94a3b8;border-radius:4px;box-shadow:0 8px 20px rgba(15,23,42,.18)}
    .agricart-pp-phrasepick__opt{display:block;width:100%;border:0;background:#fff;font-size:11px;padding:5px 7px;text-align:start;cursor:pointer}
    .agricart-pp-phrasepick__opt:hover,.agricart-pp-phrasepick__opt.is-active{background:#e2e8f0}
    .agricart-pp-paybox{width:210mm;max-width:100%;margin:0 auto 8px;padding:8px 10px;border-radius:8px;border:1px solid #93c5fd;background:#eff6ff;font-size:12px;display:flex;flex-wrap:wrap;gap:6px;align-items:center}
    .agricart-pp-paybox input{border:1px solid #cbd5e1;border-radius:5px;padding:4px 6px;font-size:12px}
    @media print{
        @page{size:A4;margin:4mm}
        html,body{background:#fff!important}
        .fi-sidebar,.fi-sidebar-close-overlay,.fi-topbar,.fi-topbar-ctn,.fi-header,.fi-breadcrumbs,.fi-page-header,
        .fi-page-sub-navigation-tabs,.fi-global-search,.fi-notifications,.fi-modal,.agricart-catalog-subnav,
        .agricart-catalog-toolbar,.agricart-pp-toolbar-slot,.agricart-pp-chrome,.agricart-pp-entry,.agricart-form-panel,
        .agricart-topbar-actions,.no-print,.agricart-pp-paybox{display:none!important}
        .fi-main-ctn,.fi-main,.fi-page,.fi-page-main,.fi-page-content,.fi-wi-widget,.fi-section,
        .fi-section-content-ctn,.fi-section-content{
            margin:0!important;padding:0!important;border:0!important;box-shadow:none!important;
            background:transparent!important;max-width:none!important;width:100%!important;overflow:visible!important
        }
        .agricart-pp-sheet-wrap{padding:0!important;gap:0!important;display:block!important}
        .agricart-pp-sheet{
            box-shadow:none!important;border:0!important;width:100%!important;max-width:none!important;
            min-height:285mm!important;padding:2mm!important;page-break-after:always;break-after:page
        }
        .agricart-pp-sheet:last-child{page-break-after:auto;break-after:auto}
        .agricart-pp-grid thead th{background:#374151!important;color:#fff!important;print-color-adjust:exact;-webkit-print-color-adjust:exact}
        .agricart-pp-inp{border:0!important;background:transparent!important;color:#111!important}
        .agricart-pp-grid__act{display:none!important}
    }
</style>
