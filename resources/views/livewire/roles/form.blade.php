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
        <div class="agricart-role-form__meta">
            <div class="agricart-form-panel__field">
                <label class="agricart-form-panel__label" for="agricart-role-name">
                    {{ __('agricart.roles.fields.name') }}
                    <span class="agricart-form-panel__required">*</span>
                </label>
                <x-filament::input.wrapper class="agricart-role-form__input-wrap">
                    <input id="agricart-role-name" type="text" wire:model="name" class="fi-input" @disabled($this->isReadonly()) />
                </x-filament::input.wrapper>
            </div>

            <div class="agricart-form-panel__field agricart-role-form__description-field">
                <label class="agricart-form-panel__label" for="agricart-role-description">{{ __('agricart.roles.fields.description') }}</label>
                <x-filament::input.wrapper class="agricart-role-form__input-wrap">
                    <textarea
                        id="agricart-role-description"
                        rows="4"
                        dir="auto"
                        wire:model="description"
                        class="fi-input agricart-form-panel__textarea agricart-role-form__description"
                        @disabled($this->isReadonly())
                    ></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </section>

    <section class="agricart-form-panel__section agricart-user-form__section">
        <h3 class="agricart-form-panel__section-title">{{ __('agricart.roles.sections.permissions') }}</h3>
        <p class="agricart-form-panel__hint">{{ __('agricart.roles.hints.permissions') }}</p>
        <p class="agricart-role-form__legend">{{ __('agricart.roles.hints.permissions_legend') }}</p>

        <div class="agricart-role-form__permission-groups">
            @foreach ($permissionGroups as $groupKey => $group)
                @php
                    $groupPermissionKeys = collect($group['permissions'])->pluck('key')->all();
                    $selectedInGroup = count(array_intersect($groupPermissionKeys, $selectedPermissions));
                    $groupChecked = $selectedInGroup === count($groupPermissionKeys) && count($groupPermissionKeys) > 0;
                    $groupPartial = $selectedInGroup > 0 && ! $groupChecked;
                    $isUr = app()->getLocale() === 'ur';
                    $groupLabel = $isUr && filled($group['label_ur'] ?? null)
                        ? $group['label_ur']
                        : $group['label_en'];
                    $groupHint = $isUr && filled($group['hint_ur'] ?? null)
                        ? $group['hint_ur']
                        : ($group['hint_en'] ?? null);
                @endphp
                <div class="agricart-role-form__permission-group" wire:key="role-permission-group-{{ $groupKey }}">
                    <label class="agricart-role-form__group-toggle" title="{{ __('agricart.roles.hints.select_all_module') }}">
                        <input
                            type="checkbox"
                            @checked($groupChecked)
                            @if ($groupPartial) data-partial="1" @endif
                            wire:click="toggleGroup(@js($groupKey), $event.target.checked)"
                            @disabled($this->isReadonly())
                        />
                        <span>{{ $groupLabel }}</span>
                        @if ($groupPartial)
                            <span class="agricart-role-form__group-partial">{{ $selectedInGroup }}/{{ count($groupPermissionKeys) }}</span>
                        @endif
                    </label>

                    @if (filled($groupHint))
                        <p class="agricart-role-form__group-hint">{{ $groupHint }}</p>
                    @endif

                    @if (! empty($group['pages']))
                        <p class="agricart-role-form__pages-heading">{{ __('agricart.roles.hints.pages') }}</p>
                        <ul class="agricart-role-form__pages">
                            @foreach ($group['pages'] as $pageLabel)
                                <li class="agricart-role-form__page">{{ $pageLabel }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <ul class="agricart-role-form__permission-list">
                        @foreach ($group['permissions'] as $permission)
                            @php
                                $permissionLabel = $isUr && filled($permission['label_ur'] ?? null)
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
