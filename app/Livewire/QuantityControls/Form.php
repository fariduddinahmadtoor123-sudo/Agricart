<?php

namespace App\Livewire\QuantityControls;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\QuantityChannelTier;
use App\Services\PricingControls\QuantityControlService;
use App\Services\PricingControls\QuantityPricingEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesSensitiveActions;

    public ?int $tierId = null;

    public string $mode = 'create';

    public string $tier_key = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $min_quantity = '1';

    public string $status = 'active';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('quantity-control-form-open')]
    public function openForm(?int $tierId = null, string $mode = 'create'): void
    {
        $this->authorizeAny(
            $mode === 'view'
                ? ['marketplace.quantity-control.view', 'marketplace.quantity-control.edit']
                : ($tierId ? 'marketplace.quantity-control.edit' : 'marketplace.quantity-control.create')
        );
        $this->resetForm();
        $this->mode = $mode;
        $this->tierId = $tierId;

        if ($tierId) {
            $this->loadRecord($tierId);
        } else {
            $this->tier_key = $this->defaultTierKey();
            $this->applyTierDefaults();
        }
    }

    #[On('quantity-control-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny($this->tierId ? 'marketplace.quantity-control.edit' : 'marketplace.quantity-control.create');

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function updatedTierKey(): void
    {
        if ($this->tierId) {
            return;
        }

        $this->applyTierDefaults();
    }

    public function saveAndClose(): void
    {
        $this->authorizeAny($this->tierId ? 'marketplace.quantity-control.edit' : 'marketplace.quantity-control.create');

        if ($this->persist()) {
            $this->dispatch('quantity-controls-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        $this->authorizeAny($this->tierId ? 'marketplace.quantity-control.edit' : 'marketplace.quantity-control.create');

        if ($this->persist()) {
            $this->dispatch('quantity-controls-saved');
            $this->resetForm();
            $this->tier_key = $this->defaultTierKey();
            $this->applyTierDefaults();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        $engine = app(QuantityPricingEngine::class);

        return view('livewire.quantity-controls.form', [
            'statuses' => QuantityControlService::STATUSES,
            'tierTypeOptions' => $engine->tierTypeOptions($this->tierId),
            'linkedPriceLabel' => filled($this->tier_key)
                ? $engine->tierTypeLabel($this->tier_key)
                : null,
        ]);
    }

    private function loadRecord(int $tierId): void
    {
        $tier = QuantityChannelTier::query()->findOrFail($tierId);

        $this->tier_key = $tier->tier_key;
        $this->name_en = $tier->name_en;
        $this->name_ur = (string) ($tier->name_ur ?? '');
        $this->min_quantity = (string) $tier->min_quantity;
        $this->status = $tier->status;
    }

    private function persist(): bool
    {
        $this->authorizeAny($this->tierId ? 'marketplace.quantity-control.edit' : 'marketplace.quantity-control.create');

        if ($this->isReadonly()) {
            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $profile = app(QuantityControlService::class)->ensureDefaultProfile();

            $payload = [
                'tier_key' => $this->tier_key,
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur,
                'min_quantity' => $this->min_quantity,
                'status' => $this->status,
            ];

            $service = app(QuantityControlService::class);

            if ($this->tierId) {
                $tier = QuantityChannelTier::query()->findOrFail($this->tierId);
                $service->updateTier($tier, $payload);
                $this->setStatus(__('quantity_controls.messages.updated'), 'success');
            } else {
                $service->createTier($profile, $payload);
                $this->setStatus(__('quantity_controls.messages.created'), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?? __('quantity_controls.messages.save_failed'), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function defaultTierKey(): string
    {
        $options = app(QuantityPricingEngine::class)->tierTypeOptions();

        return (string) ($options[0]['value'] ?? 'retail');
    }

    private function applyTierDefaults(): void
    {
        $definition = QuantityPricingEngine::PRICE_TIER_TYPES[$this->tier_key] ?? null;

        if ($definition === null) {
            return;
        }

        if (! filled($this->name_en)) {
            $this->name_en = $definition['name_en'];
        }

        if (! filled($this->name_ur)) {
            $this->name_ur = $definition['name_ur'];
        }
    }

    private function resetForm(): void
    {
        $this->tierId = null;
        $this->mode = 'create';
        $this->tier_key = '';
        $this->name_en = '';
        $this->name_ur = '';
        $this->min_quantity = '1';
        $this->status = 'active';
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('quantity-control-form-busy', saving: $this->saving);
    }
}
