<x-filament-panels::page>
    @script
        <script>
            Livewire.on('secret-codings-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-secret-coding-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:secret-codings.list-secret-codings />
</x-filament-panels::page>
