<?php

namespace App\Livewire\FormPanel;

use App\Filament\Layout\FormPanelRegistry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class DynamicForm extends Component
{
    public ?string $component = null;

    public int $instance = 0;

    #[On('form-panel-mount')]
    public function mountForm(string $formKey, string $mode = 'create'): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->canUseAdmin(), 403);

        $component = FormPanelRegistry::component($formKey, $user, $mode);
        abort_unless($component !== null, 403);

        // Always remount so the panel never paints a previous order's UI
        // (e.g. quotation bank fields) for a millisecond before openForm runs.
        $this->component = $component;
        $this->instance++;

        $this->dispatch('form-panel-mounted', formKey: $formKey);
    }

    #[On('form-panel-unmount')]
    public function unmountForm(): void
    {
        $this->component = null;
        $this->instance++;
    }

    public function render(): View
    {
        return view('livewire.form-panel.dynamic-form');
    }
}
