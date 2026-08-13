<?php

namespace App\Livewire\Units;

use App\Enums\CategoryAiStatus;
use App\Models\Unit;
use App\Models\User;
use App\Services\Catalog\UnitEnrichmentService;
use App\Services\Catalog\UnitService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Form extends Component
{
    #[Locked]
    public ?int $unitId = null;

    #[Locked]
    public string $mode = 'create';

    public string $name_en = '';

    public string $abbreviation_en = '';

    public string $name_ur = '';

    public string $abbreviation_ur = '';

    #[Locked]
    public string $code = '';

    #[Locked]
    public string $slug = '';

    public string $status = 'active';

    #[Locked]
    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {}

    public function openForm(?int $unitId = null, string $mode = 'create'): void
    {
        $normalizedMode = in_array($mode, ['create', 'edit', 'view'], true) ? $mode : 'create';
        $this->authorizeAction(
            $unitId === null ? 'create' : ($normalizedMode === 'view' ? 'view' : 'edit'),
        );
        $this->resetForm();
        $this->mode = $normalizedMode;
        $this->unitId = $unitId;

        if ($unitId) {
            $this->loadUnit($unitId);
        }
    }

    public function handlePanelAction(string $action): void
    {
        if ($this->mode === 'view' && in_array($action, ['save_close', 'save_next', 'ai'], true)) {
            $this->dispatchBusyState();
            return;
        }
        match ($action) {
            'ai' => $this->enrichWithAi(),
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function enrichWithAi(): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly() || $this->enriching) {
            return;
        }

        if (! filled(trim($this->name_en)) && ! filled(trim($this->name_ur))) {
            $this->setStatus(__('agricart.units.ai.name_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        $aiLock = Cache::lock(
            'unit-ai-enrichment:'.(auth()->id() ?? 'guest').':'.($this->unitId ?? 'new'),
            120,
        );

        if (! $aiLock->get()) {
            $this->setStatus(__('agricart.units.ai.already_running'), 'warning');
            $this->dispatchBusyState();
            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(UnitEnrichmentService::class)->enrich(
                $this->name_en,
                $this->name_ur,
                $this->abbreviation_en,
                $this->abbreviation_ur,
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
            $aiLock->release();
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('units-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('units-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.units.form');
    }

    private function persist(): bool
    {
        if ($this->saving) {
            return false;
        }

        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly()) {
            return false;
        }

        $missing = [];

        if (! filled(trim($this->name_en))) {
            $missing[] = __('agricart.units.fields.name_en');
        }

        if (! filled(trim($this->abbreviation_en))) {
            $missing[] = __('agricart.units.fields.abbreviation_en');
        }

        if (! filled(trim($this->name_ur))) {
            $missing[] = __('agricart.units.fields.name_ur');
        }

        if (! filled(trim($this->abbreviation_ur))) {
            $missing[] = __('agricart.units.fields.abbreviation_ur');
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.units.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );
            $this->dispatchBusyState();

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(UnitService::class);
            $payload = $this->buildPayload($service);

            if ($this->unitId) {
                $unit = Unit::query()->findOrFail($this->unitId);
                $service->update($unit, $payload);
            } else {
                $unit = $service->create($payload);
                $this->unitId = $unit->id;
                $this->code = $unit->code;
                $this->mode = 'edit';
            }

            $this->setStatus(__('agricart.units.messages.saved'), 'success');

            return true;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.units.messages.validation_failed'), 'danger');

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
        $this->dispatch('unit-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(UnitService $service): array
    {
        if (! filled($this->slug) && filled($this->name_en)) {
            $this->slug = $service->uniqueSlug($service->slugFromName($this->name_en), $this->unitId);
        }

        return [
            'code' => $this->code ?: null,
            'slug' => $this->slug,
            'status' => $this->status ?: 'active',
            'name_en' => $this->name_en,
            'abbreviation_en' => $this->abbreviation_en,
            'name_ur' => $this->name_ur,
            'abbreviation_ur' => $this->abbreviation_ur,
            'ai_status' => $this->ai_status ?: 'none',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['name_en', 'name_ur', 'abbreviation_en', 'abbreviation_ur'] as $field) {
            $value = $data[$field] ?? null;

            if (! is_string($value) || ! filled(trim($value))) {
                continue;
            }

            if (! filled(trim($this->{$field}))) {
                $this->{$field} = (string) $value;
            }
        }

        if (filled($data['ai_status'] ?? null)) {
            $this->ai_status = (string) $data['ai_status'];
        }
    }

    private function loadUnit(int $unitId): void
    {
        $unit = Unit::query()->findOrFail($unitId);

        $this->name_en = $unit->name_en;
        $this->abbreviation_en = $unit->abbreviation_en;
        $this->name_ur = $unit->name_ur;
        $this->abbreviation_ur = $unit->abbreviation_ur;
        $this->code = $unit->code;
        $this->slug = $unit->slug;
        $this->status = $unit->status;
        $this->ai_status = $unit->ai_status?->value ?? 'none';
    }

    private function resetForm(): void
    {
        $this->unitId = null;
        $this->mode = 'create';
        $this->name_en = '';
        $this->abbreviation_en = '';
        $this->name_ur = '';
        $this->abbreviation_ur = '';
        $this->code = app(UnitService::class)->nextCode();
        $this->slug = '';
        $this->status = 'active';
        $this->ai_status = 'none';
        $this->saving = false;
        $this->enriching = false;
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function mutationAction(): string
    {
        return $this->unitId === null ? 'create' : 'edit';
    }

    private function authorizeAction(string $action): void
    {
        abort_unless($this->userCan($action), 403);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'catalog', 'units', $action);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
