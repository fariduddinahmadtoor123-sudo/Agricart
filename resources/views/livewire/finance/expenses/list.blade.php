<div class="agricart-categories agricart-finance-expenses">
    @if ($expenses->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.finance.expenses.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('expense_number', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expenses.list.col_number') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expenses.list.col_name') }}</th>
                        @endif
                        @if (in_array('category', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expenses.list.col_category') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expenses.list.col_status') }}</th>
                        @endif
                        <th>{{ __('agricart.finance.expenses.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expenses as $expense)
                        <tr wire:key="expense-{{ $expense->id }}" class="agricart-categories__row">
                            @if (in_array('expense_number', $visibleColumns, true))
                                <td><code>{{ $expense->expense_number }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $expense->displayName() }}</td>
                            @endif
                            @if (in_array('category', $visibleColumns, true))
                                <td>{{ $expense->category?->displayName() ?? '—' }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    @if ($expense->isActive())
                                        {{ __('agricart.form_panel.statuses.active') }}
                                    @else
                                        {{ __('agricart.form_panel.statuses.inactive') }}
                                    @endif
                                </td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $expense->id }})">
                                    {{ __('agricart.finance.expenses.actions.edit_short') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $expenses->links() }}
        </div>
    @endif
</div>
