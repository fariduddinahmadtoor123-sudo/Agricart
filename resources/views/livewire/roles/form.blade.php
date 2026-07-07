<div
    class="agricart-role-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('role-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:role-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:role-form-open.window="$wire.openForm($event.detail.roleId ?? null, $event.detail.mode ?? 'create')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-user-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    @if ($isSystem)
        <p class="agricart-form-panel__hint agricart-role-form__system-note">{{ __('agricart.roles.hints.super_admin_locked') }}</p>
    @endif

    <section class="agricart-form-panel__section agricart-user-form__section">
        <div class="agricart-user-form__identity-grid agricart-role-form__meta">
            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-role-name">
                    {{ __('agricart.roles.fields.name') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper>
                    <input id="agricart-role-name" type="text" wire:model="name" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-form-panel__field--full">
                <label class="agricart-form-panel__label" for="agricart-role-description">{{ __('agricart.roles.fields.description') }}</label>
                <x-filament::input.wrapper>
                    <textarea id="agricart-role-description" rows="2" wire:model="description" class="fi-input agricart-form-panel__textarea" @disabled($this->isReadonly())></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.roles.sections.permissions') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.roles.hints.permissions') }}</p>

        <div class="agricart-role-form__permission-groups">
            @foreach ($permissionGroups as $groupKey => $group)
                @php
                    $groupPermissionKeys = collect($group['permissions'])->pluck('key')->all();
                    $groupChecked = count(array_intersect($groupPermissionKeys, $selectedPermissions)) === count($groupPermissionKeys);
                    $groupLabel = app()->getLocale() === 'ur' && filled($group['label_ur'] ?? null)
                        ? $group['label_ur']
                        : $group['label_en'];
                @endphp
                <div class="agricart-role-form__permission-group" wire:key="role-permission-group-{{ $groupKey }}">
                    <label class="agricart-role-form__group-toggle">
                        <input
                            type="checkbox"
                            @checked($groupChecked)
                            wire:click="toggleGroup(@js($groupKey), $event.target.checked)"
                            @disabled($this->isReadonly())
                        />
                        <span>{{ $groupLabel }}</span>
                    </label>

                    <ul class="agricart-role-form__permission-list">
                        @foreach ($group['permissions'] as $permission)
                            @php
                                $permissionLabel = app()->getLocale() === 'ur' && filled($permission['label_ur'] ?? null)
                                    ? $permission['label_ur']
                                    : $permission['label_en'];
                            @endphp
                            <li wire:key="role-permission-{{ $permission['key'] }}">
                                <label class="agricart-role-form__permission-item">
                                    <input
                                        type="checkbox"
                                        value="{{ $permission['key'] }}"
                                        wire:model.live="selectedPermissions"
                                        @disabled($this->isReadonly())
                                    />
                                    <span>{{ $permissionLabel }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>
</div>
