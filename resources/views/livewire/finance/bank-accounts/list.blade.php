<div class="agricart-categories agricart-finance-banks">
    @if ($accounts->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.finance.banks.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('account_code', $visibleColumns, true))
                            <th>{{ __('agricart.finance.banks.list.col_code') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.finance.banks.list.col_name') }}</th>
                        @endif
                        @if (in_array('bank', $visibleColumns, true))
                            <th>{{ __('agricart.finance.banks.list.col_bank') }}</th>
                        @endif
                        @if (in_array('account_number', $visibleColumns, true))
                            <th>{{ __('agricart.finance.banks.list.col_account_number') }}</th>
                        @endif
                        @if (in_array('balance', $visibleColumns, true))
                            <th>{{ __('agricart.finance.banks.list.col_balance') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.finance.banks.list.col_status') }}</th>
                        @endif
                        <th>{{ __('agricart.finance.banks.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $account)
                        <tr wire:key="bank-{{ $account->id }}" class="agricart-categories__row">
                            @if (in_array('account_code', $visibleColumns, true))
                                <td><code>{{ $account->account_code ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $account->displayName() }}</td>
                            @endif
                            @if (in_array('bank', $visibleColumns, true))
                                <td>{{ $account->displayBankName() ?: '—' }}</td>
                            @endif
                            @if (in_array('account_number', $visibleColumns, true))
                                <td>{{ $account->displayAccountIdentifier() ?: '—' }}</td>
                            @endif
                            @if (in_array('balance', $visibleColumns, true))
                                <td><strong>{{ $money((float) ($balances[$account->id] ?? 0)) }}</strong></td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    @if ($account->isActive())
                                        {{ __('agricart.form_panel.statuses.active') }}
                                    @else
                                        {{ __('agricart.form_panel.statuses.inactive') }}
                                    @endif
                                </td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $account->id }})">{{ __('agricart.finance.banks.actions.edit_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="openHistory({{ $account->id }})">{{ __('agricart.finance.banks.actions.history_short') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $accounts->links() }}
        </div>
    @endif
</div>
