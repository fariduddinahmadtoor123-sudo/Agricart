<x-filament-panels::page>
    @script
        <script>
            const bindCategoriesToolbarSearch = () => {
                const input = document.getElementById('agricart-catalog-search-categories');

                if (! input || input.dataset.categoriesSearchBound === 'true') {
                    return;
                }

                input.dataset.categoriesSearchBound = 'true';

                input.addEventListener('input', (event) => {
                    window.Livewire.dispatch('categories-search', {
                        query: event.target.value,
                    });
                });
            };

            bindCategoriesToolbarSearch();
            document.addEventListener('livewire:navigated', bindCategoriesToolbarSearch);

            Livewire.on('categories-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-category-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });

            Livewire.on('category-form-scroll', (payload) => {
                const step = payload?.step ?? 1;
                const anchors = @js(\App\Filament\Forms\CategoryForm::stepScrollAnchors());
                const anchor = anchors[step] ?? 'hero';
                const target = document.getElementById('agricart-cat-section-' + anchor);

                if (! target) {
                    return;
                }

                const body = target.closest('.agricart-form-panel__body');

                if (body) {
                    body.scrollTo({ top: target.offsetTop - body.offsetTop - 8, behavior: 'smooth' });
                }
            });
        </script>
    @endscript

    <livewire:categories.list-categories />
</x-filament-panels::page>
