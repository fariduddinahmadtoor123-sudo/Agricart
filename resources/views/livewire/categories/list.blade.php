@php use Illuminate\Support\Facades\Storage; @endphp
<div class="agricart-categories">
    @if ($categories->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.categories.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('image', $visibleColumns, true))
                            <th>{{ __('agricart.categories.list.col_image') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('agricart.categories.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('agricart.categories.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('slug', $visibleColumns, true))
                            <th>{{ __('agricart.categories.list.col_slug') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.categories.list.col_status') }}</th>
                        @endif
                        @if (in_array('ai_status', $visibleColumns, true))
                            <th>{{ __('agricart.categories.list.col_ai_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.categories.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.categories.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr wire:key="category-{{ $category->id }}" class="agricart-categories__row">
                            @if (in_array('image', $visibleColumns, true))
                                <td>
                                    @if ($category->image_path)
                                        <img src="{{ Storage::disk('public')->url($category->image_path) }}" alt="" class="agricart-categories__thumb" />
                                    @else
                                        <span class="agricart-categories__thumb-placeholder">—</span>
                                    @endif
                                </td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $category->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td dir="rtl">{{ $category->name_ur }}</td>
                            @endif
                            @if (in_array('slug', $visibleColumns, true))
                                <td><code>{{ $category->slug }}</code></td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ ucfirst($category->status) }}</td>
                            @endif
                            @if (in_array('ai_status', $visibleColumns, true))
                                <td>{{ $category->ai_status?->label() ?? '—' }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $category->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $category->id }})">{{ __('agricart.categories.actions.view_short') }}</button>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $category->id }})">{{ __('agricart.categories.actions.edit_short') }}</button>
                                <button type="button" class="agricart-categories__action is-danger" wire:click="deleteCategory({{ $category->id }})" wire:confirm="{{ __('agricart.categories.actions.delete_confirm') }}">{{ __('agricart.categories.actions.delete_short') }}</button>
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
