<?php

namespace App\Livewire\DeliveryMethods;

use App\Enums\ShippingChargeMode;
use App\Models\DeliveryMethod;
use App\Models\User;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\Sales\SaleService;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Form extends Component
{
    #[Locked]
    public ?int $deliveryMethodId = null;

    #[Locked]
    public string $mode = 'create';

    public string $name_en = '';

    public string $name_ur = '';

    public string $description_en = '';

    public string $description_ur = '';

    public string $slug = '';

    public int $sort_order = 0;

    public string $status = DeliveryMethod::STATUS_ACTIVE;

    /** @var list<string> */
    public array $allowed_payment_methods = [];

    public bool $bank_transfer_allowed = true;

    public bool $cod_allowed = true;

    public string $cod_min_advance_percent = '0';

    public bool $full_advance_required = false;

    public string $customer_notice_en = '';

    public string $customer_notice_ur = '';

    public string $shipping_charge_mode = 'calculate_later';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->allowed_payment_methods = SaleService::PAYMENT_METHODS;
        $this->bank_transfer_allowed = true;
        $this->cod_allowed = true;
    }

    public function openForm(?int $deliveryMethodId = null, string $mode = 'create'): void
    {
        $normalizedMode = in_array($mode, ['create', 'edit', 'view'], true) ? $mode : 'create';
        $this->authorizeAction(
            $deliveryMethodId === null
                ? 'create'
                : ($normalizedMode === 'view' ? 'view' : 'edit'),
        );

        $this->resetForm();
        $this->mode = $normalizedMode;
        $this->deliveryMethodId = $deliveryMethodId;

        if ($deliveryMethodId) {
            $this->loadMethod($deliveryMethodId);
        }
    }

    public function handlePanelAction(string $action): void
    {
        if ($this->mode === 'view' && in_array($action, ['save_close', 'save_next'], true)) {
            return;
        }

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('delivery-methods-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('delivery-methods-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.delivery-methods.form', [
            'shippingModes' => ShippingChargeMode::cases(),
        ]);
    }

    private function persist(): bool
    {
        if ($this->saving || $this->isReadonly()) {
            return false;
        }

        $this->authorizeAction($this->mutationAction());
        $this->saving = true;
        $this->statusMessage = null;
        $this->syncAllowedPaymentMethodsFromUi();

        try {
            $payload = [
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur,
                'description_en' => $this->description_en !== '' ? $this->description_en : null,
                'description_ur' => $this->description_ur !== '' ? $this->description_ur : null,
                'slug' => $this->slug !== '' ? $this->slug : null,
                'sort_order' => $this->sort_order,
                'status' => $this->status,
                'allowed_payment_methods' => $this->allowed_payment_methods,
                'cod_allowed' => $this->cod_allowed,
                'cod_min_advance_percent' => $this->cod_min_advance_percent,
                'full_advance_required' => $this->full_advance_required,
                'customer_notice_en' => $this->customer_notice_en !== '' ? $this->customer_notice_en : null,
                'customer_notice_ur' => $this->customer_notice_ur !== '' ? $this->customer_notice_ur : null,
                'shipping_charge_mode' => $this->shipping_charge_mode,
            ];

            $service = app(DeliveryMethodService::class);

            if ($this->deliveryMethodId) {
                $method = DeliveryMethod::query()->findOrFail($this->deliveryMethodId);
                $service->update($method, $payload);
            } else {
                $created = $service->create($payload);
                $this->deliveryMethodId = $created->id;
                $this->slug = $created->slug;
            }

            $this->setStatus(__('agricart.delivery_methods.actions.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: $exception->getMessage();
            $this->setStatus((string) $message, 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
        }
    }

    private function loadMethod(int $id): void
    {
        $method = DeliveryMethod::query()->with('rule')->findOrFail($id);
        $rule = $method->rule;

        $this->name_en = $method->name_en;
        $this->name_ur = $method->name_ur;
        $this->description_en = (string) ($method->description_en ?? '');
        $this->description_ur = (string) ($method->description_ur ?? '');
        $this->slug = $method->slug;
        $this->sort_order = (int) $method->sort_order;
        $this->status = $method->status;
        $this->allowed_payment_methods = $rule?->allowedPaymentMethods() ?: SaleService::PAYMENT_METHODS;
        $this->bank_transfer_allowed = in_array('bank_transfer', $this->allowed_payment_methods, true);
        $this->cod_allowed = (bool) ($rule?->cod_allowed ?? true);
        $this->cod_min_advance_percent = (string) ($rule?->cod_min_advance_percent ?? '0');
        $this->full_advance_required = (bool) ($rule?->full_advance_required ?? false);
        $this->customer_notice_en = (string) ($rule?->customer_notice_en ?? '');
        $this->customer_notice_ur = (string) ($rule?->customer_notice_ur ?? '');
        $this->shipping_charge_mode = $rule?->shipping_charge_mode?->value ?? ShippingChargeMode::CalculateLater->value;
    }

    private function resetForm(): void
    {
        $this->deliveryMethodId = null;
        $this->name_en = '';
        $this->name_ur = '';
        $this->description_en = '';
        $this->description_ur = '';
        $this->slug = '';
        $this->sort_order = 0;
        $this->status = DeliveryMethod::STATUS_ACTIVE;
        $this->allowed_payment_methods = SaleService::PAYMENT_METHODS;
        $this->bank_transfer_allowed = true;
        $this->cod_allowed = true;
        $this->cod_min_advance_percent = '0';
        $this->full_advance_required = false;
        $this->customer_notice_en = '';
        $this->customer_notice_ur = '';
        $this->shipping_charge_mode = ShippingChargeMode::CalculateLater->value;
        $this->statusMessage = null;
        $this->statusType = 'success';
        $this->saving = false;
    }

    private function syncAllowedPaymentMethodsFromUi(): void
    {
        $payments = [];

        if ($this->cod_allowed) {
            $payments[] = 'cash';
        }

        if ($this->bank_transfer_allowed) {
            $payments[] = 'bank_transfer';
        }

        $this->allowed_payment_methods = $payments;
    }

    private function mutationAction(): string
    {
        return $this->deliveryMethodId ? 'edit' : 'create';
    }

    private function authorizeAction(string $action): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        abort_unless(\App\Services\Access\ModulePageAccess::can($user, 'logistics', 'delivery-methods', $action), 403);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
