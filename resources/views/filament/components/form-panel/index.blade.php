@php
    use App\Filament\Clusters\Purchasing\PurchasesPage;
    use App\Filament\Clusters\Sales\PosSalesPage;
    use App\Filament\Forms\CategoryForm;
    use Filament\Support\Icons\Heroicon;

    $progressSteps = \App\Filament\Layout\ModuleFormPanel::progressSteps();
    $formPanelDefaults = [
        'addRecord' => __('agricart.form_panel.add_record'),
        'record' => __('agricart.form_panel.record'),
        'minimize' => __('agricart.form_panel.minimize'),
        'stretch' => __('agricart.form_panel.stretch'),
        'maximize' => __('agricart.form_panel.maximize'),
        'close' => __('agricart.form_panel.close'),
        'restore' => __('agricart.form_panel.restore'),
    ];
    $quickFormOpenPaths = [
        'sale' => parse_url(PosSalesPage::getUrl(), PHP_URL_PATH) ?: '',
        'purchase' => parse_url(PurchasesPage::getUrl(), PHP_URL_PATH) ?: '',
    ];
    $quickFormOpenPanels = [
        'sale' => [
            'title' => __('pos.actions.add'),
            'resource' => PosSalesPage::getNavigationLabel(),
            'formKey' => 'sales',
        ],
        'purchase' => [
            'title' => __('purchases.actions.add'),
            'resource' => PurchasesPage::getNavigationLabel(),
            'formKey' => 'purchases',
        ],
    ];
@endphp

