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
                        @if (in_array('schedule', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expenses.list.col_schedule') }}</th>
                        @endif
                        @if (in_array('estimated_cost', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expenses.list.col_estimated') }}</th>
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
                            @if (in_array('schedule', $visibleColumns, true))
                                @php
                                    $dueStatus = $expense->dueStatus();
                                    $openCycle = $schedule->oldestOpenCycle($expense);
                                    $openCount = count($schedule->openCycles($expense));
                                @endphp
                                <td>
                                    @if ($expense->issue_day)
                                        {{ __('agricart.finance.expenses.list.schedule', [
                                            'issue' => $expense->issue_day,
                                            'due' => $expense->due_day ?? '—',
                                        ]) }}
                                    @else
                                        —
                                    @endif
                                    @if ($dueStatus)
                                        <div @class([
                                            'agricart-finance-expense-due',
                                            'is-issued' => $dueStatus === \App\Models\Expense::DUE_ISSUED,
                                            'is-due-soon' => $dueStatus === \App\Models\Expense::DUE_SOON,
                                            'is-overdue' => $dueStatus === \App\Models\Expense::DUE_OVERDUE,
                                            'is-paid' => $dueStatus === \App\Models\Expense::DUE_PAID,
                                        ])>
                                            {{ __('agricart.finance.expenses.due.statuses.'.$dueStatus) }}
                                            @if ($openCycle)
                                                · {{ $openCycle['issue_on']->toDateString() }}
                                            @endif
                                            @if ($openCount > 1)
                                                · {{ __('agricart.finance.expenses.list.open_cycles', ['count' => $openCount]) }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('estimated_cost', $visibleColumns, true))
                                <td class="is-numeric">
                                    @if ($expense->estimated_cost !== null)
                                        {{ $money((float) $expense->estimated_cost) }}
                                    @else
                                        —
                                    @endif
                                </td>
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
                                @if ($canEdit && $schedule->oldestOpenCycle($expense) !== null)
                                    <button type="button" class="agricart-categories__action" wire:click="markCycleDone({{ $expense->id }})" wire:confirm="{{ __('agricart.finance.expenses.actions.mark_done_confirm') }}">
                                        {{ __('agricart.finance.expenses.actions.mark_done') }}
                                    </button>
                                @endif
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
