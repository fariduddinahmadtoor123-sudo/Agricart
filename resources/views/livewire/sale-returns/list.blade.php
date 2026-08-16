<div class="agricart-categories agricart-purchases">
    @if (session('sale_return_error'))
        <div class="agricart-categories__empty" role="alert">
            <p>{{ session('sale_return_error') }}</p>
        </div>
    @endif

    @if ($activeFilters !== [])
        <div class="agricart-list-filters">
            @foreach ($activeFilters as $filter)
                <button
                    type="button"
                    class="agricart-list-filters__chip"
                    wire:click="removeFilter('{{ $filter['field'] }}')"
                    wire:key="sale-return-filter-{{ $filter['field'] }}"
                >
                    <span>{{ $filter['label'] }}</span>
                    <span aria-hidden="true">×</span>
                </button>
            @endforeach

            @if ($hasListConstraints)
                <button type="button" class="agricart-list-filters__clear" wire:click="clearFilters">
                    {{ __('sale_returns.list.clear_filters') }}
                </button>
            @endif
        </div>
    @endif

    @if ($pendingRequests->isNotEmpty())
        <div class="agricart-categories__table-wrap agricart-sale-return-requests">
            <h2 class="agricart-sale-return-requests__title">{{ __('sale_returns.requests.heading') }}</h2>
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('sale_returns.requests.col_number') }}</th>
                        <th>{{ __('sale_returns.requests.col_invoice') }}</th>
                        <th>{{ __('sale_returns.requests.col_order') }}</th>
                        <th>{{ __('sale_returns.requests.col_customer') }}</th>
                        <th>{{ __('sale_returns.requests.col_items') }}</th>
                        <th>{{ __('sale_returns.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingRequests as $request)
                        <tr wire:key="online-return-request-{{ $request->id }}">
                            <td><code>{{ $request->request_number }}</code></td>
                            <td><code>{{ $request->order?->sale?->sale_number ?? '—' }}</code></td>
                            <td>{{ $request->order?->order_number ?? '—' }}</td>
                            <td>{{ $request->customer?->displayLabel() ?? '—' }}</td>
                            <td>{{ $request->items->sum('quantity') }}</td>
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openRequest({{ $request->id }})">{{ __('sale_returns.requests.view') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($viewingRequest)
        <div class="agricart-categories__table-wrap agricart-sale-return-requests__detail" wire:key="online-return-request-detail-{{ $viewingRequest->id }}">
            <div class="agricart-sale-return-requests__detail-head">
                <h2 class="agricart-sale-return-requests__title">{{ __('sale_returns.requests.detail_heading', ['number' => $viewingRequest->request_number]) }}</h2>
                <button type="button" class="agricart-categories__action" wire:click="closeRequest">{{ __('sale_returns.requests.close') }}</button>
            </div>

            <dl class="agricart-sale-return-requests__meta">
                <div>
                    <dt>{{ __('sale_returns.requests.col_invoice') }}</dt>
                    <dd><code>{{ $viewingRequest->order?->sale?->sale_number ?? '—' }}</code></dd>
                </div>
                <div>
                    <dt>{{ __('sale_returns.requests.col_order') }}</dt>
                    <dd>{{ $viewingRequest->order?->order_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sale_returns.requests.col_customer') }}</dt>
                    <dd>{{ $viewingRequest->customer?->displayLabel() ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('sale_returns.requests.col_note') }}</dt>
                    <dd>{{ $viewingRequest->customer_note ?: '—' }}</dd>
                </div>
            </dl>

            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('sale_returns.requests.col_product') }}</th>
                        <th>{{ __('sale_returns.requests.col_qty') }}</th>
                        <th>{{ __('sale_returns.requests.col_reason') }}</th>
                        <th>{{ __('sale_returns.requests.col_photo') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($viewingRequest->items as $line)
                        <tr>
                            <td>{{ $line->orderItem?->displayName() ?: ($line->product?->name_en ?? '—') }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ''), '0'), '.') }}</td>
                            <td>{{ __('agricart.customer.returns.reasons.'.$line->reason) }}</td>
                            <td>
                                @if ($line->photoUrl())
                                    <a href="{{ $line->photoUrl() }}" target="_blank" rel="noopener">{{ __('sale_returns.requests.open_photo') }}</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">{{ __('sale_returns.requests.errors.items_required') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="agricart-categories__actions agricart-sale-return-requests__detail-actions">
                @if ($canApprove)
                    <button
                        type="button"
                        class="agricart-categories__action is-primary"
                        wire:click="approveRequest({{ $viewingRequest->id }})"
                        wire:confirm="{{ __('sale_returns.requests.approve_confirm') }}"
                    >{{ __('sale_returns.requests.approve') }}</button>
                @endif
                @if ($canEdit)
                    @if ($rejectingRequestId === $viewingRequest->id)
                        <div class="agricart-sale-return-requests__reject">
                            <textarea wire:model="rejectNote" rows="2" placeholder="{{ __('sale_returns.requests.reject_note') }}"></textarea>
                            <button type="button" class="agricart-categories__action" wire:click="rejectRequest({{ $viewingRequest->id }})">{{ __('sale_returns.requests.reject_save') }}</button>
                            <button type="button" class="agricart-categories__action" wire:click="cancelRejectRequest">{{ __('sale_returns.requests.reject_cancel') }}</button>
                        </div>
                    @else
                        <button type="button" class="agricart-categories__action" wire:click="startRejectRequest({{ $viewingRequest->id }})">{{ __('sale_returns.requests.reject') }}</button>
                    @endif
                @endif
            </div>
        </div>
    @endif

    @if ($returns->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('sale_returns.list.no_results') : __('sale_returns.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('return_number', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_number') }}</th>
                        @endif
                        @if (in_array('sale_number', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_sale_number') }}</th>
                        @endif
                        @if (in_array('customer', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_customer') }}</th>
                        @endif
                        @if (in_array('refund_total', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_refund_total') }}</th>
                        @endif
                        @if (in_array('payment_status', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_payment_status') }}</th>
                        @endif
                        @if (in_array('payment_method', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_payment_method') }}</th>
                        @endif
                        @if (in_array('return_date', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_return_date') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('sale_returns.list.col_created') }}</th>
                        @endif
                        <th>{{ __('sale_returns.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($returns as $return)
                        <tr wire:key="sale-return-{{ $return->id }}">
                            @if (in_array('return_number', $visibleColumns, true))
                                <td><code>{{ $return->return_number }}</code></td>
                            @endif
                            @if (in_array('sale_number', $visibleColumns, true))
                                <td><code>{{ $return->sale?->sale_number ?? '—' }}</code></td>
                            @endif
                            @if (in_array('customer', $visibleColumns, true))
                                <td>{{ $return->customer?->displayLabel() ?? '—' }}</td>
                            @endif
                            @if (in_array('refund_total', $visibleColumns, true))
                                <td>{{ number_format((float) $return->refund_total, 2) }}</td>
                            @endif
                            @if (in_array('payment_status', $visibleColumns, true))
                                <td>{{ __('pos.payment_statuses.' . $return->payment_status) }}</td>
                            @endif
                            @if (in_array('payment_method', $visibleColumns, true))
                                <td>{{ __('pos.payment_methods.' . $return->payment_method) }}</td>
                            @endif
                            @if (in_array('return_date', $visibleColumns, true))
                                <td>{{ $return->return_date?->format('M j, Y') }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $return->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $return->id }})">{{ __('sale_returns.actions.view_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $return->id }})">{{ __('sale_returns.actions.edit_short') }}</button>
                                @endif
                                <button type="button" class="agricart-categories__action" wire:click="printReturn({{ $return->id }})">{{ __('sale_returns.actions.print_short') }}</button>
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteReturn({{ $return->id }})" wire:confirm="{{ __('sale_returns.actions.delete_confirm') }}">{{ __('sale_returns.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $returns->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('sale-returns-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url;

        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
