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
});
