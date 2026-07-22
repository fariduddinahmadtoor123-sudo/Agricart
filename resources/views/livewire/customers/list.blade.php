<div class="agricart-categories agricart-customers">
    @if (session('customer_error'))
        <div class="agricart-portable-import__alert is-danger" role="alert">
            <p>{{ session('customer_error') }}</p>
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
            'hint' => __('customers.portable.hint'),
            'importLabel' => __('customers.actions.import_archive'),
            'cancelLabel' => __('customers.actions.remove'),
        ])
    @endif

    @if ($customers->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('customers.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('customer_number', $visibleColumns, true))
                            <th>{{ __('customers.list.col_number') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('customers.list.col_name') }}</th>
                        @endif
                        @if (in_array('phone', $visibleColumns, true))
                            <th>{{ __('customers.list.col_phone') }}</th>
                        @endif
                        @if (in_array('channel', $visibleColumns, true))
                            <th>{{ __('customers.list.col_channel') }}</th>
                        @endif
                        @if (in_array('city', $visibleColumns, true))
                            <th>{{ __('customers.list.col_city') }}</th>
                        @endif
                        @if (in_array('balance', $visibleColumns, true))
                            <th>{{ __('customers.list.col_balance') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('customers.list.col_created') }}</th>
                        @endif
                        <th>{{ __('customers.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="agricart-categories__row">
                            @if (in_array('customer_number', $visibleColumns, true))
                                <td><code>{{ $customer->customer_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $customer->displayName() }}</td>
                            @endif
                            @if (in_array('phone', $visibleColumns, true))
                                <td>{{ $customer->primaryPhone() ?: '—' }}</td>
                            @endif
                            @if (in_array('channel', $visibleColumns, true))
                                <td>{{ $customer->channelLabel() }}</td>
                            @endif
                            @if (in_array('city', $visibleColumns, true))
                                <td>{{ $customer->displayCity() ?: '—' }}</td>
                            @endif
                            @if (in_array('balance', $visibleColumns, true))
                                <td>{{ number_format((float) $customer->opening_balance, 2) }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $customer->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $customer->id }})">{{ __('customers.actions.view_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $customer->id }})">{{ __('customers.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete && $customer->isOffline())
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteCustomer({{ $customer->id }})" wire:confirm="{{ __('customers.actions.delete_confirm') }}">{{ __('customers.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $customers->links() }}
        </div>
    @endif
</div>
