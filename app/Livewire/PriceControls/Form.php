<?php

namespace App\Livewire\PriceControls;

use App\Models\PriceControl;
use App\Services\PricingControls\PriceControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $priceControlId = null;

    public string $mode = 'create';

    public string $control_number = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $status = 'active';

    public string $notes = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->control_number = app(PriceControlService::class)->nextControlNumber();
    }

    #[On('price-control-form-open')]
    public function openForm(?int $priceControlId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->priceControlId = $priceControlId;

        if ($priceControlId) {
            $this->loadRecord($priceControlId);
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
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.price-controls.form', [
            'statuses' => PriceControlService::STATUSES,
        ]);
    }

    private function loadRecord(int $recordId): void
    {
        $record = PriceControl::query()->findOrFail($recordId);

        $this->control_number = $record->control_number;
        $this->name_en = $record->name_en;
        $this->name_ur = (string) ($record->name_ur ?? '');
        $this->status = $record->status;
        $this->notes = (string) ($record->notes ?? '');
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
            $payload = [
                'control_number' => $this->control_number,
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur,
                'status' => $this->status,
                'notes' => $this->notes,
            ];

            $service = app(PriceControlService::class);

            if ($this->priceControlId) {
                $record = PriceControl::query()->findOrFail($this->priceControlId);
                $service->update($record, $payload);
                $this->setStatus(__('price_controls.messages.updated'), 'success');
            } else {
                $service->create($payload);
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

    private function resetForm(): void
    {
        $this->priceControlId = null;
        $this->mode = 'create';
        $this->control_number = app(PriceControlService::class)->nextControlNumber();
        $this->name_en = '';
        $this->name_ur = '';
        $this->status = 'active';
        $this->notes = '';
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