<div
    x-data="{
        isOpen: false,
        minimized: false,
        backdropPressed: false,
        size: 'normal',
        title: @js($formPanelDefaults['addRecord']),
        resourceLabel: @js($formPanelDefaults['record']),
        formKey: null,
        pendingOpenDetail: null,
        lastFocusedElement: null,
        currentStep: 1,
        progressSteps: @js($progressSteps),
        defaults: @js($formPanelDefaults),
        categoryVisualMapping: { slots: [null, null, null, null, null], selected: null },
        categoryFaqs: [],
        categoryAiEnriching: false,
        categorySaving: false,
        brandAiEnriching: false,
        brandSaving: false,
        productReviewAiEnriching: false,
        productReviewSaving: false,
        unitAiEnriching: false,
        unitSaving: false,
        packingUnitAiEnriching: false,
        packingUnitSaving: false,
        attributeAiEnriching: false,
        attributeSaving: false,
        controlAiEnriching: false,
        controlSaving: false,
        productAiEnriching: false,
        productSaving: false,
        companyProfileAiEnriching: false,
        companyProfileSaving: false,
        supplierAiEnriching: false,
        supplierSaving: false,
        customerAiEnriching: false,
        customerSaving: false,
        transportCompanyAiEnriching: false,
        transportCompanySaving: false,
        userAiEnriching: false,
        userSaving: false,
        purchasePlanningSaving: false,
        purchasePaymentSummarySaving: false,
        purchaseQuotationSaving: false,
        purchaseSaving: false,
        priceUpdateSaving: false,
        saleSaving: false,
        saleQuotationSaving: false,
        saleReturnSaving: false,
        onlineOrderSaving: false,
        priceControlSaving: false,
        quantityControlSaving: false,
        secretCodingSaving: false,
        salesChannelSaving: false,
        staffAdvanceSaving: false,
        financeBankSaving: false,
        financeFundTransferSaving: false,
        financeExpenseCategorySaving: false,
        contactMessageTemplateSaving: false,
        financeExpenseSaving: false,
        financeExpenseAiEnriching: false,
        financeExpensePaymentSaving: false,
        controlOpenSignature: null,
        formMode: 'create',
        onlineOrderContext: null,
        onlineOrderId: null,
        roleSaving: false,
        selectCategoryVisualIcon(index) {
            this.categoryVisualMapping.selected = index;
        },
        addCategoryFaq() {
            this.categoryFaqs.push({ question_en: '', question_ur: '', answer_en: '', answer_ur: '' });
        },
        removeCategoryFaq(index) {
            this.categoryFaqs.splice(index, 1);
        },
        resetCategoryForm() {
            this.categoryVisualMapping = { slots: [null, null, null, null, null], selected: null };
            this.categoryFaqs = [];
        },
        get totalSteps() {
            return this.progressSteps.length;
        },
        get progressPercent() {
            return Math.round((this.currentStep / this.totalSteps) * 100);
        },
        open(detail = {}) {
            if (Array.isArray(detail)) {
                detail = detail[0] ?? {};
            }

            const incomingFormKey = detail.formKey ?? null;
            const incomingMode = ['create', 'edit', 'view'].includes(detail.mode) ? detail.mode : 'create';
            if (! this.isOpen && ! detail.__formMounted) {
                this.lastFocusedElement = document.activeElement;
            }
            const controlSignature = incomingFormKey === 'controls'
                ? `${detail.controlId ?? 'new'}:${incomingMode}`
                : null;

            if (this.isOpen && controlSignature !== null && this.controlOpenSignature === controlSignature) {
                return;
            }

            this.title = detail.title ?? this.defaults.addRecord;
            this.resourceLabel = detail.resource ?? this.defaults.record;
            this.formKey = incomingFormKey;
            this.formMode = incomingMode;
            this.onlineOrderContext = detail.onlineOrderContext ?? null;
            this.onlineOrderId = detail.orderId ?? null;
            this.controlOpenSignature = controlSignature;
            this.currentStep = 1;

            if (! detail.__formMounted && incomingFormKey) {
                this.pendingOpenDetail = { ...detail };
                this.size = this.preferredPanelSize(incomingFormKey);
                this.isOpen = true;
                this.minimized = false;
                this.backdropPressed = false;
                document.body.classList.add('agricart-form-panel-open');
                window.Livewire.dispatch('form-panel-mount', { formKey: incomingFormKey, mode: incomingMode });
                return;
            }

            if (detail.__formMounted) {
                this.pendingOpenDetail = null;
            }

            if (this.formKey === 'categories') {
                this.resetCategoryForm();
                window.dispatchEvent(new CustomEvent('category-form-open', {
                    detail: {
                        categoryId: detail.categoryId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'brands') {
                window.dispatchEvent(new CustomEvent('brand-form-open', {
                    detail: {
                        brandId: detail.brandId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'product-reviews') {
                window.dispatchEvent(new CustomEvent('product-review-form-open', {
                    detail: {
                        productId: detail.productId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'units') {
                window.dispatchEvent(new CustomEvent('unit-form-open', {
                    detail: {
                        unitId: detail.unitId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'packing-units') {
                window.dispatchEvent(new CustomEvent('packing-unit-form-open', {
                    detail: {
                        packingUnitId: detail.packingUnitId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'attributes') {
                window.dispatchEvent(new CustomEvent('attribute-form-open', {
                    detail: {
                        attributeId: detail.attributeId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'controls') {
                window.dispatchEvent(new CustomEvent('control-form-open', {
                    detail: {
                        controlId: detail.controlId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'products') {
                window.dispatchEvent(new CustomEvent('product-form-open', {
                    detail: {
                        productId: detail.productId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'labels') {
                window.dispatchEvent(new CustomEvent('label-print-form-open', {
                    detail: {},
                    bubbles: true,
                }));
            }

            if (this.formKey === 'users' || this.formKey === 'staff') {
                const formContext = detail.formContext ?? (this.formKey === 'staff' ? 'staff' : 'settings');
                if (this.formKey === 'staff') {
                    this.formKey = 'users';
                }
                window.dispatchEvent(new CustomEvent('user-form-open', {
                    detail: {
                        userId: detail.userId ?? null,
                        mode: detail.mode ?? 'create',
                        formContext,
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'roles') {
                window.dispatchEvent(new CustomEvent('role-form-open', {
                    detail: {
                        roleId: detail.roleId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'company-profile') {
                window.dispatchEvent(new CustomEvent('company-profile-form-open', {
                    detail: {},
                    bubbles: true,
                }));
            }

            if (this.formKey === 'suppliers') {
                window.dispatchEvent(new CustomEvent('supplier-form-open', {
                    detail: {
                        supplierId: detail.supplierId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'customers') {
                window.dispatchEvent(new CustomEvent('customer-form-open', {
                    detail: {
                        customerId: detail.customerId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'transport-companies') {
                window.dispatchEvent(new CustomEvent('transport-company-form-open', {
                    detail: {
                        transportCompanyId: detail.transportCompanyId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'staff-salary') {
                window.dispatchEvent(new CustomEvent('staff-salary-form-open', {
                    detail: {
                        userId: detail.userId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'staff-attendance') {
                window.dispatchEvent(new CustomEvent('staff-attendance-form-open', {
                    detail: {
                        attendanceDate: detail.attendanceDate ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'staff-advance') {
                window.dispatchEvent(new CustomEvent('staff-advance-form-open', {
                    detail: {
                        defaultType: detail.defaultType ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'staff-leave') {
                window.dispatchEvent(new CustomEvent('staff-leave-form-open', {
                    detail: {
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'staff-payroll') {
                window.dispatchEvent(new CustomEvent('staff-payroll-form-open', {
                    detail: {
                        payrollMonth: detail.payrollMonth ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'finance-bank') {
                window.dispatchEvent(new CustomEvent('finance-bank-form-open', {
                    detail: {
                        bankAccountId: detail.bankAccountId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'finance-fund-transfer') {
                window.dispatchEvent(new CustomEvent('finance-fund-transfer-form-open', {
                    detail: {
                        transferId: detail.transferId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'finance-expense-category') {
                window.dispatchEvent(new CustomEvent('finance-expense-category-form-open', {
                    detail: {
                        categoryId: detail.categoryId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'contact-message-templates') {
                window.dispatchEvent(new CustomEvent('contact-message-template-form-open', {
                    detail: {
                        templateId: detail.templateId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'finance-expense') {
                window.dispatchEvent(new CustomEvent('finance-expense-form-open', {
                    detail: {
                        expenseId: detail.expenseId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'finance-expense-payment') {
                window.dispatchEvent(new CustomEvent('finance-expense-payment-form-open', {
                    detail: {
                        paymentId: detail.paymentId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'purchase-planning') {
                window.dispatchEvent(new CustomEvent('purchase-planning-form-open', {
                    detail: {
                        sheetId: detail.sheetId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'purchase-payment-summary') {
                window.dispatchEvent(new CustomEvent('purchase-payment-summary-form-open', {
                    detail: {
                        summaryId: detail.summaryId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'purchase-quotations') {
                window.dispatchEvent(new CustomEvent('purchase-quotation-form-open', {
                    detail: {
                        quotationId: detail.quotationId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'purchases') {
                window.dispatchEvent(new CustomEvent('purchase-form-open', {
                    detail: {
                        purchaseId: detail.purchaseId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'price-update') {
                window.dispatchEvent(new CustomEvent('price-update-form-open', {
                    detail: {
                        productId: detail.productId ?? null,
                        mode: detail.mode ?? 'edit',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'sales') {
                window.dispatchEvent(new CustomEvent('sale-form-open', {
                    detail: {
                        saleId: detail.saleId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'sale-quotations') {
                window.dispatchEvent(new CustomEvent('sale-quotation-form-open', {
                    detail: {
                        quotationId: detail.quotationId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'sale-returns') {
                window.dispatchEvent(new CustomEvent('sale-return-form-open', {
                    detail: {
                        returnId: detail.returnId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'online-orders') {
                window.dispatchEvent(new CustomEvent('online-order-form-open', {
                    detail: {
                        orderId: detail.orderId ?? null,
                        mode: detail.mode ?? 'view',
                        onlineOrderContext: detail.onlineOrderContext ?? null,
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'price-controls') {
                window.dispatchEvent(new CustomEvent('price-control-form-open', {
                    detail: {
                        tierId: detail.tierId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'quantity-controls') {
                window.dispatchEvent(new CustomEvent('quantity-control-form-open', {
                    detail: {
                        tierId: detail.tierId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'secret-coding') {
                window.dispatchEvent(new CustomEvent('secret-coding-form-open', {
                    detail: {
                        secretCodingId: detail.secretCodingId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'sales-channel') {
                window.dispatchEvent(new CustomEvent('sales-channel-form-open', {
                    detail: {
                        salesChannelId: detail.salesChannelId ?? null,
                        mode: detail.mode ?? 'create',
                        channelSlug: detail.channelSlug ?? null,
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'delivery-methods') {
                window.dispatchEvent(new CustomEvent('delivery-method-form-open', {
                    detail: {
                        deliveryMethodId: detail.deliveryMethodId ?? null,
                        mode: detail.mode ?? 'create',
                    },
                    bubbles: true,
                }));
            }

            if (this.formKey === 'logistics-ship') {
                window.dispatchEvent(new CustomEvent('logistics-ship-form-open', {
                    detail: {
                        orderId: detail.orderId ?? null,
                        mode: detail.mode ?? 'edit',
                    },
                    bubbles: true,
                }));
            }

            this.isOpen = true;
            this.minimized = false;
            this.backdropPressed = false;
            this.size = this.preferredPanelSize(this.formKey);
            document.body.classList.add('agricart-form-panel-open');
            this.$nextTick(() => this.focusPanel());

            if (this.formKey === 'ai-log-detail' && detail.logId) {
                window.Livewire.dispatch('ai-log-detail-open', { logId: detail.logId });
            }

            if (this.formKey === 'activity-log-detail' && detail.logId) {
                window.Livewire.dispatch('activity-log-detail-open', { logId: detail.logId });
            }

            if (this.formKey === 'system-error-detail') {
                if (detail.errorId) {
                    window.Livewire.dispatch('system-error-detail-open', { errorId: detail.errorId });
                } else if (detail.fileKey) {
                    window.Livewire.dispatch('system-error-file-detail-open', { fileKey: detail.fileKey });
                }
            }

            if (this.formKey === 'media-library') {
                window.Livewire.dispatch('media-form-open', {
                    assetId: detail.assetId ?? null,
                    mode: detail.mode ?? 'create',
                });
            }
        },
        forwardQueuedOrder() {
            const orderId = this.onlineOrderId;

            if (! orderId) {
                return;
            }

            this.close(false);
            this.$nextTick(() => {
                window.Livewire.dispatch('order-dispatch-open-forward', { orderId: Number(orderId) });
            });
        },
        close(notify = true) {
            if (! this.isOpen && ! this.minimized) {
                return;
            }

            this.isOpen = false;
            this.minimized = false;
            this.backdropPressed = false;
            this.size = 'normal';
            this.formKey = null;
            this.onlineOrderContext = null;
            this.onlineOrderId = null;
            this.categoryAiEnriching = false;
            this.categorySaving = false;
            this.brandAiEnriching = false;
            this.brandSaving = false;
            this.productReviewAiEnriching = false;
            this.productReviewSaving = false;
            this.unitAiEnriching = false;
            this.unitSaving = false;
            this.packingUnitAiEnriching = false;
            this.packingUnitSaving = false;
            this.attributeAiEnriching = false;
            this.attributeSaving = false;
            this.controlAiEnriching = false;
            this.controlSaving = false;
            this.controlOpenSignature = null;
            this.companyProfileAiEnriching = false;
            this.companyProfileSaving = false;
            this.supplierAiEnriching = false;
            this.supplierSaving = false;
            this.customerAiEnriching = false;
            this.customerSaving = false;
            this.transportCompanyAiEnriching = false;
            this.transportCompanySaving = false;
            this.userAiEnriching = false;
            this.userSaving = false;
            this.purchasePlanningSaving = false;
            this.purchasePaymentSummarySaving = false;
            this.purchaseQuotationSaving = false;
            this.purchaseSaving = false;
            this.priceUpdateSaving = false;
            this.saleSaving = false;
            this.saleQuotationSaving = false;
            this.saleReturnSaving = false;
            this.onlineOrderSaving = false;
            this.priceControlSaving = false;
            this.quantityControlSaving = false;
            this.secretCodingSaving = false;
            this.salesChannelSaving = false;
            this.staffAdvanceSaving = false;
            this.financeBankSaving = false;
            this.financeFundTransferSaving = false;
            this.financeExpenseCategorySaving = false;
            this.contactMessageTemplateSaving = false;
            this.financeExpenseSaving = false;
            this.financeExpenseAiEnriching = false;
            this.financeExpensePaymentSaving = false;
            document.body.classList.remove('agricart-form-panel-open');
            window.Livewire.dispatch('form-panel-unmount');

            const focusTarget = this.lastFocusedElement;
            this.lastFocusedElement = null;
            this.pendingOpenDetail = null;
            this.$nextTick(() => {
                if (focusTarget?.isConnected && typeof focusTarget.focus === 'function') {
                    focusTarget.focus();
                }
            });

            if (notify) {
                window.dispatchEvent(new CustomEvent('close-form-panel', { bubbles: true }));
            }
        },
        minimize() {
            this.minimized = true;
        },
        restore() {
            this.minimized = false;
        },
        toggleMaximize() {
            this.size = this.size === 'maximized' ? 'normal' : 'maximized';
            this.minimized = false;
        },
        toggleStretch() {
            this.size = this.size === 'stretched' ? 'normal' : 'stretched';
            this.minimized = false;
        },
        goToStep(step) {
            this.currentStep = Math.min(Math.max(step, 1), this.totalSteps);

            if (this.formKey === 'categories') {
                this.$nextTick(() => this.scrollToCategorySection(this.currentStep));
            }
        },
        scrollToCategorySection(step) {
            const anchors = @js(CategoryForm::stepScrollAnchors());
            const anchor = anchors[step] ?? 'hero';
            const target = document.getElementById('agricart-cat-section-' + anchor);

            if (! target) {
                return;
            }

            const body = target.closest('.agricart-form-panel__body');

            if (body) {
                body.scrollTo({
                    top: target.offsetTop - body.offsetTop - 8,
                    behavior: 'smooth',
                });

                return;
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        handleAction(key) {
            if (this.formKey === 'categories') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') {
                    return;
                }

                if ((key === 'save_close' || key === 'save_next') && this.categorySaving) {
                    return;
                }

                if (key === 'ai' && this.categoryAiEnriching) {
                    return;
                }

                if (key === 'ai') {
                    this.categoryAiEnriching = true;
                }

                if (key === 'save_close' || key === 'save_next') {
                    this.categorySaving = true;
                }

                window.dispatchEvent(new CustomEvent('category-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'brands') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') {
                    return;
                }

                if ((key === 'save_close' || key === 'save_next') && this.brandSaving) {
                    return;
                }

                if (key === 'ai' && this.brandAiEnriching) {
                    return;
                }

                if (key === 'ai') {
                    this.brandAiEnriching = true;
                }

                if (key === 'save_close' || key === 'save_next') {
                    this.brandSaving = true;
                }

                window.dispatchEvent(new CustomEvent('brand-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'product-reviews') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') {
                    return;
                }

                if ((key === 'save_close' || key === 'save_next') && this.productReviewSaving) {
                    return;
                }

                if (key === 'ai' && this.productReviewAiEnriching) {
                    return;
                }

                if (key === 'ai') {
                    this.productReviewAiEnriching = true;
                }

                if (key === 'save_close' || key === 'save_next') {
                    this.productReviewSaving = true;
                }

                window.dispatchEvent(new CustomEvent('product-review-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'units') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') return;
                if ((key === 'save_close' || key === 'save_next') && this.unitSaving) return;
                if (key === 'ai' && this.unitAiEnriching) return;

                if (key === 'ai') {
                    this.unitAiEnriching = true;
                }
                if (key === 'save_close' || key === 'save_next') {
                    this.unitSaving = true;
                }

                window.dispatchEvent(new CustomEvent('unit-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'packing-units') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') return;
                if ((key === 'save_close' || key === 'save_next') && this.packingUnitSaving) return;
                if (key === 'ai' && this.packingUnitAiEnriching) return;

                if (key === 'ai') {
                    this.packingUnitAiEnriching = true;
                }
                if (key === 'save_close' || key === 'save_next') {
                    this.packingUnitSaving = true;
                }

                window.dispatchEvent(new CustomEvent('packing-unit-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'attributes') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') return;
                if ((key === 'save_close' || key === 'save_next') && this.attributeSaving) return;
                if (key === 'ai' && this.attributeAiEnriching) return;

                if (key === 'ai') {
                    this.attributeAiEnriching = true;
                }
                if (key === 'save_close' || key === 'save_next') {
                    this.attributeSaving = true;
                }

                window.dispatchEvent(new CustomEvent('attribute-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'controls') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') return;
                if ((key === 'save_close' || key === 'save_next') && this.controlSaving) return;
                if (key === 'ai' && this.controlAiEnriching) return;

                if (key === 'ai') {
                    this.controlAiEnriching = true;
                }
                if (key === 'save_close' || key === 'save_next') {
                    this.controlSaving = true;
                }

                window.dispatchEvent(new CustomEvent('control-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'products') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (this.formMode === 'view') {
                    return;
                }

                if ((key === 'save_close' || key === 'save_next') && this.productSaving) {
                    return;
                }

                if (key === 'ai' && this.productAiEnriching) {
                    return;
                }

                if (key === 'ai') {
                    this.productAiEnriching = true;
                }

                if (key === 'save_close' || key === 'save_next') {
                    this.productSaving = true;
                }

                window.dispatchEvent(new CustomEvent('product-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'labels') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'print') {
                    document.getElementById('agricart-label-batch-submit')?.click();
                }

                return;
            }

            if (this.formKey === 'users' || this.formKey === 'staff') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'ai') {
                    this.userAiEnriching = true;
                }

                window.dispatchEvent(new CustomEvent('user-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'roles') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('role-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'company-profile') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'ai') {
                    this.companyProfileAiEnriching = true;
                }

                window.dispatchEvent(new CustomEvent('company-profile-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'suppliers') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'ai') {
                    this.supplierAiEnriching = true;
                }

                window.dispatchEvent(new CustomEvent('supplier-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'customers') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'ai') {
                    this.customerAiEnriching = true;
                }

                window.dispatchEvent(new CustomEvent('customer-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'transport-companies') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'ai') {
                    this.transportCompanyAiEnriching = true;
                }

                window.dispatchEvent(new CustomEvent('transport-company-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'staff-salary') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('staff-salary-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'staff-attendance') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('staff-attendance-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'staff-advance') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('staff-advance-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'staff-leave') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('staff-leave-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'staff-payroll') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('staff-payroll-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'finance-bank') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'save_close' && this.financeBankSaving) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('finance-bank-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'finance-fund-transfer') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'save_close' && this.financeFundTransferSaving) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('finance-fund-transfer-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'finance-expense-category') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if ((key === 'save_close' || key === 'save_next') && this.financeExpenseCategorySaving) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('finance-expense-category-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'contact-message-templates') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if ((key === 'save_close' || key === 'save_next') && this.contactMessageTemplateSaving) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('contact-message-template-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'finance-expense') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if (key === 'ai') {
                    this.financeExpenseAiEnriching = true;
                }

                if ((key === 'save_close' || key === 'save_next') && this.financeExpenseSaving) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('finance-expense-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'finance-expense-payment') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                if ((key === 'save_close' || key === 'save_next') && this.financeExpensePaymentSaving) {
                    return;
                }

                window.dispatchEvent(new CustomEvent('finance-expense-payment-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'purchase-planning') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('purchase-planning-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'purchase-payment-summary') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('purchase-payment-summary-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'purchase-quotations') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('purchase-quotation-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'purchases') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('purchase-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'price-update') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('price-update-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'sales') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('sale-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'sale-quotations') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('sale-quotation-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'sale-returns') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('sale-return-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'online-orders') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('online-order-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'price-controls') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('price-control-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'quantity-controls') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('quantity-control-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'secret-coding') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('secret-coding-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'sales-channel') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('sales-channel-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'delivery-methods') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('delivery-method-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (this.formKey === 'logistics-ship') {
                if (key === 'cancel') {
                    this.close();
                    return;
                }

                window.dispatchEvent(new CustomEvent('logistics-ship-form-action', {
                    detail: { action: key },
                    bubbles: true,
                }));

                return;
            }

            if (key === 'save_next' && this.currentStep < this.totalSteps) {
                this.currentStep += 1;

                if (this.formKey === 'categories') {
                    this.$nextTick(() => this.scrollToCategorySection(this.currentStep));
                }

                return;
            }

            if (key === 'ai') {
                return;
            }

            this.close();
        },
        preferredPanelSize(formKey) {
            return [
                'ai-settings',
                'ai-log-detail',
                'activity-log-detail',
                'system-error-detail',
                'media-library',
                'categories',
                'brands',
                'product-reviews',
                'units',
                'packing-units',
                'attributes',
                'controls',
                'products',
                'users',
                'staff',
                'roles',
                'company-profile',
                'suppliers',
                'customers',
                'transport-companies',
                'staff-salary',
                'staff-attendance',
                'staff-advance',
                'staff-leave',
                'staff-payroll',
                'finance-bank',
                'finance-fund-transfer',
                'finance-expense-category',
                'contact-message-templates',
                'finance-expense',
                'finance-expense-payment',
                'purchase-planning',
                'purchase-payment-summary',
                'purchase-quotations',
                'purchases',
                'price-update',
                'sales',
                'sale-quotations',
                'sale-returns',
                'online-orders',
                'price-controls',
                'quantity-controls',
                'secret-coding',
                'sales-channel',
                'delivery-methods',
                'logistics-ship',
            ].includes(formKey) ? 'stretched' : 'normal';
        },
        panelClasses() {
            return {
                'agricart-form-panel--normal': this.size === 'normal',
                'agricart-form-panel--stretched': this.size === 'stretched',
                'agricart-form-panel--maximized': this.size === 'maximized',
                'agricart-form-panel--wide': this.formKey === 'ai-settings' || this.formKey === 'ai-log-detail' || this.formKey === 'activity-log-detail' || this.formKey === 'system-error-detail' || this.formKey === 'media-library' || this.formKey === 'categories' || this.formKey === 'brands' || this.formKey === 'product-reviews' || this.formKey === 'units' || this.formKey === 'packing-units' || this.formKey === 'attributes' || this.formKey === 'controls' || this.formKey === 'products' || this.formKey === 'labels' || this.formKey === 'users' || this.formKey === 'staff' || this.formKey === 'roles' || this.formKey === 'company-profile' || this.formKey === 'suppliers' || this.formKey === 'customers' || this.formKey === 'transport-companies' || this.formKey === 'contact-message-templates' || this.formKey === 'purchase-planning' || this.formKey === 'purchase-payment-summary' || this.formKey === 'purchase-quotations' || this.formKey === 'purchases' || this.formKey === 'price-update' || this.formKey === 'sales' || this.formKey === 'sale-quotations' || this.formKey === 'sale-returns' || this.formKey === 'online-orders' || this.formKey === 'price-controls' || this.formKey === 'quantity-controls' || this.formKey === 'secret-coding' || this.formKey === 'sales-channel' || this.formKey === 'delivery-methods' || this.formKey === 'logistics-ship' || this.formKey === 'staff-salary' || this.formKey === 'staff-attendance' || this.formKey === 'staff-advance' || this.formKey === 'staff-leave' || this.formKey === 'staff-payroll' || this.formKey === 'finance-bank' || this.formKey === 'finance-fund-transfer' || this.formKey === 'finance-expense-category' || this.formKey === 'finance-expense' || this.formKey === 'finance-expense-payment',
            };
        },
        hasTextSelection() {
            return (window.getSelection()?.toString() || '').trim() !== '';
        },
        requestCloseFromBackdrop() {
            // Industry standard: only close when the press started on the dimmed
            // backdrop — not when the user was selecting / copying text in the form.
            if (! this.backdropPressed) {
                return;
            }

            this.backdropPressed = false;

            if (this.hasTextSelection()) {
                return;
            }

            this.close();
        },
        requestCloseFromEscape(event) {
            if (! this.isOpen || this.minimized) {
                return;
            }

            // Keep the panel open while copying (Ctrl/Cmd held) or text is selected.
            if (event?.ctrlKey || event?.metaKey || event?.altKey || this.hasTextSelection()) {
                return;
            }

            this.close();
        },
        focusPanel() {
            const panel = this.$el.querySelector('.agricart-form-panel');
            const target = panel?.querySelector(
                'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [href], [tabindex]:not([tabindex=&quot;-1&quot;])',
            );
            (target || panel)?.focus?.();
        },
        trapFocus(event) {
            const panel = event.currentTarget;
            const focusable = [...panel.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=&quot;-1&quot;])',
            )].filter((element) => element.offsetParent !== null);

            if (focusable.length === 0) {
                event.preventDefault();
                panel.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
        enhanceStatusAlerts() {
            window.agricartEnhanceFormPanelAlerts?.(this.$el?.querySelector?.('.agricart-form-panel__body'));
        },
    }"
    x-on:open-form-panel.window="open(Array.isArray($event.detail) ? ($event.detail[0] || {}) : ($event.detail || {})); $nextTick(() => enhanceStatusAlerts())"
    x-on:form-panel-mounted.window="if (pendingOpenDetail) { open({ ...pendingOpenDetail, __formMounted: true }); $nextTick(() => { enhanceStatusAlerts(); focusPanel(); }); }"
    x-on:close-form-panel.window="close(false)"
    x-on:category-form-busy.window="categoryAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); categorySaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:brand-form-busy.window="brandAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); brandSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:product-review-form-busy.window="productReviewAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); productReviewSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:unit-form-busy.window="unitAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); unitSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:packing-unit-form-busy.window="packingUnitAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); packingUnitSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:attribute-form-busy.window="attributeAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); attributeSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:control-form-busy.window="controlAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); controlSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:product-form-busy.window="productAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); productSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:user-form-busy.window="userAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); userSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:role-form-busy.window="roleSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:company-profile-form-busy.window="companyProfileAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); companyProfileSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:supplier-form-busy.window="supplierAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); supplierSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:customer-form-busy.window="customerAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); customerSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:transport-company-form-busy.window="transportCompanyAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); transportCompanySaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:purchase-planning-form-busy.window="purchasePlanningSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:purchase-payment-summary-form-busy.window="purchasePaymentSummarySaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:purchase-quotation-form-busy.window="purchaseQuotationSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:purchase-form-busy.window="purchaseSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:price-update-form-busy.window="priceUpdateSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:sale-form-busy.window="saleSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:sale-quotation-form-busy.window="saleQuotationSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:sale-return-form-busy.window="saleReturnSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:online-order-form-busy.window="onlineOrderSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:price-control-form-busy.window="priceControlSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:quantity-control-form-busy.window="quantityControlSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:secret-coding-form-busy.window="secretCodingSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:sales-channel-form-busy.window="salesChannelSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:staff-advance-form-busy.window="staffAdvanceSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:finance-bank-form-busy.window="financeBankSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:finance-fund-transfer-form-busy.window="financeFundTransferSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:finance-expense-category-form-busy.window="financeExpenseCategorySaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:contact-message-template-form-busy.window="contactMessageTemplateSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:finance-expense-form-busy.window="financeExpenseAiEnriching = !!($event.detail?.enriching ?? $event.detail?.[0]?.enriching); financeExpenseSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:finance-expense-payment-form-busy.window="financeExpensePaymentSaving = !!($event.detail?.saving ?? $event.detail?.[0]?.saving)"
    x-on:keydown.escape.window="requestCloseFromEscape($event)"
    x-effect="if (isOpen && !minimized) { $nextTick(() => enhanceStatusAlerts()) }"
    data-agricart-copy-label="{{ __('agricart.actions.copy') }}"
    data-agricart-copied-label="{{ __('agricart.actions.copied') }}"
>
    {{-- Backdrop + panel --}}
    <div
        x-cloak
        x-show="isOpen && !minimized"
        x-transition.opacity
        class="agricart-form-panel-overlay"
        x-on:pointerdown.self="backdropPressed = true"
        x-on:click.self="requestCloseFromBackdrop()"
    >
        <div
            class="agricart-form-panel"
            x-bind:class="panelClasses()"
            x-on:pointerdown="backdropPressed = false"
            x-on:click.stop
            role="dialog"
            aria-modal="true"
            x-bind:aria-label="title"
            tabindex="-1"
            x-on:keydown.tab="trapFocus($event)"
        >
            <header class="agricart-form-panel__header">
                <div class="agricart-form-panel__header-main">
                    <h2 class="agricart-form-panel__title" x-text="title"></h2>
                    <p
                        class="agricart-form-panel__subtitle"
                        x-show="formKey !== 'media-library' && formKey !== 'products' && formKey !== 'users' && formKey !== 'staff' && formKey !== 'roles' && formKey !== 'company-profile' && formKey !== 'suppliers' && formKey !== 'customers' && formKey !== 'transport-companies' && formKey !== 'contact-message-templates' && formKey !== 'purchase-planning' && formKey !== 'purchase-payment-summary' && formKey !== 'purchase-quotations' && formKey !== 'purchases' && formKey !== 'price-update' && formKey !== 'sales' && formKey !== 'sale-quotations' && formKey !== 'sale-returns' && formKey !== 'online-orders' && formKey !== 'price-controls' && formKey !== 'quantity-controls' && formKey !== 'secret-coding' && formKey !== 'sales-channel' && formKey !== 'staff-salary' && formKey !== 'staff-attendance' && formKey !== 'staff-advance' && formKey !== 'staff-leave' && formKey !== 'staff-payroll' && formKey !== 'finance-bank' && formKey !== 'finance-fund-transfer' && formKey !== 'finance-expense-category' && formKey !== 'finance-expense' && formKey !== 'finance-expense-payment' && resourceLabel"
                        x-text="resourceLabel"
                        x-cloak
                    ></p>
                </div>

                <div class="agricart-form-panel__window-controls">
                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="{{ __('agricart.form_panel.minimize') }}"
                        x-on:click="minimize()"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedMinus" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.minimize') }}</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="{{ __('agricart.form_panel.stretch') }}"
                        x-on:click="toggleStretch()"
                        x-bind:class="{ 'is-active': size === 'stretched' }"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedArrowsPointingOut" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.stretch') }}</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn"
                        title="{{ __('agricart.form_panel.maximize') }}"
                        x-on:click="toggleMaximize()"
                        x-bind:class="{ 'is-active': size === 'maximized' }"
                    >
                        <x-filament::icon
                            :icon="Heroicon::OutlinedWindow"
                            class="agricart-form-panel__window-icon"
                        />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.maximize') }}</span>
                    </button>

                    <button
                        type="button"
                        class="agricart-form-panel__window-btn agricart-form-panel__window-btn--close"
                        title="{{ __('agricart.form_panel.close') }}"
                        x-on:click="close()"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedXMark" class="agricart-form-panel__window-icon" />
                        <span class="fi-sr-only">{{ __('agricart.form_panel.close') }}</span>
                    </button>
                </div>
            </header>

            <div class="agricart-form-panel__body">
                <div
                    class="agricart-form-panel__loading"
                    x-show="pendingOpenDetail !== null"
                    x-cloak
                    aria-hidden="true"
                >
                    <span class="agricart-form-panel__loading-bar"></span>
                    <span class="agricart-form-panel__loading-bar agricart-form-panel__loading-bar--short"></span>
                    <span class="agricart-form-panel__loading-bar"></span>
                </div>
                <div class="agricart-form-panel__sections">
                    <div x-show="formKey" x-cloak>
                        <livewire:form-panel.dynamic-form />
                    </div>

                    <div x-show="formKey !== 'ai-settings' && formKey !== 'ai-log-detail' && formKey !== 'activity-log-detail' && formKey !== 'system-error-detail' && formKey !== 'media-library' && formKey !== 'categories' && formKey !== 'brands' && formKey !== 'product-reviews' && formKey !== 'units' && formKey !== 'packing-units' && formKey !== 'attributes' && formKey !== 'controls' && formKey !== 'products' && formKey !== 'labels' && formKey !== 'users' && formKey !== 'staff' && formKey !== 'roles' && formKey !== 'company-profile' && formKey !== 'suppliers' && formKey !== 'customers' && formKey !== 'transport-companies' && formKey !== 'contact-message-templates' && formKey !== 'purchase-planning' && formKey !== 'purchase-payment-summary' && formKey !== 'purchase-quotations' && formKey !== 'purchases' && formKey !== 'price-update' && formKey !== 'sales' && formKey !== 'sale-quotations' && formKey !== 'sale-returns' && formKey !== 'online-orders' && formKey !== 'price-controls' && formKey !== 'quantity-controls' && formKey !== 'secret-coding' && formKey !== 'sales-channel' && formKey !== 'staff-salary' && formKey !== 'staff-attendance' && formKey !== 'staff-advance' && formKey !== 'staff-leave' && formKey !== 'staff-payroll' && formKey !== 'finance-bank' && formKey !== 'finance-fund-transfer' && formKey !== 'finance-expense-category' && formKey !== 'finance-expense' && formKey !== 'finance-expense-payment' && formKey !== 'delivery-methods' && formKey !== 'logistics-ship'">
                        @include('filament.components.form-panel.sample-fields')
                    </div>
                </div>
            </div>

            <footer class="agricart-form-panel__footer" x-show="formKey !== 'ai-settings' && formKey !== 'ai-log-detail' && formKey !== 'activity-log-detail' && formKey !== 'system-error-detail' && formKey !== 'media-library'">
                @include('filament.components.form-panel.footer')
            </footer>
        </div>
    </div>

    {{-- Minimized dock --}}
    <div
        x-cloak
        x-show="isOpen && minimized"
        class="agricart-form-panel-minimized"
    >
        <div class="agricart-form-panel-minimized__inner">
            <button
                type="button"
                class="agricart-form-panel-minimized__restore"
                x-on:click="restore()"
            >
                <x-filament::icon :icon="Heroicon::OutlinedWindow" class="agricart-form-panel__window-icon" />
                <span x-text="title"></span>
            </button>

            <div class="agricart-form-panel-minimized__controls">
                <button
                    type="button"
                    class="agricart-form-panel__window-btn"
                    title="{{ __('agricart.form_panel.restore') }}"
                    x-on:click="restore()"
                >
                    <x-filament::icon :icon="Heroicon::OutlinedArrowsPointingIn" class="agricart-form-panel__window-icon" />
                </button>
                <button
                    type="button"
                    class="agricart-form-panel__window-btn agricart-form-panel__window-btn--close"
                    title="{{ __('agricart.form_panel.close') }}"
                    x-on:click="close()"
                >
                    <x-filament::icon :icon="Heroicon::OutlinedXMark" class="agricart-form-panel__window-icon" />
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    if (! window.__agricartFormPanelTriggerBound) {
        window.__agricartFormPanelTriggerBound = true;

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-agricart-open-form-panel]');

            if (! trigger) {
                return;
            }

            event.preventDefault();

            const formKey = trigger.getAttribute('data-form-key');

            window.dispatchEvent(new CustomEvent('open-form-panel', {
                detail: {
                    title: trigger.getAttribute('data-form-title') || @js($formPanelDefaults['addRecord']),
                    resource: trigger.getAttribute('data-form-resource') || @js($formPanelDefaults['record']),
                    formKey: formKey && formKey !== '' ? formKey : null,
                    formContext: trigger.getAttribute('data-form-context') || null,
                    attendanceDate: trigger.getAttribute('data-attendance-date') || null,
                    payrollMonth: trigger.getAttribute('data-payroll-month') || null,
                    categoryId: trigger.getAttribute('data-category-id') ? Number(trigger.getAttribute('data-category-id')) : null,
                    mode: trigger.getAttribute('data-form-mode') || 'create',
                },
            }));
        });
    }

    document.addEventListener('livewire:init', () => {
        Livewire.on('category-form-busy', (payload) => {
            const detail = payload?.enriching !== undefined ? payload : (Array.isArray(payload) ? payload[0] : {});

            window.dispatchEvent(new CustomEvent('category-form-busy', {
                detail,
                bubbles: true,
            }));
        });
    });

    (function () {
        const quickOpenPaths = @json($quickFormOpenPaths);
        const quickOpenPanels = @json($quickFormOpenPanels);
        const storageKey = 'agricart-quick-form-open';

        const normalizePath = (path) => {
            if (typeof path !== 'string') {
                return '';
            }

            const trimmed = path.replace(/\/+$/, '') || '/';

            return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
        };

        const currentPath = () => normalizePath(window.location.pathname);

        const resolveQuickOpenKey = () => {
            const fromStorage = sessionStorage.getItem(storageKey);

            if (fromStorage && quickOpenPanels[fromStorage]) {
                return fromStorage;
            }

            const open = new URLSearchParams(window.location.search).get('open');

            if (open && quickOpenPanels[open]) {
                return open;
            }

            return null;
        };

        const stripOpenQuery = () => {
            const params = new URLSearchParams(window.location.search);

            if (! params.has('open')) {
                return;
            }

            params.delete('open');
            const query = params.toString();
            const next = window.location.pathname + (query ? `?${query}` : '');

            window.history.replaceState({}, '', next);
        };

        const tryQuickFormOpen = () => {
            const key = resolveQuickOpenKey();

            if (! key) {
                return;
            }

            const expectedPath = normalizePath(quickOpenPaths[key] ?? '');

            if (expectedPath === '' || currentPath() !== expectedPath) {
                return;
            }

            sessionStorage.removeItem(storageKey);
            stripOpenQuery();

            const panel = quickOpenPanels[key];

            window.setTimeout(() => {
                window.dispatchEvent(new CustomEvent('open-form-panel', {
                    detail: {
                        title: panel.title,
                        resource: panel.resource,
                        formKey: panel.formKey,
                        mode: 'create',
                    },
                }));
            }, 120);
        };

        if (! window.__agricartQuickFormOpenBound) {
            window.__agricartQuickFormOpenBound = true;
            document.addEventListener('DOMContentLoaded', tryQuickFormOpen);
            document.addEventListener('livewire:navigated', tryQuickFormOpen);
        }

        ['sales-open-print', 'sale-quotations-open-print', 'sale-returns-open-print', 'online-sale-orders-open-print', 'finance-banks-open-print', 'finance-fund-transfers-open-print', 'finance-expense-payments-open-print', 'brands-open-print', 'suppliers-open-print', 'online-order-open-external'].forEach((eventName) => {
            Livewire.on(eventName, (payload) => {
                const url = payload?.url ?? payload?.[0]?.url;

                if (url) {
                    window.open(url, '_blank');
                }
            });
        });

        const alertSelector = [
            '.agricart-category-form__alert',
            '.agricart-user-form__alert',
            '.agricart-product-form__alert',
            '.agricart-ai-form__alert',
            '.agricart-purchase-form__status',
            '.agricart-planning-form__status',
        ].join(', ');

        window.agricartEnhanceFormPanelAlerts = function (root) {
            if (! root) {
                return;
            }

            const panelRoot = root.closest('[data-agricart-copy-label]') || document.querySelector('[data-agricart-copy-label]');
            const copyLabel = panelRoot?.getAttribute('data-agricart-copy-label') || 'Copy';
            const copiedLabel = panelRoot?.getAttribute('data-agricart-copied-label') || 'Copied';

            root.querySelectorAll(alertSelector).forEach((alert) => {
                if (alert.dataset.agricartCopyReady === '1') {
                    return;
                }

                alert.dataset.agricartCopyReady = '1';
                alert.classList.add('agricart-status-alert');

                const text = document.createElement('div');
                text.className = 'agricart-status-alert__text';

                while (alert.firstChild) {
                    text.appendChild(alert.firstChild);
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'agricart-status-alert__copy';
                button.textContent = copyLabel;
                button.setAttribute('aria-label', copyLabel);

                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const value = (text.innerText || '').trim();

                    if (! value) {
                        return;
                    }

                    const markCopied = () => {
                        button.textContent = copiedLabel;
                        button.classList.add('is-copied');
                        window.setTimeout(() => {
                            button.textContent = copyLabel;
                            button.classList.remove('is-copied');
                        }, 1500);
                    };

                    if (navigator.clipboard?.writeText) {
                        navigator.clipboard.writeText(value).then(markCopied).catch(() => {
                            // Fallback below.
                            const range = document.createRange();
                            range.selectNodeContents(text);
                            const selection = window.getSelection();
                            selection?.removeAllRanges();
                            selection?.addRange(range);
                            try {
                                document.execCommand('copy');
                                markCopied();
                            } catch (error) {
                                // Leave text selected so the user can Ctrl+C.
                            }
                        });

                        return;
                    }

                    const range = document.createRange();
                    range.selectNodeContents(text);
                    const selection = window.getSelection();
                    selection?.removeAllRanges();
                    selection?.addRange(range);

                    try {
                        document.execCommand('copy');
                        markCopied();
                    } catch (error) {
                        // Selection remains for manual Ctrl+C.
                    }
                });

                alert.appendChild(text);
                alert.appendChild(button);
            });
        };

        document.addEventListener('livewire:morph.updated', () => {
            window.agricartEnhanceFormPanelAlerts(
                document.querySelector('.agricart-form-panel__body'),
            );
        });
    })();
</script>
