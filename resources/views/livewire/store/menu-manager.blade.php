<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="saveSettings" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.menu.settings') }}</h3>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="menu_enabled" />
            <span>{{ __('agricart.store.admin.menu.enabled') }}</span>
        </label>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="menu_show_home" />
            <span>{{ __('agricart.store.admin.menu.show_home') }}</span>
        </label>

        <div class="agricart-store-admin__grid-2">
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.menu.home_en') }}</label>
                <input type="text" wire:model="menu_home_label_en" class="agricart-store-admin__input" />
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.menu.home_ur') }}</label>
                <input type="text" wire:model="menu_home_label_ur" class="agricart-store-admin__input" dir="rtl" />
            </div>
        </div>

        <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
    </form>

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.menu.items') }}</h3>

        <form wire:submit="saveItem" class="agricart-store-admin__form">
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.label_en') }}</label>
                    <input type="text" wire:model="label_en" class="agricart-store-admin__input" required />
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.label_ur') }}</label>
                    <input type="text" wire:model="label_ur" class="agricart-store-admin__input" dir="rtl" />
                </div>
            </div>
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.menu.url') }}</label>
                    <input type="text" wire:model="url" class="agricart-store-admin__input" placeholder="/about" />
                    @error('url') <span class="agricart-store-admin__error">{{ $message }}</span> @enderror
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.menu.category') }}</label>
                    <select wire:model="category_id" class="agricart-store-admin__input">
                        <option value="">{{ __('agricart.store.admin.menu.no_category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name_en }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label class="agricart-store-admin__checkbox">
                <input type="checkbox" wire:model="opens_in_new_tab" />
                <span>{{ __('agricart.store.admin.menu.new_tab') }}</span>
            </label>
            <div class="agricart-store-admin__actions">
                <button type="submit" class="agricart-store-admin__btn">
                    {{ $editingId ? __('agricart.store.admin.update') : __('agricart.store.admin.add') }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="resetItemForm" class="agricart-store-admin__btn agricart-store-admin__btn--muted">{{ __('agricart.store.admin.cancel') }}</button>
                @endif
            </div>
        </form>

        <div class="agricart-store-admin__table-wrap">
            <table class="agricart-store-admin__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.store.admin.fields.label_en') }}</th>
                        <th>{{ __('agricart.store.admin.menu.link') }}</th>
                        <th>{{ __('agricart.store.admin.fields.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr wire:key="menu-{{ $item->id }}">
                            <td>{{ $item->label_en }}</td>
                            <td>{{ $item->category ? $item->category->name_en : ($item->url ?? '—') }}</td>
                            <td>{{ $item->is_active ? __('agricart.store.admin.active') : __('agricart.store.admin.inactive') }}</td>
                            <td class="agricart-store-admin__row-actions">
                                <button type="button" wire:click="editItem({{ $item->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.edit') }}</button>
                                <button type="button" wire:click="toggleItem({{ $item->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.toggle') }}</button>
                                <button type="button" wire:click="deleteItem({{ $item->id }})" wire:confirm="{{ __('agricart.store.admin.delete_confirm') }}" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('agricart.store.admin.menu.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
