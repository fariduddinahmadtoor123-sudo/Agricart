<div class="agricart-categories agricart-customers">
    @if (session('customer_error'))
        <div class="agricart-portable-import__alert is-danger" role="alert">
            <p>{{ session('customer_error') }}</p>
        </div>
    @endif

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
            'hint' => __('customers.portable.hint'),
            'importLabel' => __('customers.actions.import_archive'),
            'cancelLabel' => __('customers.actions.remove'),
        ])
    @endif

    @if ($showMessagePanel)
        <div class="agricart-portable-import agricart-customer-message-panel" wire:key="customer-message-panel">
            <div class="agricart-customer-message-panel__card">
                <h3 class="agricart-portable-import__title">{{ __('customers.message.title') }}</h3>
                <p class="agricart-portable-import__hint">
                    {{ __('customers.message.to', ['name' => $messageCustomerLabel]) }}
                </p>
                <p class="agricart-portable-import__hint">{{ __('customers.message.hint') }}</p>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-customer-message-template">
                        {{ __('customers.message.template') }}
                    </label>
                    <select
                        id="agricart-customer-message-template"
                        class="agricart-customer-message-panel__input"
                        wire:model.live="messageTemplateId"
                    >
                        <option value="">{{ __('customers.message.template_custom') }}</option>
                        @foreach ($messageTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($messageTemplateId === null)
                    <div class="agricart-customer-message-panel__field">
                        <label class="agricart-form-panel__label" for="agricart-customer-message-template-name">
                            {{ __('customers.message.template_name') }}
                        </label>
                        <input
                            id="agricart-customer-message-template-name"
                            type="text"
                            class="agricart-customer-message-panel__input"
                            wire:model="messageTemplateName"
                            maxlength="120"
                            placeholder="{{ __('customers.message.template_name_placeholder') }}"
                        />
                        @error('messageTemplateName')
                            <p class="agricart-form-panel__hint is-danger">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-customer-message-subject">
                        {{ __('customers.message.subject') }}
                    </label>
                    <input
                        id="agricart-customer-message-subject"
                        type="text"
                        class="agricart-customer-message-panel__input"
                        wire:model="messageSubject"
                        maxlength="200"
                    />
                    @error('messageSubject')
                        <p class="agricart-form-panel__hint is-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="agricart-customer-message-panel__field">
                    <label class="agricart-form-panel__label" for="agricart-customer-message-body">
                        {{ __('customers.message.body') }}
                    </label>
                    <textarea
                        id="agricart-customer-message-body"
                        class="agricart-customer-message-panel__textarea"
                        rows="22"
                        cols="120"
                        wire:model="messageBody"
                        maxlength="5000"
                        dir="auto"
                    ></textarea>
                    @error('messageBody')
                        <p class="agricart-form-panel__hint is-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div class="agricart-portable-import__actions agricart-customer-message-panel__actions">
                    <button type="button" class="agricart-categories__action" wire:click="sendMessageToCustomer" wire:loading.attr="disabled">
                        {{ __('customers.message.send') }}
                    </button>
                    <button type="button" class="agricart-categories__action" wire:click="saveMessageTemplate" wire:loading.attr="disabled">
                        {{ __('customers.message.save_template') }}
                    </button>
                    <button type="button" class="agricart-categories__action" wire:click="closeMessagePanel">
                        {{ __('customers.actions.remove') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($customers->isEmpty())
        <div class="agricart-categories__empty">
            <p>{{ __('customers.list.empty') }}</p>
        </div>
    @else
        <div class="agricart-categories__table-wrap">
            <table class="agricart-categories__table">
                <thead>
                    <tr>
                        @if (in_array('customer_number', $visibleColumns, true))
                            <th>{{ __('customers.list.col_number') }}</th>
                        @endif
                        @if (in_array('name', $visibleColumns, true))
                            <th>{{ __('customers.list.col_name') }}</th>
                        @endif
                        @if (in_array('phone', $visibleColumns, true))
                            <th>{{ __('customers.list.col_phone') }}</th>
                        @endif
                        @if (in_array('channel', $visibleColumns, true))
                            <th>{{ __('customers.list.col_channel') }}</th>
                        @endif
                        @if (in_array('city', $visibleColumns, true))
                            <th>{{ __('customers.list.col_city') }}</th>
                        @endif
                        @if (in_array('balance', $visibleColumns, true))
                            <th>{{ __('customers.list.col_balance') }}</th>
                        @endif
                        @if (in_array('created_at', $visibleColumns, true))
                            <th>{{ __('customers.list.col_created') }}</th>
                        @endif
                        <th>{{ __('customers.list.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="agricart-categories__row">
                            @if (in_array('customer_number', $visibleColumns, true))
                                <td><code>{{ $customer->customer_number ?: '—' }}</code></td>
                            @endif
                            @if (in_array('name', $visibleColumns, true))
                                <td>{{ $customer->displayName() }}</td>
                            @endif
                            @if (in_array('phone', $visibleColumns, true))
                                <td>{{ $customer->primaryPhone() ?: '—' }}</td>
                            @endif
                            @if (in_array('channel', $visibleColumns, true))
                                <td>{{ $customer->channelLabel() }}</td>
                            @endif
                            @if (in_array('city', $visibleColumns, true))
                                <td>{{ $customer->displayCity() ?: '—' }}</td>
                            @endif
                            @if (in_array('balance', $visibleColumns, true))
                                <td>{{ number_format((float) $customer->opening_balance, 2) }}</td>
                            @endif
                            @if (in_array('created_at', $visibleColumns, true))
                                <td>{{ $customer->created_at?->format('M j, Y') }}</td>
                            @endif
                            <td class="agricart-categories__actions">
                                <button type="button" class="agricart-categories__action" wire:click="openView({{ $customer->id }})">{{ __('customers.actions.view_short') }}</button>
                                @if ($canEdit)
                                    <button type="button" class="agricart-categories__action" wire:click="openEdit({{ $customer->id }})">{{ __('customers.actions.edit_short') }}</button>
                                    @if ($customer->isOnline())
                                        <button type="button" class="agricart-categories__action" wire:click="openSendMessage({{ $customer->id }})">{{ __('customers.actions.send_message') }}</button>
                                    @endif
                                @endif
                                @if ($canDelete && $customer->isOffline())
                                    <button type="button" class="agricart-categories__action is-danger" wire:click="deleteCustomer({{ $customer->id }})" wire:confirm="{{ __('customers.actions.delete_confirm') }}">{{ __('customers.actions.delete_short') }}</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="agricart-categories__pagination">
            {{ $customers->links() }}
        </div>
    @endif
</div>
