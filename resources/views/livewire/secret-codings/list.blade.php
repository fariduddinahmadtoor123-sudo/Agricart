<div class="agricart-categories agricart-secret-codings">
    @if (session('secret_coding_error'))
        <div class="agricart-user-form__alert is-danger">{{ session('secret_coding_error') }}</div>
    @endif

    @if ($records->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('secret_coding.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('profile_number', $visibleColumns, true))
                            <th>{{ __('secret_coding.list.col_number') }}</th>
                        @endif
                        @if (in_array('name_en', $visibleColumns, true))
                            <th>{{ __('secret_coding.list.col_name_en') }}</th>
                        @endif
                        @if (in_array('name_ur', $visibleColumns, true))
                            <th>{{ __('secret_coding.list.col_name_ur') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('secret_coding.list.col_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('secret_coding.list.col_created') }}</th>
                        @endif
                        <th>{{ __('secret_coding.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr wire:key="secret-coding-{{ $record->id }}" class="agricart-categories__row">
                            @if (in_array('profile_number', $visibleColumns, true))
                                <td><code>{{ $record->profile_number }}</code></td>
                            @endif
                            @if (in_array('name_en', $visibleColumns, true))
                                <td>{{ $record->name_en }}</td>
                            @endif
                            @if (in_array('name_ur', $visibleColumns, true))
                                <td>{{ $record->name_ur }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('secret_coding.statuses.' . $record->status) }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $record->created_at?->format('Y-m-d H:i') }}</td>
                            @endif
                            <td>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $record->id }})">
                                    {{ __('secret_coding.actions.edit_short') }}
                                </button>
                                <button
                                    type="button"
                                    class="agricart-categories__action is-danger"
                                    wire:click="deleteRecord({{ $record->id }})"
                                    wire:confirm="{{ __('secret_coding.messages.delete_confirm', ['name' => $record->name_en]) }}"
                                >
                                    {{ __('secret_coding.actions.delete_short') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $records->links() }}
        </div>
    @endif
</div>
