<div class="agricart-categories agricart-attributes">
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
            'hint' => __('agricart.catalog.portable.hint', ['entity' => __('agricart.catalog.portable.entities.attributes')]),
            'importLabel' => __('agricart.products.actions.import_archive'),
            'cancelLabel' => __('agricart.products.actions.remove'),
        ])
    @endif

    @if ($attributes->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.attributes.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('code', $visibleColumns, true))
                            <th>{{ __('agricart.attributes.list.col_code') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.attributes.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.attributes.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.attributes.list.col_status') }}</th>
                        @endif
                        @if (in_array('ai_status', $visibleColumns, true))
                            <th>{{ __('agricart.attributes.list.col_ai_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.attributes.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.attributes.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attributes as $attribute)
                        <tr wire:key="attribute-{{ $attribute->id }}" class="agricart-categories__row">
                            @if (in_array('code', $visibleColumns, true))
                                <td><code>{{ $attribute->code }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $attribute->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $attribute->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($attribute->status) }}</td>
                            @endif
                            @if (in_array('ai_status', $visibleColumns, true))
                                <td>{{ $attribute->ai_status?->label() ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $attribute->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)<button type="button" class="agricart-categories__action" wire:click="openView({{ $attribute->id }})">{{ __('agricart.attributes.actions.view_short') }}</button>@endif
                                @if ($canEdit)<button type="button" class="agricart-categories__action" wire:click="openEdit({{ $attribute->id }})">{{ __('agricart.attributes.actions.edit_short') }}</button>@endif
                                @if ($canDelete)<button type="button" class="agricart-categories__action is-danger" wire:click="deleteAttribute({{ $attribute->id }})" wire:confirm="{{ __('agricart.attributes.actions.delete_confirm') }}">{{ __('agricart.attributes.actions.delete_short') }}</button>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $attributes->links() }}
        </div>
    @endif
</div>
