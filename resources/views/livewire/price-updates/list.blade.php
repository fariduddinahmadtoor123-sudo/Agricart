<div class="agricart-categories agricart-price-updates">
    @if ($products->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ $hasListConstraints ? __('price_updates.list.no_results') : __('price_updates.list.empty') }}</p>
            @if ($canEdit && ! $hasListConstraints)
                <button type="button" class="agricart-categories__action" wire:click="openCreate">
                    {{ __('price_updates.actions.add') }}
                </button>
            @endif
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('thumb', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_thumb') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_name') }}</th>
                        @endif
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_code') }}</th>
                        @endif
                        @if (in_array('last_buy_price', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_buy') }}</th>
                        @endif
                        @if (in_array('retail_price', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_retail') }}</th>
                        @endif
                        @if (in_array('wholesale_price', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_wholesale') }}</th>
                        @endif
                        @if (in_array('super_wholesale_price', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_bulk') }}</th>
                        @endif
                        @if (in_array('distributor_price', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_distributor') }}</th>
                        @endif
                        @if (in_array('updated_at', $visibleColumns, true))
                            <th>{{ __('price_updates.list.col_updated') }}</th>
                        @endif
                        <th>{{ __('price_updates.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr wire:key="price-update-product-{{ $product->id }}" class="agricart-categories__row">
                            @if (in_array('thumb', $visibleColumns, true))
                                <td>
                                    @if ($url = $this->thumbUrl($product))
                                        <img src="{{ $url }}" alt="" class="agricart-categories__thumb" />
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>
                                    <strong>{{ $product->name_en }}</strong>
                                    @if (filled($product->name_ur))
                                        <div class="agricart-price-updates__meta" dir="rtl">{{ $product->name_ur }}</div>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $product->code }}</code></td>
                            @endif
                            @if (in_array('last_buy_price', $visibleColumns, true))
                                <td>{{ $product->last_buy_price ?? '—' }}</td>
                            @endif
                            @if (in_array('retail_price', $visibleColumns, true))
                                <td>{{ $product->retail_price ?? '—' }}</td>
                            @endif
                            @if (in_array('wholesale_price', $visibleColumns, true))
                                <td>{{ $product->wholesale_price ?? '—' }}</td>
                            @endif
                            @if (in_array('super_wholesale_price', $visibleColumns, true))
                                <td>{{ $product->super_wholesale_price ?? '—' }}</td>
                            @endif
                            @if (in_array('distributor_price', $visibleColumns, true))
                                <td>{{ $product->distributor_price ?? '—' }}</td>
                            @endif
                            @if (in_array('updated_at', $visibleColumns, true))
                                <td>{{ agricart_datetime($product->updated_at, 'd M Y H:i') }}</td>
                            @endif
                            <td>
                                <div class="agricart-categories__actions">
                                    @if ($canEdit)
                                        <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $product->id }})">
                                            {{ __('price_updates.actions.update') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $products->links() }}
        </div>
    @endif
</div>
