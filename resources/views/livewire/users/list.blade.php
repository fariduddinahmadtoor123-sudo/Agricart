<div class="agricart-categories agricart-users">
    @if ($flashMessage)
        <p class="agricart-form-panel__status is-{{ $flashType }}">{{ $flashMessage }}</p>
    @endif

    @if ($users->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.users.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('user_number', $visibleColumns, true))
                            <th>{{ __('agricart.users.list.col_user_number') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('agricart.users.list.col_name') }}</th>
                        @endif
                        @if (in_array('email', $visibleColumns, true))
                            <th>{{ __('agricart.users.list.col_email') }}</th>
                        @endif
                        @if (in_array('role', $visibleColumns, true))
                            <th>{{ __('agricart.users.list.col_role') }}</th>
                        @endif
                        @if (in_array('status', $visibleColumns, true))
                            <th>{{ __('agricart.users.list.col_status') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('agricart.users.list.col_created') }}</th>
                        @endif
                        <th>{{ __('agricart.users.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="agricart-categories__row">
                            @if (in_array('user_number', $visibleColumns, true))
                                <td><code>{{ $user->user_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $user->name }}</td>
                            @endif
                            @if (in_array('email', $visibleColumns, true))
                                <td>{{ $user->email }}</td>
                            @endif
                            @if (in_array('role', $visibleColumns, true))
                                <td>{{ $user->role?->name ?? '—' }}</td>
                            @endif
                            @if (in_array('status', $visibleColumns, true))
                                <td>{{ __('agricart.users.statuses.' . ($user->status ?? 'pending')) }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $user->created_at?->format('Y-m-d') }}</td>
                            @endif
                            <td>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $user->id }})">
                                    {{ __('agricart.users.actions.edit_short') }}
                                </button>
                                @if ($this->canArchiveUser($user))
                                    <button
                                        type="button"
                                        class="agricart-categories__action is-danger"
                                        wire:click="archiveUser({{ $user->id }})"
                                        wire:confirm="{{ __('agricart.users.actions.archive_confirm', ['name' => $user->name]) }}"
                                    >
                                        {{ __('agricart.users.actions.archive_short') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $users->links() }}
        </div>
    @endif
</div>
