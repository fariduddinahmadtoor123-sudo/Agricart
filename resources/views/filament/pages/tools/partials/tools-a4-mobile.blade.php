{{-- Screen-only: make External Apps A4 sheets usable on phones. Print CSS stays A4. --}}
<style>
    @media screen and (max-width: 900px) {
        /*
         * Do NOT wrap the catalog toolbar (search + filters + actions) into a column —
         * that row must stay one line. Only wrap the in-sheet entry chrome fields.
         */
        .agricart-catalog-toolbar--tools-reorder,
        .agricart-catalog-toolbar--tools-ps,
        .agricart-catalog-toolbar--tools-pp,
        .agricart-catalog-toolbar--tools-he {
            flex-wrap: nowrap !important;
            align-items: center !important;
        }
        .agricart-ro-toolbar-slot,
        .agricart-ps-toolbar-slot,
        .agricart-pp-toolbar-slot,
        .agricart-he-toolbar-slot {
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }
        .agricart-ro-chrome,
        .agricart-pp-chrome {
            flex-wrap: wrap !important;
            gap: .45rem !important;
        }
        .agricart-ro-entry,
        .agricart-pp-entry {
            flex-wrap: wrap !important;
            min-width: 0 !important;
            width: 100% !important;
        }
        .agricart-ro-entry .agricart-ro-phrasepick,
        .agricart-pp-entry .agricart-pp-phrasepick,
        .agricart-ro-entry__input,
        .agricart-pp-entry__input {
            flex: 1 1 100% !important;
            min-width: 0 !important;
            max-width: none !important;
        }
        .agricart-ro-toolbar-slot .agricart-ro-chrome__purchaser {
            flex: 1 1 100% !important;
            max-width: none !important;
            min-width: 0 !important;
        }

        /* Paper: fluid on screen (not crushed fixed-mm soup) */
        .agricart-ro-sheet-wrap,
        .agricart-pp-sheet-wrap {
            align-items: stretch !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: .25rem 0 1rem !important;
            overflow-x: visible !important;
        }
        .agricart-ro-sheet,
        .agricart-pp-sheet,
        .agricart-ro-fulfill-banner,
        .agricart-pp-paybox {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            min-height: 0 !important;
            padding: 10px 8px !important;
            box-sizing: border-box !important;
        }

        /* Two paper columns → stack for thumb typing */
        .agricart-ro-sheet__body {
            flex-direction: column !important;
            gap: 10px !important;
        }
        .agricart-ro-grid-col {
            flex: 1 1 auto !important;
            width: 100% !important;
        }

        /* Touch-friendlier cells */
        .agricart-ro-grid,
        .agricart-pp-grid {
            font-size: 14px !important;
        }
        .agricart-ro-grid th,
        .agricart-ro-grid td,
        .agricart-pp-grid th,
        .agricart-pp-grid td,
        .agricart-ps-pending__table td,
        .agricart-ps-sources__table th,
        .agricart-ps-sources__table td,
        .agricart-ro-foot__write th,
        .agricart-ro-foot__write td {
            height: auto !important;
            min-height: 2.4rem !important;
            padding: 4px 5px !important;
        }
        .agricart-ro-grid thead th,
        .agricart-pp-grid thead th {
            min-height: 2rem !important;
            font-size: 12px !important;
        }
        .agricart-ro-grid__inp,
        .agricart-pp-inp,
        .agricart-ro-foot__inp,
        .agricart-ps-pending__inp {
            font-size: 14px !important;
            min-height: 1.75rem !important;
        }
        .agricart-ro-dot {
            width: 18px !important;
            height: 18px !important;
        }
        .agricart-ro-meta,
        .agricart-pp-meta {
            gap: 6px 0 !important;
        }
        .agricart-ro-meta__item,
        .agricart-pp-meta__item {
            border-inline-end: 0 !important;
            padding: 0 6px 4px 0 !important;
            flex: 1 1 45% !important;
        }
        .agricart-ro-meta__item--prepared,
        .agricart-ro-meta__item:last-child {
            margin-inline-start: 0 !important;
            text-align: start !important;
        }
        .agricart-ro-strip {
            gap: 4px !important;
        }
        .agricart-ro-strip__shop {
            max-width: 100% !important;
        }
        .agricart-ps-balance,
        .agricart-ro-totals,
        .agricart-pp-totals {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 6px !important;
        }
        .agricart-ps-sources__table,
        .agricart-ps-pending__table {
            display: block !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        .agricart-ro-list__table-wrap {
            -webkit-overflow-scrolling: touch;
        }
        .agricart-ro-list__actions {
            justify-content: flex-start !important;
        }
    }

    @media screen and (max-width: 560px) {
        .agricart-ro-grid--ps-marks .agricart-ro-grid__shopkeeper { width: 48% !important; }
        .agricart-ro-grid--ps-marks .agricart-ro-grid__payment { width: 22% !important; }
        .agricart-ro-grid--ps-marks .agricart-ro-grid__no { width: 10% !important; }
        .agricart-ro-grid--ps-marks .agricart-ro-grid__mark { width: 10% !important; }
        .agricart-pp-grid__shop { width: 32% !important; }
        .agricart-pp-grid__pays { width: 24% !important; }
        .agricart-pp-entry__owed { width: 100% !important; max-width: none !important; }
    }
</style>
