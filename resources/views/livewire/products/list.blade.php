@php use Illuminate\Support\Facades\Storage; @endphp
<div class="agricart-categories agricart-products">
    @if (session('product_error'))
        <div class="agricart-categories__empty" role="alert">
            <p>{{ session('product_error') }}</p>
        </div>
    @endif

    @if (filled($portableStatus))
        <div class="agricart-categories__empty" role="status">
            <p>{{ $portableStatus }}</p>
        </div>
    @endif

    @if ($showImportPanel)
        <div class="agricart-categories__empty" style="text-align:start">
            <p>{{ __('agricart.products.portable.hint') }}</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center;margin-top:0.75rem">
                <input type="file" wire:model="importArchive" accept=".ags,application/octet-stream" />
                <button type="button" class="agricart-categories__action" wire:click="importUploadedArchive" wire:loading.attr="disabled">
                    {{ __('agricart.products.actions.import_archive') }}
                </button>
                <button type="button" class="agricart-categories__action" wire:click="closeImportPanel">
                    {{ __('agricart.products.actions.remove') }}
                </button>
            </div>
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.products.list.empty') }}</p>
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
                        @if (in_array('label_code', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_label_code') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.products.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.products.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="agricart-categories__row">
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
                            @if (in_array('label_code', $visibleColumns, true))
                                <td><code>{{ $product->label_code ?? '—' }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $product->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $product->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($product->status) }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $product->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $product->id }})">{{ __('agricart.products.actions.view_short') }}</button>
                                @endif
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $product->id }})">{{ __('agricart.products.actions.edit_short') }}</button>
                                @endif
                                <a class="agricart-categories__action" href="{{ $this->storeUrl($product) }}" target="_blank" rel="noopener noreferrer">{{ __('agricart.products.actions.store_link') }}</a>
                                <a class="agricart-categories__action" href="{{ $this->whatsappShareUrl($product) }}" target="_blank" rel="noopener noreferrer">{{ __('agricart.products.actions.whatsapp_share') }}</a>
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteProduct({{ $product->id }})" wire:confirm="{{ __('agricart.products.actions.delete_confirm') }}">{{ __('agricart.products.actions.delete_short') }}</button>
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
