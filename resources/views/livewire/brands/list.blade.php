@php use Illuminate\Support\Facades\Storage; @endphp
<div class="agricart-categories agricart-brands">
    @if (filled($portableStatus))
        <div @class([
            'agricart-portable-import__alert',
            'is-success' => ($portableStatusTone ?? 'success') === 'success',
            'is-danger' => ($portableStatusTone ?? 'success') === 'danger',
        ]) role="status">
            <p>{{ $portableStatus }}</p>
        </div>
    @endif

    @if ($showImportPanel)
        @include('livewire.partials.portable-import-panel', [
            'hint' => __('agricart.catalog.portable.hint', ['entity' => __('agricart.catalog.portable.entities.brands')]),
            'importLabel' => __('agricart.products.actions.import_archive'),
            'cancelLabel' => __('agricart.products.actions.remove'),
        ])
    @endif

    @if ($brands->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.brands.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('image', $visibleColumns, true))
                            <th>{{ __('agricart.brands.list.col_image') }}</th>
                        @endif
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('agricart.brands.list.col_code') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.brands.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.brands.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.brands.list.col_status') }}</th>
                        @endif
                        @if (in_array('ai_status', $visibleColumns, true))
                            <th>{{ __('agricart.brands.list.col_ai_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.brands.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.brands.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($brands as $brand)
                        <tr wire:key="brand-{{ $brand->id }}" class="agricart-categories__row">
                            @if (in_array('image', $visibleColumns, true))
                                <td>
                                    @if ($brand->image_path)
                                        <img src="{{ Storage::disk('public')->url($brand->image_path) }}" alt="" class="agricart-categories__thumb" />
                                    @else
                                        <span class="agricart-categories__thumb-placeholder">—</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $brand->code }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $brand->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $brand->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($brand->status) }}</td>
                            @endif
                            @if (in_array('ai_status', $visibleColumns, true))
                                <td>{{ $brand->ai_status?->label() ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $brand->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $brand->id }})">{{ __('agricart.brands.actions.view_short') }}</button>
                                    <button type="button" class="agricart-categories__action" wire:click="printBrand({{ $brand->id }})">{{ __('agricart.brands.actions.print_short') }}</button>
                                @endif
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $brand->id }})">{{ __('agricart.brands.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteBrand({{ $brand->id }})" wire:confirm="{{ __('agricart.brands.actions.delete_confirm') }}">{{ __('agricart.brands.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $brands->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.on('brands-open-print', (payload) => {
        const url = payload?.url ?? payload?.[0]?.url ?? null;
        if (url) {
            window.open(url, '_blank');
        }
    });
</script>
@endscript
