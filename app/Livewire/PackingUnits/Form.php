<?php

namespace App\Livewire\PackingUnits;

use App\Enums\CategoryAiStatus;
use App\Models\PackingUnit;
use App\Services\Catalog\PackingUnitEnrichmentService;
use App\Services\Catalog\PackingUnitService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $packingUnitId = null;

    public string $mode = 'create';

    public string $name_en = '';

    public string $name_ur = '';

    public string $code = '';

    public string $slug = '';

    public string $status = 'active';

    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->code = app(PackingUnitService::class)->nextCode();
    }

    #[On('packing-unit-form-open')]
    public function openForm(?int $packingUnitId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->packingUnitId = $packingUnitId;

        if ($packingUnitId) {
            $this->loadPackingUnit($packingUnitId);
        }
    }

    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'ai' => $this->enrichWithAi(),
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function enrichWithAi(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (! filled(trim($this->name_en)) && ! filled(trim($this->name_ur))) {
            $this->setStatus(__('agricart.packing_units.ai.name_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(PackingUnitEnrichmentService::class)->enrich(
                $this->name_en,
                $this->name_ur,
            );

            if (! $result['success']) {
                $this->ai_status = CategoryAiStatus::Failed->value;
                $this->setStatus($result['message'], 'danger');

                return;
            }

            $this->applyEnrichmentData($result['data'] ?? []);
            $this->ai_status = CategoryAiStatus::Enriched->value;
            $this->setStatus($result['message'], 'success');
        } catch (\Throwable $exception) {
            $this->ai_status = CategoryAiStatus::Failed->value;
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if (! $this->persist()) {
            return;
        }

        $this->dispatch('packing-units-saved');
        $this->dispatch('close-form-panel');
        $this->js("window.dispatchEvent(new CustomEvent('close-form-panel', { bubbles: true }))");
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('packing-units-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.packing-units.form');
    }

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        $missing = [];

        if (! filled(trim($this->name_en))) {
            $missing[] = __('agricart.packing_units.fields.name_en');
        }

        if (! filled(trim($this->name_ur))) {
            $missing[] = __('agricart.packing_units.fields.name_ur');
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.packing_units.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(PackingUnitService::class);
            $payload = $this->buildPayload($service);

            if ($this->packingUnitId) {
                $packingUnit = PackingUnit::query()->findOrFail($this->packingUnitId);
                $service->update($packingUnit, $payload);
            } else {
                $packingUnit = $service->create($payload);
                $this->packingUnitId = $packingUnit->id;
                $this->code = $packingUnit->code;
                $this->mode = 'edit';
            }

            $this->setStatus(__('agricart.packing_units.messages.saved'), 'success');

            return true;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.packing_units.messages.validation_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('packing-unit-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(PackingUnitService $service): array
    {
        if (! filled($this->slug) && filled($this->name_en)) {
            $this->slug = $service->uniqueSlug($service->slugFromName($this->name_en), $this->packingUnitId);
        }

        return [
            'code' => $this->code ?: null,
            'slug' => $this->slug,
            'status' => $this->status ?: 'active',
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'ai_status' => $this->ai_status ?: 'none',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['name_en', 'name_ur'] as $field) {
            $value = $data[$field] ?? null;

            if (! filled($value)) {
                continue;
            }

            if (! filled(trim($this->{$field}))) {
                $this->{$field} = trim((string) $value);
            }
        }

        if (filled($data['ai_status'] ?? null)) {
            $this->ai_status = (string) $data['ai_status'];
        }
    }

    private function loadPackingUnit(int $packingUnitId): void
    {
        $packingUnit = PackingUnit::query()->findOrFail($packingUnitId);

        $this->name_en = $packingUnit->name_en;
        $this->name_ur = $packingUnit->name_ur;
        $this->code = $packingUnit->code;
        $this->slug = $packingUnit->slug;
        $this->status = $packingUnit->status;
        $this->ai_status = $packingUnit->ai_status?->value ?? 'none';
    }

    private function resetForm(): void
    {
        $this->packingUnitId = null;
        $this->mode = 'create';
        $this->name_en = '';
        $this->name_ur = '';
        $this->code = app(PackingUnitService::class)->nextCode();
        $this->slug = '';
        $this->status = 'active';
        $this->ai_status = 'none';
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
