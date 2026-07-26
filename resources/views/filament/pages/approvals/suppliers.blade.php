<x-filament-panels::page>
    @script
        <script>
            Livewire.on('supplier-approvals-filter', () => {});
        </script>
    @endscript

    <livewire:approvals.list-supplier-approvals />
</x-filament-panels::page>
