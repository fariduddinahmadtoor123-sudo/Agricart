<?php

namespace App\Livewire\Controls;

use App\Enums\CategoryAiStatus;
use App\Models\CatalogControl;
use App\Models\User;
use App\Services\Catalog\ControlEnrichmentService;
use App\Services\Catalog\ControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Form extends Component
{
    #[Locked]
    public ?int $controlId = null;

    #[Locked]
    public string $mode = 'create';

    public string $name_en = '';

    public string $name_ur = '';

    public string $value_en = '';

    public string $value_ur = '';

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

    public function openForm(?int $controlId = null, string $mode = 'create'): void
    {
        $normalizedMode = in_array($mode, ['create', 'edit', 'view'], true) ? $mode : 'create';
        $this->authorizeAction(
            $controlId === null ? 'create' : ($normalizedMode === 'view' ? 'view' : 'edit'),
        );
        $this->resetForm();
        $this->mode = $normalizedMode;
        $this->controlId = $controlId;

        if ($controlId) {
            $this->loadControl($controlId);
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

        if (! filled(trim($this->name_en))
            && ! filled(trim($this->name_ur))
            && ! filled(trim($this->value_en))
            && ! filled(trim($this->value_ur))) {
            $this->setStatus(__('agricart.controls.ai.input_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        $aiLock = Cache::lock(
            'control-ai-enrichment:'.(auth()->id() ?? 'guest').':'.($this->controlId ?? 'new'),
            120,
        );

        if (! $aiLock->get()) {
            $this->setStatus(__('agricart.controls.ai.already_running'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(ControlEnrichmentService::class)->enrich(
                $this->name_en,
                $this->name_ur,
                $this->value_en,
                $this->value_ur,
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
        if (! $this->persist()) {
            return;
        }

        $this->dispatch('controls-saved');
        $this->dispatch('close-form-panel');
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('controls-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.controls.form');
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

        foreach ([
            'name_en' => __('agricart.controls.fields.name_en'),
            'name_ur' => __('agricart.controls.fields.name_ur'),
            'value_en' => __('agricart.controls.fields.value_en'),
            'value_ur' => __('agricart.controls.fields.value_ur'),
        ] as $field => $label) {
            if (! filled(trim($this->{$field}))) {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.controls.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );
            $this->dispatchBusyState();

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(ControlService::class);
            $payload = $this->buildPayload();

            if ($this->controlId) {
                $control = CatalogControl::query()->findOrFail($this->controlId);
                $service->update($control, $payload);
            } else {
                $control = $service->create($payload);
                $this->controlId = $control->id;
                $this->code = $control->code;
                $this->mode = 'edit';
            }

            $this->setStatus(__('agricart.controls.messages.saved'), 'success');

            return true;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.controls.messages.validation_failed'), 'danger');

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
        $this->dispatch('control-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        return [
            'status' => $this->status ?: 'active',
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'value_en' => $this->value_en,
            'value_ur' => $this->value_ur,
            'ai_status' => $this->ai_status ?: 'none',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['name_en', 'name_ur', 'value_en', 'value_ur'] as $field) {
            $value = $data[$field] ?? null;

            if (! is_string($value) || ! filled(trim($value))) {
                continue;
            }

            if (! filled(trim($this->{$field}))) {
                $this->{$field} = trim($value);
            }
        }

        if (filled($data['ai_status'] ?? null)) {
            $this->ai_status = (string) $data['ai_status'];
        }
    }

    private function loadControl(int $controlId): void
    {
        $control = CatalogControl::query()->findOrFail($controlId);

        $this->name_en = $control->name_en;
        $this->name_ur = $control->name_ur;
        $this->value_en = $control->value_en;
        $this->value_ur = $control->value_ur;
        $this->code = $control->code;
        $this->slug = $control->slug;
        $this->status = $control->status;
        $this->ai_status = $control->ai_status?->value ?? 'none';
    }

    private function resetForm(): void
    {
        $this->controlId = null;
        $this->mode = 'create';
        $this->name_en = '';
        $this->name_ur = '';
        $this->value_en = '';
        $this->value_ur = '';
        $this->code = app(ControlService::class)->nextCode();
        $this->slug = '';
        $this->status = 'active';
        $this->ai_status = 'none';
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function mutationAction(): string
    {
        return $this->controlId === null ? 'create' : 'edit';
    }

    private function authorizeAction(string $action): void
    {
        abort_unless($this->userCan($action), 403);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->canUseAdmin()
            && ($user->isSuperAdmin() || $user->hasPermission('catalog.'.$action));
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
