<div class="agricart-store-admin">
    @include('livewire.store.partials.status')

    <form wire:submit="saveSettings" class="agricart-store-admin__card agricart-store-admin__form">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.ticker.settings') }}</h3>

        <label class="agricart-store-admin__checkbox">
            <input type="checkbox" wire:model="ticker_enabled" />
            <span>{{ __('agricart.store.admin.ticker.enabled') }}</span>
        </label>

        <div class="agricart-store-admin__field">
            <label>{{ __('agricart.store.admin.ticker.speed') }} ({{ $ticker_speed }})</label>
            <input type="range" wire:model="ticker_speed" min="10" max="100" step="5" class="agricart-store-admin__range" />
            <p class="agricart-store-admin__hint">{{ __('agricart.store.admin.ticker.speed_hint') }}</p>
        </div>

        <button type="submit" class="agricart-store-admin__btn">{{ __('agricart.store.admin.save') }}</button>
    </form>

    <section class="agricart-store-admin__card">
        <h3 class="agricart-store-admin__title">{{ __('agricart.store.admin.ticker.messages') }}</h3>

        <form wire:submit="saveMessage" class="agricart-store-admin__form">
            <div class="agricart-store-admin__grid-2">
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.text_en') }}</label>
                    <input type="text" wire:model="text_en" class="agricart-store-admin__input" required />
                </div>
                <div class="agricart-store-admin__field">
                    <label>{{ __('agricart.store.admin.fields.text_ur') }}</label>
                    <input type="text" wire:model="text_ur" class="agricart-store-admin__input" dir="rtl" />
                </div>
            </div>
            <div class="agricart-store-admin__field">
                <label>{{ __('agricart.store.admin.ticker.link') }}</label>
                <input type="url" wire:model="link" class="agricart-store-admin__input" placeholder="https://" />
            </div>
            <div class="agricart-store-admin__actions">
                <button type="submit" class="agricart-store-admin__btn">
                    {{ $editingId ? __('agricart.store.admin.update') : __('agricart.store.admin.add') }}
                </button>
                @if ($editingId)
                    <button type="button" wire:click="resetMessageForm" class="agricart-store-admin__btn agricart-store-admin__btn--muted">
                        {{ __('agricart.store.admin.cancel') }}
                    </button>
                @endif
            </div>
        </form>

        <div class="agricart-store-admin__table-wrap">
            <table class="agricart-store-admin__table">
                <thead>
                    <tr>
                        <th>{{ __('agricart.store.admin.fields.text_en') }}</th>
                        <th>{{ __('agricart.store.admin.fields.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr wire:key="ticker-{{ $message->id }}">
                            <td>{{ $message->text_en }}</td>
                            <td>{{ $message->is_active ? __('agricart.store.admin.active') : __('agricart.store.admin.inactive') }}</td>
                            <td class="agricart-store-admin__row-actions">
                                <button type="button" wire:click="editMessage({{ $message->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.edit') }}</button>
                                <button type="button" wire:click="toggleMessage({{ $message->id }})" class="agricart-store-admin__link-btn">{{ __('agricart.store.admin.toggle') }}</button>
                                <button type="button" wire:click="deleteMessage({{ $message->id }})" wire:confirm="{{ __('agricart.store.admin.delete_confirm') }}" class="agricart-store-admin__link-btn is-danger">{{ __('agricart.store.admin.delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">{{ __('agricart.store.admin.ticker.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
