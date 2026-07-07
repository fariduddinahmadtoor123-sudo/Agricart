<x-filament-panels::page>
    @script
        <script>
            Livewire.on('users-columns-sync', (payload) => {
                const columns = payload?.columns ?? [];

                document.querySelectorAll('[data-user-column]').forEach((input) => {
                    input.checked = columns.includes(input.value);
                });
            });
        </script>
    @endscript

    <livewire:users.list-users />
</x-filament-panels::page>
