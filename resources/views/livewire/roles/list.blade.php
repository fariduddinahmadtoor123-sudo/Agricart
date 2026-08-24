<div class="agricart-categories agricart-roles">
    @if (session('role_error'))
        <div class="agricart-user-form__alert is-danger">{{ session('role_error') }}</div>
    @endif

    @if ($roles->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('agricart.roles.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.roles.list.col_name') }}</th>
                        <th>{{ __('agricart.roles.list.col_permissions') }}</th>
                        <th>{{ __('agricart.roles.list.col_type') }}</th>
                        <th>{{ __('agricart.roles.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr wire:key="role-{{ $role->id }}" class="agricart-categories__row">
                            <td>{{ $role->name }}</td>
                            <td>{{ number_format($role->permissions_count) }}</td>
                            <td>
                                @if ($role->is_system)
                                    <span class="agricart-roles__badge">{{ __('agricart.roles.list.system') }}</span>
                                @else
                                    {{ __('agricart.roles.list.custom') }}
                                @endif
                            </td>
                            <td>
                                <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $role->id }})">
                                    {{ $role->is_system ? __('agricart.roles.actions.view') : __('agricart.roles.actions.edit_short') }}
                                </button>
                                @unless ($role->is_system)
                                    @if ($canDeleteRoles)
                                    <button
                                        type="button"
                                        class="agricart-categories__action is-danger"
                                        wire:click="deleteRole({{ $role->id }})"
                                        wire:confirm="{{ __('agricart.roles.messages.delete_confirm', ['name' => $role->name]) }}"
                                    >
                                        {{ __('agricart.roles.actions.delete_short') }}
                                    </button>
                                    @endif
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $roles->links() }}
        </div>
    @endif
</div>
