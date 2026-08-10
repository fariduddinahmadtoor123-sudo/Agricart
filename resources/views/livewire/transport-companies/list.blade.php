<div class="agricart-categories agricart-suppliers agricart-transport-companies">
    @if ($companies->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('transport_companies.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('transport_code', $visibleColumns, true))
                            <th>{{ __('transport_companies.list.col_code') }}</th>
                        @endif
                        @if (in_array('business_name', $visibleColumns, true))
                            <th>{{ __('transport_companies.list.col_business') }}</th>
                        @endif
                        @if (in_array('contact_name', $visibleColumns, true))
                            <th>{{ __('transport_companies.list.col_contact') }}</th>
                        @endif
                        @if (in_array('transport_type', $visibleColumns, true))
                            <th>{{ __('transport_companies.list.col_type') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('transport_companies.list.col_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('transport_companies.list.col_created') }}</th>
                        @endif
                        <th>{{ __('transport_companies.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr wire:key="transport-{{ $company->id }}" class="agricart-categories__row">
                            @if (in_array('transport_code', $visibleColumns, true))
                                <td><code>{{ $company->transport_code }}</code></td>
                            @endif
                            @if (in_array('business_name', $visibleColumns, true))
                                <td>{{ $company->displayBusinessName() }}</td>
                            @endif
                            @if (in_array('contact_name', $visibleColumns, true))
                                <td>{{ $company->primaryContactName() ?: '—' }}</td>
                            @endif
                            @if (in_array('transport_type', $visibleColumns, true))
                                <td>{{ $company->transportTypeLabel() ?: '—' }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('transport_companies.statuses.' . $company->status) }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $company->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $company->id }})">{{ __('transport_companies.actions.view_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $company->id }})">{{ __('transport_companies.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteTransportCompany({{ $company->id }})" wire:confirm="{{ __('transport_companies.actions.delete_confirm') }}">{{ __('transport_companies.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $companies->links() }}
        </div>
    @endif
</div>
