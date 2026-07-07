<div class="agricart-reorder">
    @if ($statusMessage)
        <div @class([
            'agricart-purchase-form__status',
            'is-success' => $statusType === 'success',
            'is-error' => $statusType === 'error',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    @if ($activeTab === 'reorder')
        @if ($reorderRows->isEmpty())
            <div class="agricart-purchase-form__empty"><p>{{ __('reorder.messages.empty_reorder') }}</p></div>
        @else
            <div class="agricart-reorder__grid">
                @foreach ($reorderRows as $index => $row)
                    <article
                        wire:key="reorder-card-{{ $row['product_id'] }}"
                        @class([
                            'agricart-reorder__card',
                            'is-selected' => in_array($row['product_id'], $selectedProductIds, true),
                            'is-queued' => $row['in_queue'],
                        ])
                    >
                        @if ($canCreate && $reorderFilter !== 'in_queue' && ! $row['in_queue'])
                            <label class="agricart-reorder__card-check">
                                <input
                                    type="checkbox"
                                    wire:click="toggleProduct({{ $row['product_id'] }})"
                                    @checked(in_array($row['product_id'], $selectedProductIds, true))
                                />
                            </label>
                        @endif

                        <div class="agricart-reorder__card-media">
                            <span class="agricart-reorder__card-sr">{{ $index + 1 }}</span>
                            @if ($thumb = $this->imageUrl($row['image_path']))
                                <img src="{{ $thumb }}" alt="" class="agricart-reorder__card-image" loading="lazy" />
                            @else
                                <div class="agricart-reorder__card-placeholder" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="agricart-reorder__card-body">
                            <h3 class="agricart-reorder__card-title">{{ $row['name'] }}</h3>

                            <dl class="agricart-reorder__card-meta">
                                <div>
                                    <dt>{{ __('reorder.columns.required_qty') }}</dt>
                                    <dd>{{ $row['required_qty'] ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('reorder.columns.previous_price') }}</dt>
                                    <dd><x-secret-price :value="$row['previous_price'] ?? ''" /></dd>
                                </div>
                                <div class="agricart-reorder__card-meta--wide">
                                    <dt>{{ __('reorder.columns.current_price') }}</dt>
                                    <dd class="agricart-reorder__blank-cell">—</dd>
                                </div>
                            </dl>

                            <div class="agricart-reorder__card-footer">
                                @if ($row['in_queue'])
                                    <span class="agricart-reorder__badge is-queue">{{ __('reorder.status.in_queue') }}</span>
                                @elseif ($row['send_count'] > 0)
                                    <span class="agricart-reorder__badge is-warning">{{ __('reorder.status.repeated', ['count' => $row['send_count']]) }}</span>
                                @else
                                    <span class="agricart-reorder__badge">{{ __('reorder.status.available') }}</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @else
        @if ($queueRows->isEmpty())
            <div class="agricart-purchase-form__empty"><p>{{ __('reorder.messages.empty_queue') }}</p></div>
        @else
            @php
                $queueList = $queueRows->values();
                $queueCount = $queueList->count();
                $useDualLayout = $queueCount >= 2;
                $queueHalf = (int) ceil($queueCount / 2);
                $queueColumns = $useDualLayout
                    ? [$queueList->slice(0, $queueHalf), $queueList->slice($queueHalf)]
                    : [$queueList];
            @endphp

            <div class="agricart-reorder__sheet">
                <div @class([
                    'agricart-reorder__sheet-dual',
                    'is-compact' => $useDualLayout,
                    'is-single' => ! $useDualLayout,
                ])>
                    @foreach ($queueColumns as $colIndex => $colRows)
                        @if ($colRows->isNotEmpty())
                            <div class="agricart-reorder__sheet-col">
                                @if ($useDualLayout)
                                    <p class="agricart-reorder__sheet-col-label">{{ $colIndex === 0 ? 'A' : 'B' }}</p>
                                @endif
                                <div class="agricart-reorder__sheet-table-wrap">
                                    <table class="agricart-reorder__sheet-table">
                                        <thead>
                                            <tr>
                                                <th>{{ __('reorder.columns.sr') }}</th>
                                                <th>{{ __('reorder.columns.thumb') }}</th>
                                                <th>{{ __('reorder.columns.product_name') }}</th>
                                                <th>{{ __('reorder.columns.required_qty') }}</th>
                                                <th>{{ __('reorder.columns.previous_price') }}</th>
                                                <th>{{ __('reorder.columns.batch') }}</th>
                                                <th>{{ __('reorder.columns.queued_at') }}</th>
                                                <th>{{ __('reorder.columns.send_count') }}</th>
                                                <th>{{ __('reorder.columns.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($colRows as $rowIndex => $item)
                                                <tr wire:key="queue-row-{{ $item->id }}">
                                                    <td>{{ $useDualLayout ? (($colIndex === 0 ? 0 : $queueHalf) + $rowIndex + 1) : ($rowIndex + 1) }}</td>
                                                    <td>
                                                        @if ($item->product && ($thumb = $this->imageUrl($item->product->image_path)))
                                                            <img src="{{ $thumb }}" alt="" class="agricart-reorder__sheet-thumb" loading="lazy" />
                                                        @endif
                                                    </td>
                                                    <td class="agricart-reorder__sheet-name">{{ $this->productName($item->product) }}</td>
                                                    <td>{{ $item->required_qty ?: '—' }}</td>
                                                    <td><x-secret-price :value="$item->previous_price" /></td>
                                                    <td><code>{{ $item->batch?->batch_number }}</code></td>
                                                    <td>{{ $item->queued_at?->format('d M Y') }}</td>
                                                    <td>{{ $item->send_count }}</td>
                                                    <td>
                                                        @if ($item->queued_at && $item->queued_at->lte(now()->subDays(7)))
                                                            <span class="agricart-reorder__badge is-warning">{{ __('reorder.status.stale') }}</span>
                                                        @else
                                                            <span class="agricart-reorder__badge is-queue">{{ __('reorder.status.pending') }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
