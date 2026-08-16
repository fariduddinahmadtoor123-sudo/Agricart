<div class="agricart-categories agricart-suppliers">
    @if (session('supplier_error'))
        <div class="agricart-portable-import__alert is-danger" role="alert">
            <p>{{ session('supplier_error') }}</p>
        </div>
    @endif

    @if (filled($portableStatus))
        <div @class([
            'agricart-portable-import__alert',
            'is-success' => ($portableStatusTone ?? 'success') === 'success',
            'is-danger' => ($portableStatusTone ?? 'success') === 'danger',
        ]) role="status">
            <p>{{ $portableStatus }}</p>
        </div>
    @endif

    @if ($showImportPanel)
        @include('livewire.partials.portable-import-panel', [
            'hint' => __('suppliers.portable.hint'),
            'importLabel' => __('suppliers.actions.import_archive'),
            'cancelLabel' => __('suppliers.actions.remove'),
        ])
    @endif

    @if ($suppliers->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('suppliers.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if ($canPrint)
                            <th class="agricart-categories__check-col"></th>
                        @endif
                        @if (in_array('supplier_code', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_code') }}</th>
                        @endif
                        @if (in_array('business_name', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_business') }}</th>
                        @endif
                        @if (in_array('contact_name', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_contact') }}</th>
                        @endif
                        @if (in_array('city', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_city') }}</th>
                        @endif
                        @if (in_array('supplier_type', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_type') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_status') }}</th>
                        @endif
                        @if (in_array('categories', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_categories') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('suppliers.list.col_created') }}</th>
                        @endif
                        <th>{{ __('suppliers.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suppliers as $supplier)
                        <tr wire:key="supplier-{{ $supplier->id }}" class="agricart-categories__row">
                            @if ($canPrint)
                                <td class="agricart-categories__check-col">
                                    <input type="checkbox" wire:model.live="selectedIds" value="{{ $supplier->id }}" aria-label="{{ $supplier->displayBusinessName() }}">
                                </td>
                            @endif
                            @if (in_array('supplier_code', $visibleColumns, true))
                                <td><code>{{ $supplier->supplier_code }}</code></td>
                            @endif
                            @if (in_array('business_name', $visibleColumns, true))
                                <td>{{ $supplier->displayBusinessName() }}</td>
                            @endif
                            @if (in_array('contact_name', $visibleColumns, true))
                                <td>{{ $supplier->primaryContactName() }}</td>
                            @endif
                            @if (in_array('city', $visibleColumns, true))
                                <td>{{ $supplier->displayCity() ?: '—' }}</td>
                            @endif
                            @if (in_array('supplier_type', $visibleColumns, true))
                                <td>{{ $supplier->supplierTypeLabel() }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('suppliers.statuses.' . $supplier->status) }}</td>
                            @endif
                            @if (in_array('categories', $visibleColumns, true))
                                <td>
                                    @if ($supplier->categories->isEmpty())
                                        —
                                    @else
                                        {{ $supplier->categories->map(fn ($category) => $categoryLabels[$category->id] ?? $category->name_en)->join(', ') }}
                                    @endif
                                </td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $supplier->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canPrint)
                                    <button type="button" class="agricart-categories__action" wire:click="printSupplier({{ $supplier->id }})">{{ __('suppliers.actions.print_short') }}</button>
                                @endif
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $supplier->id }})">{{ __('suppliers.actions.view_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $supplier->id }})">{{ __('suppliers.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteSupplier({{ $supplier->id }})" wire:confirm="{{ __('suppliers.actions.delete_confirm') }}">{{ __('suppliers.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('suppliers-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
