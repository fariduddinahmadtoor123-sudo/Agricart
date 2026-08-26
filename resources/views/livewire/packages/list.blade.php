@php use Illuminate\Support\Facades\Storage; @endphp
<div class="agricart-categories agricart-packages">
    @if ($packages->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.packages.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('image', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_image') }}</th>
                        @endif
                        @if (in_array('package_number', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_package_number') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('discount_percent', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_discount') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_status') }}</th>
                        @endif
                        @if (in_array('sort_order', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_sort_order') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.packages.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.packages.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($packages as $package)
                        <tr wire:key="package-{{ $package->id }}" class="agricart-categories__row">
                            @if (in_array('image', $visibleColumns, true))
                                <td>
                                    @if ($package->image_path)
                                        <img src="{{ Storage::disk('public')->url($package->image_path) }}" alt="" class="agricart-categories__thumb" />
                                    @else
                                        <span class="agricart-categories__thumb-placeholder">—</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('package_number', $visibleColumns, true))
                                <td><code>{{ $package->package_number }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>
                                    {{ $package->name_en }}
                                    <span class="agricart-form-panel__hint">{{ __('agricart.packages.list.items_count', ['count' => $package->items_count]) }}</span>
                                </td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $package->name_ur ?: '—' }}</td>
                            @endif
                            @if (in_array('discount_percent', $visibleColumns, true))
                                <td>{{ rtrim(rtrim(number_format((float) $package->discount_percent, 2), '0'), '.') }}%</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($package->status) }}</td>
                            @endif
                            @if (in_array('sort_order', $visibleColumns, true))
                                <td>{{ $package->sort_order }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $package->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                @if ($canView)
                                    <button type="button" class="agricart-categories__action" wire:click="openView({{ $package->id }})">{{ __('agricart.packages.actions.view_short') }}</button>
                                @endif
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $package->id }})">{{ __('agricart.packages.actions.edit_short') }}</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deletePackage({{ $package->id }})" wire:confirm="{{ __('agricart.packages.actions.delete_confirm') }}">{{ __('agricart.packages.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $packages->links() }}
        </div>
    @endif
</div>
