@php use Illuminate\Support\Facades\Storage; @endphp
<div class="agricart-categories agricart-product-agricart-reviews">
    @if ($products->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.products.review_page.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('image', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_image') }}</th>
                        @endif
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_code') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('brand', $visibleColumns, true))
                            <th>{{ __('agricart.products.fields.brand') }}</th>
                        @endif
                        @if (in_array('agricart_rating', $visibleColumns, true))
                            <th>{{ __('agricart.products.review_page.list.col_rating') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_status') }}</th>
                        @endif
                        @if (in_array('updated_at', $visibleColumns, true))
                            <th>{{ __('agricart.products.review_page.list.col_updated') }}</th>
                        @endif
                        <th>{{ __('agricart.products.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr wire:key="product-agricart-review-{{ $product->id }}" class="agricart-categories__row">
                            @if (in_array('image', $visibleColumns, true))
                                <td>
                                    @if ($product->image_path)
                                        <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="" class="agricart-categories__thumb" />
                                    @else
                                        <span class="agricart-categories__thumb-placeholder">—</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $product->code }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $product->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $product->name_ur }}</td>
                            @endif
                            @if (in_array('brand', $visibleColumns, true))
                                <td>{{ $product->brand?->name_en ?: '—' }}</td>
                            @endif
                            @if (in_array('agricart_rating', $visibleColumns, true))
                                <td>
                                    <strong>{{ $product->agricart_rating }}</strong>
                                    <span class="agricart-product-agricart-reviews__score-of">/ {{ $scoreMax }}</span>
                                </td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($product->status) }}</td>
                            @endif
                            @if (in_array('updated_at', $visibleColumns, true))
                                <td>{{ $product->updated_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $product->id }})">{{ __('agricart.products.actions.view_short') }}</button>
                                @endif
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $product->id }})">{{ __('agricart.products.actions.edit_short') }}</button>
                                    <button
                                        type="button"
                                        class="agricart-categories__action is-danger"
                                        wire:click="clearReview({{ $product->id }})"
                                        wire:confirm="{{ __('agricart.products.review_page.actions.clear_confirm') }}"
                                    >{{ __('agricart.products.review_page.actions.clear_short') }}</button>
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
