<?php

namespace App\Livewire\AiSettings;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\AiConfiguration;
use App\Services\Ai\AiConfigurationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Manager extends Component
{
    use AuthorizesSensitiveActions;

    public ?AiConfiguration $activeConfiguration = null;

    public string $testPrompt = '';

    public ?string $testResponse = null;

    public ?string $testMessage = null;

    public string $testStatus = 'idle';

    public bool $testingPrompt = false;

    public function mount(AiConfigurationService $service): void
    {
        if (! $this->canAccessAny('settings.ai.manage')) {
            return;
        }

        $this->testPrompt = __('agricart.ai.manager.default_prompt');
        $this->loadActiveConfiguration($service);
    }

    #[On('ai-settings-saved')]
    public function refreshConfiguration(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');
        $this->loadActiveConfiguration($service);
        $this->resetPromptState();
    }

    public function sendTestPrompt(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');

        $this->validate([
            'testPrompt' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        if ($this->activeConfiguration === null) {
            $this->testStatus = 'danger';
            $this->testMessage = __('agricart.ai.messages.no_config');

            return;
        }

        $this->testingPrompt = true;
        $this->testResponse = null;
        $this->testMessage = null;

        try {
            $result = $service->prompt($this->activeConfiguration, $this->testPrompt);

            $this->testStatus = $result['success'] ? 'success' : 'danger';
            $this->testMessage = $result['message'];
            $this->testResponse = $result['response'];

            if ($result['success']) {
                $this->activeConfiguration = $service->testConfiguration($this->activeConfiguration);
            }
        } catch (\Throwable $exception) {
            $this->testStatus = 'danger';
            $this->testMessage = \App\Support\FriendlyError::message($exception);
        } finally {
            $this->testingPrompt = false;
        }
    }

    public function retestConnection(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');

        if ($this->activeConfiguration === null) {
            return;
        }

        $this->activeConfiguration = $service->testConfiguration($this->activeConfiguration);
        $this->testMessage = $this->activeConfiguration->last_test_message;
        $this->testStatus = $this->activeConfiguration->isConnected() ? 'success' : 'danger';
        $this->dispatch('ai-settings-saved');
    }

    public function pauseAi(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');

        if ($this->activeConfiguration === null || $this->activeConfiguration->isPaused()) {
            return;
        }

        $this->activeConfiguration = $service->pause($this->activeConfiguration);
        $this->resetPromptState();
        $this->dispatch('ai-settings-saved');
    }

    public function resumeAi(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');

        if ($this->activeConfiguration === null || ! $this->activeConfiguration->isPaused()) {
            return;
        }

        $this->activeConfiguration = $service->resume($this->activeConfiguration);
        $this->testMessage = $this->activeConfiguration->last_test_message;
        $this->testStatus = $this->activeConfiguration->isConnected() ? 'success' : 'danger';
        $this->dispatch('ai-settings-saved');
    }

    public function deleteConfiguration(AiConfigurationService $service): void
    {
        $this->authorizeAny('settings.ai.manage');

        if ($this->activeConfiguration === null) {
            return;
        }

        $service->delete($this->activeConfiguration);
        $this->activeConfiguration = null;
        $this->resetPromptState();
        $this->dispatch('ai-settings-saved');
    }

    public function render(): View
    {
        return view('livewire.ai-settings.manager');
    }

    private function loadActiveConfiguration(AiConfigurationService $service): void
    {
        $this->activeConfiguration = $service->active();
    }

    private function resetPromptState(): void
    {
        $this->testResponse = null;
        $this->testMessage = null;
        $this->testStatus = 'idle';
    }
}
