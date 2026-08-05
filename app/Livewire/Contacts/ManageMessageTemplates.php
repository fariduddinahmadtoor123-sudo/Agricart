<?php

namespace App\Livewire\Contacts;

use App\Models\User;
use App\Services\Contacts\ContactMessageTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class ManageMessageTemplates extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name_en = '';

    public string $name_ur = '';

    public string $subject_en = '';

    public string $subject_ur = '';

    public string $body_en = '';

    public string $body_ur = '';

    public bool $is_active = true;

    public ?string $statusMessage = null;

    public string $statusTone = 'success';

    #[On('contacts-message-templates-create')]
    public function openCreate(): void
    {
        if (! $this->userCan('edit')) {
            $this->flash(__('contact_message_templates.forbidden'), 'danger');

            return;
        }

        $this->resetForm();
        $this->showForm = true;
        $this->statusMessage = null;
    }

    public function save(ContactMessageTemplateService $service): void
    {
        if (! $this->userCan('edit')) {
            $this->flash(__('contact_message_templates.forbidden'), 'danger');

            return;
        }

        $this->validate([
            'name_en' => ['required', 'string', 'min:2', 'max:120'],
            'name_ur' => ['nullable', 'string', 'min:2', 'max:120'],
            'subject_en' => ['required', 'string', 'min:3', 'max:200'],
            'subject_ur' => ['nullable', 'string', 'min:3', 'max:200'],
            'body_en' => ['required', 'string', 'min:2', 'max:5000'],
            'body_ur' => ['nullable', 'string', 'min:2', 'max:5000'],
            'is_active' => ['boolean'],
        ], [], [
            'name_en' => __('contact_message_templates.fields.name_en'),
            'name_ur' => __('contact_message_templates.fields.name_ur'),
            'subject_en' => __('contact_message_templates.fields.subject_en'),
            'subject_ur' => __('contact_message_templates.fields.subject_ur'),
            'body_en' => __('contact_message_templates.fields.body_en'),
            'body_ur' => __('contact_message_templates.fields.body_ur'),
        ]);

        try {
            $payload = [
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur,
                'subject_en' => $this->subject_en,
                'subject_ur' => $this->subject_ur,
                'body_en' => $this->body_en,
                'body_ur' => $this->body_ur,
                'is_active' => $this->is_active,
            ];

            if ($this->editingId !== null) {
                $template = $service->findCustomerTemplate($this->editingId);
                if ($template === null) {
                    $this->flash(__('contact_message_templates.errors.not_found'), 'danger');

                    return;
                }

                $service->updateTemplate($template, $payload);
                $this->flash(__('contact_message_templates.updated'));
            } else {
                $service->createCustomerTemplate($payload);
                $this->flash(__('contact_message_templates.created'));
            }

            $this->closeForm();
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('contact_message_templates.errors.validation'), 'danger');
        }
    }

    public function edit(int $id, ContactMessageTemplateService $service): void
    {
        if (! $this->userCan('view')) {
            return;
        }

        $template = $service->findCustomerTemplate($id);
        if ($template === null) {
            return;
        }

        $this->editingId = $template->id;
        $this->name_en = (string) $template->name_en;
        $this->name_ur = (string) ($template->name_ur ?? '');
        $this->subject_en = (string) $template->subject_en;
        $this->subject_ur = (string) ($template->subject_ur ?? '');
        $this->body_en = (string) $template->body_en;
        $this->body_ur = (string) ($template->body_ur ?? '');
        $this->is_active = $template->is_active;
        $this->showForm = true;
        $this->statusMessage = null;
        $this->resetErrorBag();
    }

    public function delete(int $id, ContactMessageTemplateService $service): void
    {
        if (! $this->userCan('edit')) {
            $this->flash(__('contact_message_templates.forbidden'), 'danger');

            return;
        }

        $template = $service->findCustomerTemplate($id);
        if ($template === null) {
            return;
        }

        try {
            $service->deleteTemplate($template);
            if ($this->editingId === $id) {
                $this->closeForm();
            }
            $this->flash(__('contact_message_templates.deleted'));
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('contact_message_templates.errors.validation'), 'danger');
        }
    }

    public function toggle(int $id, ContactMessageTemplateService $service): void
    {
        if (! $this->userCan('edit')) {
            $this->flash(__('contact_message_templates.forbidden'), 'danger');

            return;
        }

        $template = $service->findCustomerTemplate($id);
        if ($template === null) {
            return;
        }

        $service->toggleActive($template);
        $this->flash(__('contact_message_templates.toggled'));
    }

    public function move(int $id, string $direction, ContactMessageTemplateService $service): void
    {
        if (! $this->userCan('edit')) {
            return;
        }

        $template = $service->findCustomerTemplate($id);
        if ($template === null) {
            return;
        }

        $service->move($template, $direction === 'up' ? 'up' : 'down');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name_en = '';
        $this->name_ur = '';
        $this->subject_en = '';
        $this->subject_ur = '';
        $this->body_en = '';
        $this->body_ur = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render(ContactMessageTemplateService $service): View
    {
        return view('livewire.contacts.manage-message-templates', [
            'templates' => $service->listForCustomer(),
            'canEdit' => $this->userCan('edit'),
            'canView' => $this->userCan('view'),
        ]);
    }

    private function flash(string $message, string $tone = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusTone = $tone;
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('contacts.'.$action);
    }
}
