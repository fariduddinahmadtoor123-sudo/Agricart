@vite(['resources/js/app.js'])

@php
    $comingSoonModules = config('agricart.modules_coming_soon', []);
    $comingSoonBadge = __('agricart.module_coming_soon.badge');
@endphp

<script>
    window.agricartComingSoonModules = @json(array_values($comingSoonModules));
    window.agricartComingSoonBadge = @json($comingSoonBadge);

    function agricartMarkComingSoonNavItems() {
        const modules = window.agricartComingSoonModules ?? [];
        const badge = window.agricartComingSoonBadge ?? 'Soon';

        if (!Array.isArray(modules) || modules.length === 0) {
            return;
        }

        document.querySelectorAll('.fi-sidebar-item-btn[href]').forEach((link) => {
            const href = link.getAttribute('href') ?? '';

            const matched = modules.some((key) => href.includes('/admin/' + key));

            if (!matched) {
                return;
            }

            const item = link.closest('.fi-sidebar-item');

            if (item) {
                item.classList.add('agricart-nav--coming-soon');
            }

            const label = link.querySelector('.fi-sidebar-item-label');

            if (label && !label.dataset.comingSoonBadge) {
                label.dataset.comingSoonBadge = badge;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', agricartMarkComingSoonNavItems);
    document.addEventListener('livewire:navigated', agricartMarkComingSoonNavItems);

    if (! window.__agricartLivewireDispatchBound) {
        window.__agricartLivewireDispatchBound = true;

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-agricart-livewire-dispatch]');

            if (! trigger) {
                return;
            }

            const eventName = trigger.getAttribute('data-agricart-livewire-dispatch');

            if (! eventName || ! window.Livewire || typeof window.Livewire.dispatch !== 'function') {
                return;
            }

            event.preventDefault();
            window.Livewire.dispatch(eventName);
        });
    }
</script>
