<x-filament-panels::page>
    @script
        <script>
            Livewire.on('staff-approvals-filter', () => {});
        </script>
    @endscript

    <livewire:approvals.list-staff-approvals />
</x-filament-panels::page>
