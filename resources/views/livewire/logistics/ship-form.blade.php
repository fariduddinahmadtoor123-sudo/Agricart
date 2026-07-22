<div
    class="agricart-logistics-ship-form agricart-category-form"
    x-data
    x-init="
        $watch('$wire.saving', (value) => {
            window.dispatchEvent(new CustomEvent('logistics-ship-form-busy', {
                detail: { saving: !!value },
                bubbles: true,
            }));
        });
    "
    x-on:logistics-ship-form-action.window="$wire.handlePanelAction($event.detail.action)"
    x-on:logistics-ship-form-open.window="$wire.openForm($event.detail.orderId ?? null, $event.detail.mode ?? 'edit')"
>
    @if ($statusMessage)
        <div @class([
            'agricart-category-form__alert',
            'is-success' => $statusType === 'success',
            'is-danger' => $statusType === 'danger',
            'is-warning' => $statusType === 'warning',
        ])>
            {{ $statusMessage }}
        </div>
    @endif

    <section class="agricart-form-panel__section">
        <h3 class="agricart-form-panel__section-title">{{ __('logistics_ship.sections.order') }}</h3>
        <p class="agricart-form-panel__help">
            <strong>{{ $orderNumber }}</strong>
            — {{ $customerLabel }}
        </p>
        @if ($itemLabels !== [])
            <ul class="agricart-form-panel__help" style="margin-top:0.5rem;padding-inline-start:1.25rem;">
                @foreach ($itemLabels as $label)
                    <li>{{ $label }}</li>
                @endforeach
            </ul>
        @endif
        <p class="agricart-form-panel__help">{{ __('logistics_ship.help.proof_rule') }}</p>
    </section>

    @forelse ($supplierParcels as $supplierId => $parcel)
        <section class="agricart-form-panel__section" wire:key="supplier-parcel-{{ $supplierId }}">
            <h3 class="agricart-form-panel__section-title">{{ $parcel['label'] ?? __('logistics_ship.labels.supplier_parcel') }}</h3>
            <div class="agricart-unit-form__grid">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-sup-carrier-{{ $supplierId }}">{{ __('logistics_ship.fields.carrier') }}</label>
                    <x-filament::input.wrapper>
                        <input id="ship-sup-carrier-{{ $supplierId }}" type="text" class="fi-input" wire:model="supplierParcels.{{ $supplierId }}.carrier_name" @disabled($this->isReadonly()) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-sup-tracking-{{ $supplierId }}">{{ __('logistics_ship.fields.tracking') }}</label>
                    <x-filament::input.wrapper>
                        <input id="ship-sup-tracking-{{ $supplierId }}" type="text" class="fi-input" wire:model="supplierParcels.{{ $supplierId }}.tracking_number" @disabled($this->isReadonly()) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-sup-bilty-{{ $supplierId }}">{{ __('logistics_ship.fields.bilty') }}</label>
                    <x-filament::input.wrapper>
                        <input id="ship-sup-bilty-{{ $supplierId }}" type="text" class="fi-input" wire:model="supplierParcels.{{ $supplierId }}.shipping_reference" @disabled($this->isReadonly()) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-sup-proof-{{ $supplierId }}">{{ __('logistics_ship.fields.proof') }}</label>
                    @if (! $this->isReadonly())
                        <input id="ship-sup-proof-{{ $supplierId }}" type="file" accept="image/*,.pdf" wire:model="supplierProofUploads.{{ $supplierId }}" class="fi-input" />
                    @endif
                    @if (filled($parcel['existing_proof'] ?? null))
                        <p class="agricart-form-panel__help">{{ __('logistics_ship.help.proof_on_file') }}</p>
                    @endif
                </div>
            </div>
        </section>
    @empty
    @endforelse

    @foreach ($staffParcels as $assignmentId => $parcel)
        <section class="agricart-form-panel__section" wire:key="staff-parcel-{{ $assignmentId }}">
            <h3 class="agricart-form-panel__section-title">{{ $parcel['label'] ?? __('logistics_ship.labels.staff_parcel') }}</h3>
            <div class="agricart-unit-form__grid">
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-staff-carrier-{{ $assignmentId }}">{{ __('logistics_ship.fields.carrier') }}</label>
                    <x-filament::input.wrapper>
                        <input id="ship-staff-carrier-{{ $assignmentId }}" type="text" class="fi-input" wire:model="staffParcels.{{ $assignmentId }}.carrier_name" @disabled($this->isReadonly()) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-staff-tracking-{{ $assignmentId }}">{{ __('logistics_ship.fields.tracking') }}</label>
                    <x-filament::input.wrapper>
                        <input id="ship-staff-tracking-{{ $assignmentId }}" type="text" class="fi-input" wire:model="staffParcels.{{ $assignmentId }}.tracking_number" @disabled($this->isReadonly()) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-staff-bilty-{{ $assignmentId }}">{{ __('logistics_ship.fields.bilty') }}</label>
                    <x-filament::input.wrapper>
                        <input id="ship-staff-bilty-{{ $assignmentId }}" type="text" class="fi-input" wire:model="staffParcels.{{ $assignmentId }}.shipping_reference" @disabled($this->isReadonly()) />
                    </x-filament::input.wrapper>
                </div>
                <div class="agricart-form-panel__field">
                    <label class="agricart-form-panel__label" for="ship-staff-proof-{{ $assignmentId }}">{{ __('logistics_ship.fields.proof') }}</label>
                    @if (! $this->isReadonly())
                        <input id="ship-staff-proof-{{ $assignmentId }}" type="file" accept="image/*,.pdf" wire:model="staffProofUploads.{{ $assignmentId }}" class="fi-input" />
                    @endif
                    @if (filled($parcel['existing_proof'] ?? null))
                        <p class="agricart-form-panel__help">{{ __('logistics_ship.help.proof_on_file') }}</p>
                    @endif
                </div>
            </div>
        </section>
    @endforeach

    @if ($supplierParcels === [] && $staffParcels === [])
        <p class="agricart-form-panel__help">{{ __('logistics_ship.messages.no_parcels') }}</p>
    @endif
</div>
