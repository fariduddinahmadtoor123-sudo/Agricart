import './password-toggle.js';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-store-ticker]').forEach((root) => {
        const track = root.querySelector('[data-store-ticker-track]');
        const pauseBtn = root.querySelector('[data-store-ticker-pause]');
        const slowerBtn = root.querySelector('[data-store-ticker-slower]');
        const fasterBtn = root.querySelector('[data-store-ticker-faster]');

        if (! track) {
            return;
        }

        let speed = Number.parseInt(root.dataset.speed ?? '40', 10);

        if (Number.isNaN(speed)) {
            speed = 40;
        }

        speed = Math.min(100, Math.max(10, speed));

        const applySpeed = () => {
            const duration = Math.max(8, 120 - speed);
            track.style.setProperty('--ag-store-ticker-duration', `${duration}s`);
        };

        applySpeed();

        pauseBtn?.addEventListener('click', () => {
            const paused = root.classList.toggle('is-paused');
            pauseBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
            pauseBtn.textContent = paused ? '▶' : '⏸';
        });

        slowerBtn?.addEventListener('click', () => {
            speed = Math.max(10, speed - 10);
            applySpeed();
        });

        fasterBtn?.addEventListener('click', () => {
            speed = Math.min(100, speed + 10);
            applySpeed();
        });
    });

    document.querySelectorAll('[data-store-product-gallery]').forEach((gallery) => {
        const mainImage = gallery.querySelector('[data-store-product-gallery-main]');
        const thumbs = gallery.querySelectorAll('[data-store-product-gallery-thumb]');

        if (! mainImage || thumbs.length === 0) {
            return;
        }

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                const url = thumb.dataset.imageUrl;

                if (! url) {
                    return;
                }

                mainImage.src = url;
                thumbs.forEach((item) => {
                    const active = item === thumb;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            });
        });
    });

    document.querySelectorAll('[data-store-product-card]').forEach((card) => {
        const mainImage = card.querySelector('[data-store-product-card-main]');
        const thumbs = card.querySelectorAll('[data-store-product-card-thumb]');

        if (! mainImage || thumbs.length === 0) {
            return;
        }

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const url = thumb.dataset.imageUrl;

                if (! url) {
                    return;
                }

                mainImage.src = url;
                thumbs.forEach((item) => {
                    const active = item === thumb;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            });
        });
    });

    const messageDialog = document.querySelector('[data-store-message-dialog]');

    if (messageDialog instanceof HTMLDialogElement) {
        document.querySelectorAll('[data-store-message-open]').forEach((button) => {
            button.addEventListener('click', () => messageDialog.showModal());
        });

        messageDialog.querySelector('[data-store-message-close]')?.addEventListener('click', () => {
            messageDialog.close();
        });

        messageDialog.addEventListener('click', (event) => {
            if (event.target === messageDialog) {
                messageDialog.close();
            }
        });

        if (messageDialog.dataset.open === 'true') {
            messageDialog.showModal();
        }
    }

    document.querySelectorAll('[data-store-pdp-tabs]').forEach((root) => {
        const tabs = root.querySelectorAll('[data-store-pdp-tab]');
        const panels = root.querySelectorAll('[data-store-pdp-panel]');

        if (tabs.length === 0) {
            return;
        }

        const activateTab = (tab, focus = false) => {
            const key = tab.dataset.storePdpTab;

            tabs.forEach((item) => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
                item.tabIndex = active ? 0 : -1;
            });

            panels.forEach((panel) => {
                const active = panel.dataset.storePdpPanel === key;
                panel.classList.toggle('is-active', active);
                panel.hidden = ! active;
            });

            if (focus) {
                tab.focus();
                tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            }
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activateTab(tab));
            tab.addEventListener('keydown', (event) => {
                let nextIndex = null;

                if (event.key === 'ArrowRight') {
                    nextIndex = (index + 1) % tabs.length;
                } else if (event.key === 'ArrowLeft') {
                    nextIndex = (index - 1 + tabs.length) % tabs.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = tabs.length - 1;
                }

                if (nextIndex === null) {
                    return;
                }

                event.preventDefault();
                activateTab(tabs[nextIndex], true);
            });
        });

        activateTab(root.querySelector('[data-store-pdp-tab].is-active') ?? tabs[0]);
    });
});
