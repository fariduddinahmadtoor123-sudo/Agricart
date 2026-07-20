<style>
    .ag-store-header__search {
        position: relative;
        z-index: 40;
        overflow: visible;
    }
    .ag-store-header__search--live .ag-store-header__search-form {
        position: relative;
    }
    .ag-store-header__search--live .ag-store-header__search-btn--submit {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    .ag-store-header__search--live .ag-store-header__search-input {
        padding-inline-end: 0.75rem;
    }
    .ag-store-search-suggestions {
        position: absolute;
        inset-block-start: calc(100% + 0.25rem);
        inset-inline: 0;
        max-height: min(24rem, 65vh);
        overflow-y: auto;
        border: 1px solid var(--agricart-border, #d1d5db);
        background: var(--agricart-surface, #fff);
        box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.14);
    }
    .ag-store-search-suggestions__option {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0.65rem;
        padding: 0.55rem 0.75rem;
        border-block-end: 1px solid var(--agricart-border, #d1d5db);
        color: var(--agricart-text, #0f172a);
        text-decoration: none;
    }
    .ag-store-search-suggestions__option:hover,
    .ag-store-search-suggestions__option:focus-visible,
    .ag-store-search-suggestions__option.is-active {
        background: color-mix(in srgb, var(--agricart-primary, #83b735) 12%, white);
        outline: none;
    }
    .ag-store-search-suggestions__thumb {
        flex: 0 0 auto;
        width: 2.5rem;
        height: 2.5rem;
        border: 1px solid var(--agricart-border, #d1d5db);
        background: var(--agricart-bg, #f4f5f7);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ag-store-search-suggestions__thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .ag-store-search-suggestions__thumb-letter {
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--agricart-muted, #64748b);
        line-height: 1;
    }
    .ag-store-search-suggestions__text {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .ag-store-search-suggestions__name {
        font-weight: 700;
        line-height: 1.3;
    }
    .ag-store-search-suggestions__meta {
        color: var(--agricart-muted, #64748b);
        font-size: 0.75rem;
    }
    .ag-store-search-suggestions__all {
        color: var(--agricart-primary, #83b735);
        font-weight: 700;
        justify-content: center;
    }
    .ag-store-search-suggestions__all .ag-store-search-suggestions__thumb {
        display: none;
    }

    /* Search results — same grid as category/product cards (works without Vite rebuild) */
    .ag-store-search-results-grid.ag-store-product-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
    .ag-store-search-results-grid .ag-store-product-card {
        display: flex;
        flex-direction: column;
        min-width: 0;
        border: 1px solid var(--agricart-border, #d1d5db);
        background: var(--agricart-surface, #fff);
    }
    .ag-store-search-results-grid .ag-store-product-card__image-wrap {
        position: relative;
        aspect-ratio: 1;
        max-height: 11rem;
        overflow: hidden;
        border-bottom: 1px solid var(--agricart-border, #d1d5db);
        background: var(--agricart-bg, #fff);
    }
    .ag-store-search-results-grid .ag-store-product-card__image {
        width: 100%;
        height: 100%;
        max-width: 100%;
        object-fit: cover;
        display: block;
    }
    .ag-store-search-results-grid .ag-store-product-card__placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        min-height: 8rem;
        font-size: 1.75rem;
        font-weight: 700;
        color: #6b7280;
    }
    .ag-store-search-results-grid .ag-store-product-card__body {
        padding: 0.65rem 0.7rem;
    }
    .ag-store-search-results-grid .ag-store-product-card__name {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 700;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
    }
    @media (max-width: 72rem) {
        .ag-store-search-results-grid.ag-store-product-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    @media (max-width: 56rem) {
        .ag-store-search-results-grid.ag-store-product-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 40rem) {
        .ag-store-search-results-grid.ag-store-product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>
