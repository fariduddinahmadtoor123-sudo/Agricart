<?php

namespace App\Livewire\PriceControls;

use App\Models\PriceChannelTier;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\PricingControls\PriceControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $tierId = null;

    public string $mode = 'create';

    public string $name_en = '';

    public string $name_ur = '';

    public string $markup_percent = '0';

    public string $product_field = '';

    public string $status = 'active';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('price-control-form-open')]
    public function openForm(?int $tierId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->tierId = $tierId;

        if ($tierId) {
            $this->loadRecord($tierId);
        } else {
            $this->product_field = $this->defaultProductField();
        }
    }

    #[On('price-control-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('price-controls-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('price-controls-saved');
            $this->resetForm();
            $this->product_field = $this->defaultProductField();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        $engine = app(ChannelPricingEngine::class);
        $exampleBase = $engine->examplePurchasePrice();
        $previewPrice = null;

        if (is_numeric($this->markup_percent) && (float) $this->markup_percent >= 0) {
            $previewPrice = $engine->formatPrice($exampleBase * (1 + ((float) $this->markup_percent / 100)));
        }

        return view('livewire.price-controls.form', [
            'statuses' => PriceControlService::STATUSES,
            'productFieldOptions' => $engine->productFieldOptions($this->tierId),
            'productFieldLabel' => filled($this->product_field)
                ? $engine->productFieldLabel($this->product_field)
                : null,
            'engine' => $engine,
            'exampleBase' => $exampleBase,
            'exampleBaseFormatted' => $engine->formatPrice($exampleBase),
            'previewPrice' => $previewPrice,
            'usesCustomField' => str_starts_with($this->product_field, 'channel:'),
        ]);
    }

    private function loadRecord(int $tierId): void
    {
        $tier = PriceChannelTier::query()->findOrFail($tierId);

        $this->name_en = $tier->name_en;
        $this->name_ur = (string) ($tier->name_ur ?? '');
        $this->markup_percent = (string) $tier->markup_percent;
        $this->product_field = $tier->product_field;
        $this->status = $tier->status;
    }

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $profile = app(PriceControlService::class)->ensureDefaultProfile();
            $productField = $this->resolveProductField();

            $payload = [
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur,
                'markup_percent' => $this->markup_percent,
                'product_field' => $productField,
                'status' => $this->status,
            ];

            $service = app(PriceControlService::class);

            if ($this->tierId) {
                $tier = PriceChannelTier::query()->findOrFail($this->tierId);
                $service->updateTier($tier, $payload);
                $this->product_field = $productField;
                $this->setStatus(__('price_controls.messages.updated'), 'success');
            } else {
                $service->createTier($profile, $payload);
                $this->setStatus(__('price_controls.messages.created'), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?? __('price_controls.messages.save_failed'), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function resolveProductField(): string
    {
        if (str_starts_with($this->product_field, 'channel:')) {
            return $this->product_field;
        }

        if ($this->product_field !== '' && $this->product_field !== '__custom__') {
            return $this->product_field;
        }

        return 'channel:' . Str::slug($this->name_en, '_');
    }

    private function defaultProductField(): string
    {
        $options = app(ChannelPricingEngine::class)->productFieldOptions();

        if ($options === []) {
            return '__custom__';
        }

        return (string) ($options[0]['value'] ?? '__custom__');
    }

    private function resetForm(): void
    {
        $this->tierId = null;
        $this->mode = 'create';
        $this->name_en = '';
        $this->name_ur = '';
        $this->markup_percent = '0';
        $this->product_field = '';
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
        $this->dispatch('price-control-form-busy', saving: $this->saving);
    }
}
