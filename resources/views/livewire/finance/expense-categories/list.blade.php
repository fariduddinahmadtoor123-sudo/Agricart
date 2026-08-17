<div class="agricart-categories agricart-finance-expense-categories">
    @if ($categories->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.finance.expense_categories.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_categories.list.col_code') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_categories.list.col_name') }}</th>
                        @endif
                        @if (in_array('kind', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_categories.list.col_kind') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.finance.expense_categories.list.col_status') }}</th>
                        @endif
                        <th>{{ __('agricart.finance.expense_categories.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr wire:key="expense-category-{{ $category->id }}" class="agricart-categories__row">
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $category->code }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $category->displayName() }}</td>
                            @endif
                            @if (in_array('kind', $visibleColumns, true))
                                <td>{{ __('agricart.finance.expense_categories.kinds.'.$category->kind) }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>
                                    @if ($category->isActive())
                                        {{ __('agricart.form_panel.statuses.active') }}
                                    @else
                                        {{ __('agricart.form_panel.statuses.inactive') }}
                                    @endif
                                </td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $category->id }})">{{ __('agricart.finance.expense_categories.actions.view_short') }}</button>
                                @endif
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $category->id }})">{{ __('agricart.finance.expense_categories.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteCategory({{ $category->id }})" wire:confirm="{{ __('agricart.finance.expense_categories.actions.delete_confirm') }}">{{ __('agricart.finance.expense_categories.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $categories->links() }}
        </div>
    @endif
</div>
