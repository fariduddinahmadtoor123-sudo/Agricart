<x-filament-panels::page>
    @script
        <script>
            const bindAttributesToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-attributes');

                if (! input || input.dataset.attributesSearchBound === 'true') {
                    return;
                }

                input.dataset.attributesSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('attributes-search', {
                        query: event.target.value,
                    });
                });
            };

            bindAttributesToolbarSearch();
            document.addEventListener('livewire:navigated', bindAttributesToolbarSearch);

            Livewire.on('attributes-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-attribute-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:attributes.list-attributes />
</x-filament-panels::page>
