import './password-toggle.js';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-store-ticker]').forEach((root) => {
        const track = root.querySelector('[data-store-ticker-track]');

        if (! track) {
            return;
        }

        let speed = Number.parseInt(root.dataset.speed ?? '40', 10);

        if (Number.isNaN(speed)) {
            speed = 40;
        }

        speed = Math.min(100, Math.max(10, speed));
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const duration = Math.max(8, 120 - speed);

        track.style.setProperty('--ag-store-ticker-duration', `${duration}s`);

        if (reducedMotion) {
            root.classList.add('is-paused');
        }
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

    document.querySelectorAll('[data-store-menu-toggle]').forEach((button) => {
        const drawer = document.querySelector('[data-store-menu-drawer]');
        const backdrop = drawer?.querySelector('[data-store-menu-backdrop]');
        const closeButtons = drawer?.querySelectorAll('[data-store-menu-close]') ?? [];

        if (! drawer) {
            return;
        }

        button.classList.add('is-enhanced');

        const labelOpen = button.dataset.labelOpen || 'Open menu';
        const labelClose = button.dataset.labelClose || 'Close menu';

        const setOpen = (open) => {
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            button.setAttribute('aria-label', open ? labelClose : labelOpen);
            drawer.classList.toggle('is-open', open);
            drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
            document.body.classList.toggle('ag-store-menu-open', open);

            if (open) {
                drawer.querySelector('[data-store-menu-close]')?.focus();
            } else {
                button.focus();
            }
        };

        button.addEventListener('click', () => {
            setOpen(button.getAttribute('aria-expanded') !== 'true');
        });

        backdrop?.addEventListener('click', () => setOpen(false));
        closeButtons.forEach((closeButton) => {
            closeButton.addEventListener('click', () => setOpen(false));
        });

        drawer.querySelectorAll('[data-store-menu-link]').forEach((link) => {
            link.addEventListener('click', () => setOpen(false));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
                setOpen(false);
            }
        });

        window.matchMedia('(min-width: 42.0625rem)').addEventListener('change', (event) => {
            if (event.matches && drawer.classList.contains('is-open')) {
                setOpen(false);
            }
        });
    });

    document.querySelectorAll('[data-store-hero-slider]').forEach((root) => {
        const track = root.querySelector('[data-store-hero-track]');
        const slides = [...root.querySelectorAll('[data-store-hero-slide]')];
        const dots = [...root.querySelectorAll('[data-store-hero-dot]')];
        const prev = root.querySelector('[data-store-hero-prev]');
        const next = root.querySelector('[data-store-hero-next]');

        if (! track || slides.length === 0) {
            return;
        }

        let index = 0;
        let timer = null;
        const intervalMs = Math.max(500, Number.parseInt(root.dataset.interval ?? '2000', 10));
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isRtl = document.documentElement.dir === 'rtl';
        let touchStartX = null;

        if (reducedMotion) {
            track.classList.add('is-instant');
        }

        const updateTrack = () => {
            const offset = index * 100;
            track.style.transform = isRtl
                ? `translateX(${offset}%)`
                : `translateX(-${offset}%)`;
        };

        const show = (nextIndex) => {
            index = (nextIndex + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                const active = slideIndex === index;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            dots.forEach((dot, dotIndex) => {
                const active = dotIndex === index;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            updateTrack();
        };

        const stop = () => {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        };

        const start = () => {
            stop();

            if (reducedMotion || slides.length <= 1) {
                return;
            }

            timer = window.setInterval(() => {
                show(index + 1);
            }, intervalMs);
        };

        if (slides.length <= 1) {
            show(0);
            return;
        }

        prev?.addEventListener('click', () => {
            show(index - 1);
            start();
        });

        next?.addEventListener('click', () => {
            show(index + 1);
            start();
        });

        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const target = Number.parseInt(dot.dataset.storeHeroDot ?? '0', 10);

                if (! Number.isNaN(target)) {
                    show(target);
                    start();
                }
            });
        });

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', () => {
            window.setTimeout(() => {
                if (! root.contains(document.activeElement)) {
                    start();
                }
            }, 0);
        });

        root.addEventListener('touchstart', (event) => {
            touchStartX = event.changedTouches[0]?.clientX ?? null;
        }, { passive: true });

        root.addEventListener('touchend', (event) => {
            if (touchStartX === null) {
                return;
            }

            const touchEndX = event.changedTouches[0]?.clientX ?? touchStartX;
            const delta = touchEndX - touchStartX;
            touchStartX = null;

            if (Math.abs(delta) < 40) {
                return;
            }

            const swipeNext = isRtl ? delta > 0 : delta < 0;
            show(swipeNext ? index + 1 : index - 1);
            start();
        }, { passive: true });

        show(0);
        start();
    });

    document.querySelectorAll('[data-copy-value]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copyValue ?? '';
            const successLabel = button.dataset.copySuccess ?? button.textContent;
            const originalLabel = button.dataset.copyLabel ?? button.textContent;

            try {
                await navigator.clipboard.writeText(value);
            } catch {
                const input = document.createElement('textarea');
                input.value = value;
                input.setAttribute('readonly', '');
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }

            button.textContent = successLabel;
            window.setTimeout(() => {
                button.textContent = originalLabel;
            }, 1600);
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

        root.addEventListener('invalid', (event) => {
            const field = event.target;

            if (! (field instanceof HTMLElement)) {
                return;
            }

            const panel = field.closest('[data-store-pdp-panel]');

            if (! (panel instanceof HTMLElement) || ! panel.hidden) {
                return;
            }

            const key = panel.dataset.storePdpPanel;
            const tab = root.querySelector(`[data-store-pdp-tab="${key}"]`);

            if (! (tab instanceof HTMLElement)) {
                return;
            }

            activateTab(tab, true);
            window.requestAnimationFrame(() => {
                if (typeof field.focus === 'function') {
                    field.focus({ preventScroll: false });
                }
            });
        }, true);
    });

    document.querySelectorAll('[data-store-copy-link]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.getAttribute('data-copy-url') ?? '';
            const copiedLabel = button.getAttribute('data-copied-label') ?? 'Copied';
            const originalLabel = button.textContent;

            if (! url) {
                return;
            }

            try {
                await navigator.clipboard.writeText(url);
                button.textContent = copiedLabel;
                window.setTimeout(() => {
                    button.textContent = originalLabel;
                }, 2000);
            } catch {
                window.prompt(copiedLabel, url);
            }
        });
    });

    document.querySelectorAll('[data-store-star-picker]').forEach((root) => {
        const labels = root.querySelectorAll('.ag-store-star-picker__star');
        const status = root.querySelector('[data-store-star-label]');
        const noneLabel = status?.dataset.noneLabel ?? '';
        const selectedTemplate = status?.dataset.selectedTemplate ?? ':count';

        const paint = (activeValue) => {
            labels.forEach((label) => {
                const input = label.querySelector('input');
                const value = Number.parseInt(input?.value ?? '0', 10);
                label.classList.toggle('is-active', Number.isFinite(activeValue) && value <= activeValue);
            });
        };

        const selected = root.querySelector('input[type="radio"]:checked');
        paint(selected ? Number.parseInt(selected.value, 10) : 0);

        labels.forEach((label) => {
            const input = label.querySelector('input');
            if (! input) {
                return;
            }

            label.addEventListener('mouseenter', () => {
                paint(Number.parseInt(input.value, 10));
            });

            label.addEventListener('mouseleave', () => {
                const checked = root.querySelector('input[type="radio"]:checked');
                paint(checked ? Number.parseInt(checked.value, 10) : 0);
            });

            input.addEventListener('change', () => {
                const value = Number.parseInt(input.value, 10);
                paint(value);
                if (status) {
                    status.textContent = selectedTemplate.replace(':count', String(value));
                }
            });
        });

        if (! selected && status && noneLabel) {
            status.textContent = noneLabel;
        }
    });

    document.querySelectorAll('[data-store-testimonials-carousel]').forEach((root) => {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const dialog = root.querySelector('[data-store-testimonial-dialog]');
        let dialogOpen = false;

        const pause = () => root.classList.add('is-paused');
        const resume = () => {
            if (! reducedMotion && ! dialogOpen && ! root.contains(document.activeElement)) {
                root.classList.remove('is-paused');
            }
        };

        if (reducedMotion) {
            pause();
        }

        root.addEventListener('mouseenter', pause);
        root.addEventListener('mouseleave', resume);
        root.addEventListener('focusin', pause);
        root.addEventListener('focusout', () => {
            window.setTimeout(resume, 0);
        });

        if (! (dialog instanceof HTMLDialogElement)) {
            return;
        }

        const nameEl = dialog.querySelector('[data-dialog-name]');
        const titleEl = dialog.querySelector('[data-dialog-title]');
        const bodyEl = dialog.querySelector('[data-dialog-body]');
        const starsEl = dialog.querySelector('[data-dialog-stars]');
        const avatarEl = dialog.querySelector('[data-dialog-avatar]');
        const replyWrap = dialog.querySelector('[data-dialog-reply-wrap]');
        const replyEl = dialog.querySelector('[data-dialog-reply]');

        const fillStars = (rating) => {
            if (! starsEl) {
                return;
            }
            starsEl.innerHTML = '';
            for (let star = 1; star <= 5; star += 1) {
                const span = document.createElement('span');
                span.textContent = '★';
                if (star <= rating) {
                    span.className = 'is-on';
                }
                starsEl.appendChild(span);
            }
        };

        const openReview = (button) => {
            const card = button.closest('.ag-store-testimonials-carousel__card');
            const payload = card?.querySelector('[data-store-testimonial-payload]');
            if (! payload) {
                return;
            }

            const rating = Number.parseInt(payload.querySelector('[data-payload-rating]')?.textContent ?? '0', 10);
            const name = payload.querySelector('[data-payload-name]')?.textContent?.trim() ?? '';
            const title = payload.querySelector('[data-payload-title]')?.textContent?.trim() ?? '';
            const body = payload.querySelector('[data-payload-body]')?.textContent ?? '';
            const photo = payload.querySelector('[data-payload-photo]')?.textContent?.trim() ?? '';
            const reply = payload.querySelector('[data-payload-reply]')?.textContent?.trim() ?? '';

            if (nameEl) {
                nameEl.textContent = name;
            }
            if (bodyEl) {
                bodyEl.textContent = body;
            }
            fillStars(rating);

            if (titleEl) {
                if (title) {
                    titleEl.hidden = false;
                    titleEl.textContent = title;
                } else {
                    titleEl.hidden = true;
                    titleEl.textContent = '';
                }
            }

            if (avatarEl) {
                avatarEl.replaceChildren();
                if (photo) {
                    const img = document.createElement('img');
                    img.src = photo;
                    img.alt = '';
                    img.width = 48;
                    img.height = 48;
                    avatarEl.appendChild(img);
                    avatarEl.hidden = false;
                } else {
                    const initial = document.createElement('span');
                    initial.textContent = (name || '?').slice(0, 1).toUpperCase();
                    avatarEl.appendChild(initial);
                    avatarEl.hidden = false;
                }
            }

            if (replyWrap && replyEl) {
                if (reply) {
                    replyEl.textContent = reply;
                    replyWrap.hidden = false;
                } else {
                    replyEl.textContent = '';
                    replyWrap.hidden = true;
                }
            }

            dialogOpen = true;
            pause();
            dialog.showModal();
        };

        root.querySelectorAll('[data-store-testimonial-open]').forEach((button) => {
            button.addEventListener('click', () => openReview(button));
        });

        dialog.querySelectorAll('[data-store-testimonial-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });

        dialog.addEventListener('close', () => {
            dialogOpen = false;
            resume();
        });
    });

    document.querySelectorAll('[data-store-brands-carousel]').forEach((root) => {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const pause = () => root.classList.add('is-paused');
        const resume = () => {
            if (! reducedMotion && ! root.contains(document.activeElement)) {
                root.classList.remove('is-paused');
            }
        };

        if (reducedMotion) {
            pause();
        }

        root.addEventListener('mouseenter', pause);
        root.addEventListener('mouseleave', resume);
        root.addEventListener('focusin', pause);
        root.addEventListener('focusout', () => {
            window.setTimeout(resume, 0);
        });
    });

    document.querySelectorAll('[data-store-profile-photo-form]').forEach((form) => {
        const root = form.querySelector('[data-store-profile-photo]');
        if (! root) {
            return;
        }

        const input = root.querySelector('[data-store-profile-photo-input]');
        const preview = root.querySelector('[data-store-profile-photo-preview]');
        const status = root.querySelector('[data-store-profile-photo-status]');
        const busy = root.querySelector('[data-store-profile-photo-busy]');
        const remove = root.querySelector('[data-store-profile-photo-remove]');
        const submit = form.querySelector('[data-store-profile-photo-submit]');
        const submitLabel = form.querySelector('[data-store-profile-photo-submit-label]');
        const maxBytes = Number.parseInt(root.dataset.maxBytes ?? '2097152', 10);
        const allowedTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
        let objectUrl = null;

        const setStatus = (message, isError = false) => {
            if (! status) {
                return;
            }
            status.textContent = message ?? '';
            status.classList.toggle('is-error', Boolean(isError && message));
            status.classList.toggle('is-ready', Boolean(! isError && message));
        };

        const showPreview = (file) => {
            if (! preview || ! file) {
                return;
            }

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
            objectUrl = URL.createObjectURL(file);

            let img = preview.querySelector('[data-store-profile-photo-img]');
            const fallback = preview.querySelector('[data-store-profile-photo-fallback]');
            if (fallback) {
                fallback.hidden = true;
            }

            if (! img) {
                img = document.createElement('img');
                img.className = 'ag-store-account__photo-img';
                img.setAttribute('data-store-profile-photo-img', '');
                img.alt = '';
                preview.insertBefore(img, busy ?? null);
            }

            img.hidden = false;
            img.src = objectUrl;
            preview.classList.add('has-local-preview');
        };

        input?.addEventListener('change', () => {
            const file = input.files?.[0];
            if (! file) {
                setStatus('');
                return;
            }

            if (remove) {
                remove.checked = false;
            }

            if (! allowedTypes.has(file.type)) {
                input.value = '';
                setStatus(root.dataset.msgInvalid ?? '', true);
                return;
            }

            if (file.size > maxBytes) {
                input.value = '';
                setStatus(root.dataset.msgTooLarge ?? '', true);
                return;
            }

            showPreview(file);
            setStatus(root.dataset.msgReady ?? '', false);
        });

        form.addEventListener('submit', () => {
            const hasFile = Boolean(input?.files?.length);
            const savingLabel = hasFile
                ? (root.dataset.msgUploading ?? '')
                : (root.dataset.msgSaving ?? '');

            if (savingLabel) {
                setStatus(savingLabel, false);
            }

            if (hasFile && busy) {
                busy.hidden = false;
                preview?.classList.add('is-uploading');
            }

            if (submit) {
                submit.disabled = true;
                submit.setAttribute('aria-busy', 'true');
            }

            if (submitLabel && savingLabel) {
                submitLabel.textContent = savingLabel;
            }
        });
    });
});
