@php use Illuminate\Support\Facades\Storage; @endphp
<div class="agricart-categories agricart-store-items">
    <div class="agricart-categories__empty" style="margin-bottom: 0.75rem; padding: 0.75rem 1rem;">
        <p>{{ __('agricart.store.admin.items.hint') }}</p>
    </div>

    @if ($products->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.store.admin.items.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('image', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.items.col_image') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.items.col_name') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.items.col_status') }}</th>
                        @endif
                        @if (in_array('category', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.items.col_category') }}</th>
                        @endif
                        @if (in_array('display_in', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.items.col_display_in') }}</th>
                        @endif
                        @if (in_array('sku', $visibleColumns, true))
                            <th>{{ __('agricart.store.admin.items.col_sku') }}</th>
                        @endif
                        <th>{{ __('agricart.store.admin.items.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr wire:key="store-item-{{ $product->id }}" class="agricart-categories__row">
                            @if (in_array('image', $visibleColumns, true))
                                <td>
                                    @if ($product->image_path)
                                        <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="" class="agricart-categories__thumb" />
                                    @else
                                        <span class="agricart-categories__thumb-placeholder">—</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $product->name_en }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst((string) $product->status) }}</td>
                            @endif
                            @if (in_array('category', $visibleColumns, true))
                                <td>{{ $product->category?->name_en ?? '—' }}</td>
                            @endif
                            @if (in_array('display_in', $visibleColumns, true))
                                <td>
                                    @php
                                        $displayNames = $product->displayCategories
                                            ->pluck('name_en')
                                            ->filter()
                                            ->values();
                                    @endphp
                                    {{ $displayNames->isEmpty() ? '—' : $displayNames->implode(', ') }}
                                </td>
                            @endif
                            @if (in_array('sku', $visibleColumns, true))
                                <td><code>{{ $product->sku ?: ($product->code ?: '—') }}</code></td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $product->id }})">{{ __('agricart.products.actions.view_short') }}</button>
                                @endif
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $product->id }})">{{ __('agricart.products.actions.edit_short') }}</button>
                                @endif
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
