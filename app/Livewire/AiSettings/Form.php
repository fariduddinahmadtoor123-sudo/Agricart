<?php

namespace App\Livewire\AiSettings;

use App\Enums\AiProvider;
use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Services\Ai\AiConfigurationService;
use App\Services\Ai\AiProviderRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesSensitiveActions;

    public string $provider = 'openrouter';

    public string $apiKey = '';

    public string $modelSearch = '';

    public ?string $selectedModelId = null;

    public ?string $selectedModelName = null;

    /** @var list<array{id: string, name: string, description: string|null}> */
    public array $models = [];

    public bool $modelsLoaded = false;

    public bool $loadingModels = false;

    public bool $saving = false;

    public bool $testing = false;

    public ?string $statusMessage = null;

    public string $statusType = 'info';

    public function mount(AiProviderRegistry $registry): void
    {
        if (! $this->canAccessAny('settings.ai.manage')) {
            return;
        }

        $enabled = collect($registry->providerOptions())
            ->first(fn (array $option): bool => $option['enabled']);

        $this->provider = $enabled['value'] ?? AiProvider::OpenRouter->value;
    }

    public function updatedProvider(): void
    {
        $this->authorizeAny('settings.ai.manage');
        $this->resetModelState();
    }

    public function updatedApiKey(): void
    {
        $this->authorizeAny('settings.ai.manage');
        $this->resetModelState();
    }

    public function loadModels(AiProviderRegistry $registry): void
    {
        $this->authorizeAny('settings.ai.manage');

        $this->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'apiKey' => ['required', 'string', 'min:8'],
        ]);

        $provider = AiProvider::from($this->provider);

        if (! $provider->isEnabled()) {
            $this->setStatus(__('agricart.ai.messages.provider_disabled'), 'danger');

            return;
        }

        $this->loadingModels = true;
        $this->statusMessage = null;

        try {
            $service = app(AiConfigurationService::class);
            $this->models = $service->fetchModels($provider, $this->apiKey);
            $this->modelsLoaded = true;

            if ($this->models === []) {
                $this->setStatus(__('agricart.ai.messages.no_models'), 'warning');
            } else {
                $this->setStatus(__('agricart.ai.messages.models_loaded', ['count' => count($this->models)]), 'success');
            }
        } catch (\Throwable $exception) {
            $this->models = [];
            $this->modelsLoaded = false;
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->loadingModels = false;
        }
    }

    public function selectModel(string $modelId): void
    {
        $this->authorizeAny('settings.ai.manage');

        $model = collect($this->models)->firstWhere('id', $modelId);

        if ($model === null) {
            return;
        }

        $this->selectedModelId = $model['id'];
        $this->selectedModelName = $model['name'];
        $this->modelSearch = $model['name'];
        $this->setStatus(__('agricart.ai.messages.model_selected', ['name' => $model['name']]), 'success');
    }

    public function testConnection(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');

        if (! $this->validateBeforeSave()) {
            return;
        }

        $this->testing = true;

        try {
            $provider = AiProvider::from($this->provider);
            $result = $service->testDraftConnection(
                $provider,
                $this->apiKey,
                $this->selectedModelId,
                $this->selectedModelName,
            );

            $this->setStatus($result['message'], $result['success'] ? 'success' : 'danger');
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->testing = false;
        }
    }

    public function saveAndClose(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');

        if (! $this->validateBeforeSave()) {
            return;
        }

        $this->saving = true;

        try {
            $service->save([
                'provider' => $this->provider,
                'api_key' => $this->apiKey,
                'model_id' => $this->selectedModelId,
                'model_name' => $this->selectedModelName,
            ]);

            $this->dispatch('ai-settings-saved');
            $this->dispatch('close-form-panel');
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');
        } finally {
            $this->saving = false;
        }
    }

    /**
     * @return list<array{id: string, name: string, description: string|null}>
     */
    public function getFilteredModelsProperty(): array
    {
        if ($this->modelSearch === '') {
            return array_slice($this->models, 0, 40);
        }

        $needle = mb_strtolower($this->modelSearch);

        return array_values(array_filter(
            $this->models,
            fn (array $model): bool => str_contains(mb_strtolower($model['name']), $needle)
                || str_contains(mb_strtolower($model['id']), $needle),
        ));
    }

    public function render(AiProviderRegistry $registry): View
    {
        return view('livewire.ai-settings.form', [
            'providerOptions' => $this->canAccessAny('settings.ai.manage')
                ? $registry->providerOptions()
                : [],
        ]);
    }

    private function validateBeforeSave(): bool
    {
        $this->validate([
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'apiKey' => ['required', 'string', 'min:8'],
            'selectedModelId' => ['required', 'string'],
        ], [
            'selectedModelId.required' => __('agricart.ai.messages.select_model'),
        ]);

        $provider = AiProvider::from($this->provider);

        if (! $provider->isEnabled()) {
            $this->setStatus(__('agricart.ai.messages.provider_disabled'), 'danger');

            return false;
        }

        return true;
    }

    private function resetModelState(): void
    {
        $this->models = [];
        $this->modelsLoaded = false;
        $this->selectedModelId = null;
        $this->selectedModelName = null;
        $this->modelSearch = '';
        $this->statusMessage = null;
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
