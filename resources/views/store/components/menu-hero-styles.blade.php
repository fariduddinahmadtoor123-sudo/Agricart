{{-- Critical menu + hero slider styles (fallback until assets are rebuilt on server) --}}
<style>
    .ag-store-menu__desktop { display: block; }
    .ag-store-menu__mobile-bar,
    .ag-store-menu__mobile-fallback { display: none; }
    .ag-store-header__menu-toggle { display: none; }

    @media (min-width: 42.0625rem) {
        .ag-store-menu__desktop { display: block; }
        .ag-store-menu-drawer { display: none !important; }
        .ag-store-header__menu-toggle { display: none !important; }
        .ag-store-menu .ag-store-menu__desktop .ag-store-menu__list { flex-direction: row; }
    }

    @media (max-width: 42rem) {
        .ag-store-header__menu-toggle.is-enhanced { display: inline-flex; }
        .ag-store-menu { border-bottom: 0; background: transparent; }
        .ag-store-menu__desktop { display: none; }

        .ag-store-menu-drawer {
            display: block;
            position: fixed;
            inset: 0;
            z-index: 120;
            pointer-events: none;
            visibility: hidden;
        }

        .ag-store-menu-drawer.is-open {
            pointer-events: auto;
            visibility: visible;
        }

        .ag-store-menu-drawer__backdrop {
            position: absolute;
            inset: 0;
            border: 0;
            padding: 0;
            background: rgb(15 23 42 / 0.45);
            opacity: 0;
            transition: opacity 0.2s ease;
            cursor: pointer;
        }

        .ag-store-menu-drawer.is-open .ag-store-menu-drawer__backdrop { opacity: 1; }

        .ag-store-menu-drawer__panel {
            position: absolute;
            inset-block: 0;
            inset-inline-start: 0;
            display: flex;
            flex-direction: column;
            width: min(18rem, 88vw);
            max-width: 100%;
            background: var(--agricart-surface);
            box-shadow: 0 0 2rem rgb(15 23 42 / 0.18);
            transform: translateX(-105%);
            transition: transform 0.28s ease;
            overflow: hidden;
        }

        html[dir='rtl'] .ag-store-menu-drawer__panel { transform: translateX(105%); }
        .ag-store-menu-drawer.is-open .ag-store-menu-drawer__panel { transform: translateX(0); }

        .ag-store-menu-drawer__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--agricart-border);
            background: color-mix(in oklab, var(--agricart-primary) 8%, white);
        }

        .ag-store-menu-drawer__title { font-size: 0.9375rem; font-weight: 700; }

        .ag-store-menu-drawer__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
        }

        .ag-store-menu-drawer .ag-store-menu__list {
            flex: 1;
            flex-direction: column;
            overflow-y: auto;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .ag-store-menu-drawer .ag-store-menu__link {
            display: block;
            border-block-start: 1px solid var(--agricart-border);
            padding: 0.9rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
    }

    body.ag-store-menu-open { overflow: hidden; }

    .ag-store-hero-slider { background: var(--agricart-surface); border-bottom: 1px solid var(--agricart-border); --ag-hero-slide-duration: 1.75s; }
    .ag-store-hero-slider__viewport {
        position: relative;
        width: 100%;
        overflow: hidden;
        aspect-ratio: 1152 / 384;
        background: color-mix(in oklab, var(--agricart-border) 35%, white);
    }
    .ag-store-hero-slider__track {
        display: flex;
        width: 100%;
        height: 100%;
        transition: transform var(--ag-hero-slide-duration, 1.75s) ease-in-out;
        will-change: transform;
    }
    .ag-store-hero-slider__track.is-instant { transition: none; }
    .ag-store-hero-slider__slide {
        flex: 0 0 100%;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .ag-store-hero-slider__link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    .ag-store-hero-slider__image {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center center;
    }
</style>
