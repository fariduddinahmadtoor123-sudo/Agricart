<?php

namespace App\Livewire\SecretCodings;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\SecretCodingProfile;
use App\Services\PricingControls\SecretCodingEngine;
use App\Services\PricingControls\SecretCodingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesSensitiveActions;

    public ?int $secretCodingId = null;

    public string $mode = 'create';

    public string $profile_number = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $status = 'active';

    public string $notes = '';

    /** @var array<string, string> */
    public array $digitSlots = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        if (! $this->canAccessAny(['marketplace.secret-coding.view', 'marketplace.secret-coding.edit'])) {
            return;
        }

        $this->profile_number = app(SecretCodingService::class)->nextProfileNumber();
        $this->digitSlots = SecretCodingService::emptyDigitSlots();
    }

    #[On('secret-coding-form-open')]
    public function openForm(?int $secretCodingId = null, string $mode = 'create'): void
    {
        $this->authorizeAny(
            $mode === 'view'
                ? ['marketplace.secret-coding.view', 'marketplace.secret-coding.edit']
                : 'marketplace.secret-coding.edit'
        );
        $this->resetForm();
        $this->mode = $mode;
        $this->secretCodingId = $secretCodingId;

        if ($secretCodingId) {
            $this->loadRecord($secretCodingId);
        }
    }

    #[On('secret-coding-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeAny('marketplace.secret-coding.edit');

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        $this->authorizeAny('marketplace.secret-coding.edit');

        if ($this->persist()) {
            $this->dispatch('secret-codings-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        $this->authorizeAny('marketplace.secret-coding.edit');

        if ($this->persist()) {
            $this->dispatch('secret-codings-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function sampleEncodedPrice(): string
    {
        return app(SecretCodingEngine::class)->encodeWithSlots('1500', $this->digitSlots);
    }

    public function render(): View
    {
        return view('livewire.secret-codings.form', [
            'statuses' => SecretCodingService::STATUSES,
            'digits' => SecretCodingService::DIGITS,
        ]);
    }

    private function loadRecord(int $recordId): void
    {
        $this->authorizeAny(['marketplace.secret-coding.view', 'marketplace.secret-coding.edit']);
        $record = SecretCodingProfile::query()->findOrFail($recordId);

        $this->profile_number = $record->profile_number;
        $this->name_en = $record->name_en;
        $this->name_ur = (string) ($record->name_ur ?? '');
        $this->status = $record->status;
        $this->notes = (string) ($record->notes ?? '');
        $this->digitSlots = app(SecretCodingService::class)->normalizeDigitSlots($record->digit_slots ?? []);
    }

    private function persist(): bool
    {
        $this->authorizeAny('marketplace.secret-coding.edit');

        if ($this->isReadonly()) {
            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $payload = [
                'profile_number' => $this->profile_number,
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur,
                'status' => $this->status,
                'notes' => $this->notes,
                'digit_slots' => $this->digitSlots,
            ];

            $service = app(SecretCodingService::class);

            if ($this->secretCodingId) {
                $record = SecretCodingProfile::query()->findOrFail($this->secretCodingId);
                $service->update($record, $payload);
                $this->setStatus(__('secret_coding.messages.updated'), 'success');
            } else {
                $service->create($payload);
                $this->setStatus(__('secret_coding.messages.created'), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?? __('secret_coding.messages.save_failed'), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function resetForm(): void
    {
        $this->secretCodingId = null;
        $this->mode = 'create';
        $this->profile_number = app(SecretCodingService::class)->nextProfileNumber();
        $this->name_en = '';
        $this->name_ur = '';
        $this->status = 'active';
        $this->notes = '';
        $this->digitSlots = SecretCodingService::emptyDigitSlots();
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
        $this->dispatch('secret-coding-form-busy', saving: $this->saving);
    }
}
