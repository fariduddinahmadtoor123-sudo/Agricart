@php
    use Filament\Support\Icons\Heroicon;

    $footerActions = \App\Filament\Layout\ModuleFormPanel::footerActions();

    $iconMap = [
        'sparkles' => Heroicon::OutlinedSparkles,
        'arrow-right' => Heroicon::OutlinedArrowRight,
        'check' => Heroicon::OutlinedCheck,
        'x-mark' => Heroicon::OutlinedXMark,
    ];
@endphp

<div class="agricart-form-panel__footer-inner">
    <div class="agricart-form-panel__progress" x-show="formKey !== 'categories' && formKey !== 'brands' && formKey !== 'units' && formKey !== 'packing-units' && formKey !== 'attributes' && formKey !== 'controls' && formKey !== 'products' && formKey !== 'users' && formKey !== 'staff' && formKey !== 'roles' && formKey !== 'company-profile' && formKey !== 'suppliers' && formKey !== 'customers' && formKey !== 'staff-salary' && formKey !== 'staff-attendance' && formKey !== 'purchase-planning' && formKey !== 'purchase-payment-summary' && formKey !== 'purchase-quotations' && formKey !== 'purchases' && formKey !== 'sales' && formKey !== 'sale-quotations' && formKey !== 'sale-returns' && formKey !== 'online-orders' && formKey !== 'price-controls' && formKey !== 'quantity-controls' && formKey !== 'secret-coding' && formKey !== 'sales-channel'" x-cloak>
        <div class="agricart-form-panel__progress-meta">
            <span
                class="agricart-form-panel__progress-label"
                x-text="@js(__('agricart.form_panel.step')).replace(':current', currentStep).replace(':total', totalSteps)"
            ></span>
            <span
                class="agricart-form-panel__progress-step-name"
                x-text="progressSteps[currentStep - 1] || ''"
            ></span>
            <span
                class="agricart-form-panel__progress-percent"
                x-text="progressPercent + '%'"
            ></span>
        </div>

        <div
            class="agricart-form-panel__progress-track"
            role="progressbar"
            x-bind:aria-valuenow="progressPercent"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="agricart-form-panel__progress-fill"
                x-bind:style="'width: ' + progressPercent + '%'"
            ></div>
        </div>

        <div class="agricart-form-panel__progress-dots">
            <template x-for="(step, index) in progressSteps" :key="index">
                <button
                    type="button"
                    class="agricart-form-panel__progress-dot"
                    x-bind:class="{ 'is-active': currentStep === index + 1, 'is-complete': currentStep > index + 1 }"
                    x-on:click="goToStep(index + 1)"
                    x-bind:title="step"
                ></button>
            </template>
        </div>
    </div>

    <div class="agricart-form-panel__actions">
        @foreach ($footerActions as $action)
            @php
                $icon = filled($action['icon']) ? ($iconMap[$action['icon']] ?? null) : null;
                $actionKey = $action['key'];
            @endphp

            @if ($actionKey === 'cancel')
                <x-filament::button
                    :color="$action['color']"
                    :outlined="$action['outlined']"
                    :icon="$icon"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn"
                    x-on:click="close()"
                >
                    {{ $action['label'] }}
                </x-filament::button>
            @elseif ($actionKey === 'ai')
                <x-filament::button
                    color="warning"
                    :icon="$iconMap['sparkles']"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn agricart-form-panel__action-btn--ai"
                    x-show="!['roles', 'purchase-planning', 'purchase-payment-summary', 'purchase-quotations', 'purchases', 'sales', 'sale-quotations', 'sale-returns', 'online-orders', 'price-controls', 'quantity-controls', 'secret-coding', 'sales-channel'].includes(formKey)"
                    x-cloak
                    x-bind:class="{ 'is-loading': (formKey === 'categories' && categoryAiEnriching) || (formKey === 'brands' && brandAiEnriching) || (formKey === 'units' && unitAiEnriching) || (formKey === 'packing-units' && packingUnitAiEnriching) || (formKey === 'attributes' && attributeAiEnriching) || (formKey === 'controls' && controlAiEnriching) || (formKey === 'products' && productAiEnriching) || (formKey === 'company-profile' && companyProfileAiEnriching) || (formKey === 'suppliers' && supplierAiEnriching) || (formKey === 'customers' && customerAiEnriching) || (formKey === 'users' && userAiEnriching) }"
                    x-bind:disabled="(formKey === 'categories' && categoryAiEnriching) || (formKey === 'brands' && brandAiEnriching) || (formKey === 'units' && unitAiEnriching) || (formKey === 'packing-units' && packingUnitAiEnriching) || (formKey === 'attributes' && attributeAiEnriching) || (formKey === 'controls' && controlAiEnriching) || (formKey === 'products' && productAiEnriching) || (formKey === 'company-profile' && companyProfileAiEnriching) || (formKey === 'suppliers' && supplierAiEnriching) || (formKey === 'customers' && customerAiEnriching) || (formKey === 'users' && userAiEnriching)"
                    x-bind:aria-busy="(formKey === 'categories' && categoryAiEnriching) || (formKey === 'brands' && brandAiEnriching) || (formKey === 'units' && unitAiEnriching) || (formKey === 'packing-units' && packingUnitAiEnriching) || (formKey === 'attributes' && attributeAiEnriching) || (formKey === 'controls' && controlAiEnriching) || (formKey === 'products' && productAiEnriching) || (formKey === 'company-profile' && companyProfileAiEnriching) || (formKey === 'suppliers' && supplierAiEnriching) || (formKey === 'customers' && customerAiEnriching) || (formKey === 'users' && userAiEnriching) ? 'true' : 'false'"
                    x-on:click="handleAction('ai')"
                >
                    <span class="agricart-form-panel__action-ai-inner">
                        <span class="agricart-form-panel__action-spinner" aria-hidden="true"></span>
                        <span class="agricart-form-panel__action-ai-label">{{ $action['label'] }}</span>
                        <span class="agricart-form-panel__action-ai-label agricart-form-panel__action-ai-label--busy">{{ __('agricart.categories.ai.enriching_short') }}</span>
                    </span>
                </x-filament::button>
            @elseif ($actionKey === 'save_next')
                <x-filament::button
                    :color="$action['color']"
                    :outlined="$action['outlined']"
                    :icon="$icon"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn"
                    x-show="formKey !== 'purchase-planning' && formKey !== 'purchase-payment-summary' && formKey !== 'purchase-quotations' && formKey !== 'purchases' && formKey !== 'sales' && formKey !== 'sale-quotations' && formKey !== 'sale-returns' && formKey !== 'online-orders' && formKey !== 'price-controls' && formKey !== 'quantity-controls' && formKey !== 'secret-coding' && formKey !== 'sales-channel' && formKey !== 'staff-salary' && formKey !== 'staff-attendance'"
                    x-cloak
                    x-on:click="handleAction('{{ $actionKey }}')"
                >
                    {{ $action['label'] }}
                </x-filament::button>
            @elseif ($actionKey === 'save_close')
                <x-filament::button
                    :color="$action['color']"
                    :outlined="$action['outlined']"
                    :icon="$icon"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn"
                    x-bind:class="{ 'is-loading': (formKey === 'purchase-planning' && purchasePlanningSaving) || (formKey === 'purchase-payment-summary' && purchasePaymentSummarySaving) || (formKey === 'purchase-quotations' && purchaseQuotationSaving) || (formKey === 'purchases' && purchaseSaving) || (formKey === 'sales' && saleSaving) || (formKey === 'sale-quotations' && saleQuotationSaving) || (formKey === 'sale-returns' && saleReturnSaving) || (formKey === 'online-orders' && onlineOrderSaving) || (formKey === 'price-controls' && priceControlSaving) || (formKey === 'quantity-controls' && quantityControlSaving) || (formKey === 'secret-coding' && secretCodingSaving) || (formKey === 'sales-channel' && salesChannelSaving) }"
                    x-bind:disabled="(formKey === 'purchase-planning' && purchasePlanningSaving) || (formKey === 'purchase-payment-summary' && purchasePaymentSummarySaving) || (formKey === 'purchase-quotations' && purchaseQuotationSaving) || (formKey === 'purchases' && purchaseSaving) || (formKey === 'sales' && saleSaving) || (formKey === 'sale-quotations' && saleQuotationSaving) || (formKey === 'sale-returns' && saleReturnSaving) || (formKey === 'online-orders' && onlineOrderSaving) || (formKey === 'price-controls' && priceControlSaving) || (formKey === 'quantity-controls' && quantityControlSaving) || (formKey === 'secret-coding' && secretCodingSaving)"
                    x-on:click="handleAction('{{ $actionKey }}')"
                >
                    <span x-show="formKey !== 'sales'">{{ $action['label'] }}</span>
                    <span x-show="formKey === 'sales'" x-cloak>{{ __('pos.actions.complete_sale') }}</span>
                </x-filament::button>
            @else
                <x-filament::button
                    :color="$action['color']"
                    :outlined="$action['outlined']"
                    :icon="$icon"
                    tag="button"
                    type="button"
                    class="agricart-form-panel__action-btn"
                    x-on:click="handleAction('{{ $actionKey }}')"
                >
                    {{ $action['label'] }}
                </x-filament::button>
            @endif
        @endforeach

        <x-filament::button
            color="warning"
            outlined
            tag="button"
            type="button"
            class="agricart-form-panel__action-btn"
            x-show="formKey === 'sales'"
            x-cloak
            x-bind:class="{ 'is-loading': saleSaving }"
            x-bind:disabled="saleSaving"
            x-on:click="handleAction('hold')"
        >
            {{ __('pos.actions.hold') }}
        </x-filament::button>

        <x-filament::button
            color="gray"
            outlined
            :icon="Heroicon::OutlinedPrinter"
            tag="button"
            type="button"
            class="agricart-form-panel__action-btn"
            x-show="formKey === 'purchase-planning' || formKey === 'purchase-payment-summary' || formKey === 'purchase-quotations' || formKey === 'purchases' || formKey === 'sales' || formKey === 'sale-quotations' || formKey === 'sale-returns' || formKey === 'online-orders'"
            x-cloak
            x-bind:class="{ 'is-loading': (formKey === 'purchase-planning' && purchasePlanningSaving) || (formKey === 'purchase-payment-summary' && purchasePaymentSummarySaving) || (formKey === 'purchase-quotations' && purchaseQuotationSaving) || (formKey === 'purchases' && purchaseSaving) || (formKey === 'sales' && saleSaving) || (formKey === 'sale-quotations' && saleQuotationSaving) || (formKey === 'sale-returns' && saleReturnSaving) || (formKey === 'online-orders' && onlineOrderSaving) }"
            x-bind:disabled="(formKey === 'purchase-planning' && purchasePlanningSaving) || (formKey === 'purchase-payment-summary' && purchasePaymentSummarySaving) || (formKey === 'purchase-quotations' && purchaseQuotationSaving) || (formKey === 'purchases' && purchaseSaving) || (formKey === 'sales' && saleSaving) || (formKey === 'sale-quotations' && saleQuotationSaving) || (formKey === 'sale-returns' && saleReturnSaving) || (formKey === 'online-orders' && onlineOrderSaving)"
            x-on:click="handleAction('print')"
        >
            <span x-show="formKey === 'purchase-planning'">{{ __('purchase_planning.actions.print') }}</span>
            <span x-show="formKey === 'purchase-payment-summary'">{{ __('purchase_payment_summary.actions.print') }}</span>
            <span x-show="formKey === 'purchase-quotations'">{{ __('purchase_quotations.actions.print') }}</span>
            <span x-show="formKey === 'purchases'">{{ __('purchases.actions.print') }}</span>
            <span x-show="formKey === 'sales'">{{ __('pos.actions.print') }}</span>
            <span x-show="formKey === 'sale-quotations'">{{ __('sale_quotations.actions.print') }}</span>
            <span x-show="formKey === 'sale-returns'">{{ __('sale_returns.actions.print') }}</span>
            <span x-show="formKey === 'online-orders'">{{ __('online_sale_orders.actions.print') }}</span>
        </x-filament::button>

        <x-filament::button
            color="gray"
            outlined
            :icon="Heroicon::OutlinedClipboardDocumentList"
            tag="button"
            type="button"
            class="agricart-form-panel__action-btn"
            x-show="formKey === 'online-orders'"
            x-cloak
            x-on:click="handleAction('packing_print')"
        >
            {{ __('online_sale_orders.actions.packing_slip') }}
        </x-filament::button>
    </div>
</div>
