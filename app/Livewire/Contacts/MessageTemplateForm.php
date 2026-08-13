<?php

namespace App\Livewire\Contacts;

use App\Models\User;
use App\Services\Contacts\ContactMessageTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class MessageTemplateForm extends Component
{
    public string $mode = 'create';

    public ?int $templateId = null;

    public string $name_en = '';

    public string $name_ur = '';

    public string $subject_en = '';

    public string $subject_ur = '';

    public string $body_en = '';

    public string $body_ur = '';

    public bool $is_active = true;

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('contact-message-template-form-open')]
    public function openForm(?int $templateId = null, ?string $mode = null): void
    {
        $this->resetValidation();
        $this->statusMessage = null;
        $this->mode = $mode ?? ($templateId ? 'edit' : 'create');
        $this->templateId = $templateId;

        if ($this->templateId !== null) {
            $this->loadTemplate();
        } else {
            $this->resetFields();
        }
    }

    #[On('contact-message-template-form-action')]
    public function handlePanelAction(string $action): void
    {
        if ($this->mode === 'view') {
            return;
        }

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->mode = 'create';
            $this->templateId = null;
            $this->resetFields();
            $this->setStatus(__('contact_message_templates.created'), 'success');
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view' || ! $this->canEditFields();
    }

    public function render(): View
    {
        return view('livewire.contacts.message-template-form');
    }

    private function persist(): bool
    {
        if ($this->saving || ! $this->canEditFields() || $this->mode === 'view') {
            return false;
        }

        $this->saving = true;
        $this->dispatch('contact-message-template-form-busy', saving: true);

        try {
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

            $service = app(ContactMessageTemplateService::class);
            $payload = [
                'name_en' => $this->name_en,
                'name_ur' => $this->name_ur,
                'subject_en' => $this->subject_en,
                'subject_ur' => $this->subject_ur,
                'body_en' => $this->body_en,
                'body_ur' => $this->body_ur,
                'is_active' => $this->is_active,
            ];

            if ($this->mode === 'create') {
                $template = $service->createCustomerTemplate($payload);
                $this->mode = 'edit';
                $this->templateId = $template->id;
            } elseif ($this->templateId !== null) {
                $template = $service->findCustomerTemplate($this->templateId);
                if ($template === null) {
                    $this->setStatus(__('contact_message_templates.errors.not_found'), 'error');

                    return false;
                }
                $service->updateTemplate($template, $payload);
            }

            $this->dispatch('contact-message-templates-saved');
            $this->setStatus(__('contact_message_templates.updated'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->setStatus(
                collect($exception->errors())->flatten()->first() ?: __('contact_message_templates.errors.validation'),
                'error',
            );

            return false;
        } finally {
            $this->saving = false;
            $this->dispatch('contact-message-template-form-busy', saving: false);
        }
    }

    private function loadTemplate(): void
    {
        $template = app(ContactMessageTemplateService::class)->findCustomerTemplate((int) $this->templateId);
        if ($template === null) {
            $this->resetFields();

            return;
        }

        $this->name_en = (string) $template->name_en;
        $this->name_ur = (string) ($template->name_ur ?? '');
        $this->subject_en = (string) $template->subject_en;
        $this->subject_ur = (string) ($template->subject_ur ?? '');
        $this->body_en = (string) $template->body_en;
        $this->body_ur = (string) ($template->body_ur ?? '');
        $this->is_active = $template->is_active;
    }

    private function resetFields(): void
    {
        $this->name_en = '';
        $this->name_ur = '';
        $this->subject_en = '';
        $this->subject_ur = '';
        $this->body_en = '';
        $this->body_ur = '';
        $this->is_active = true;
    }

    private function canEditFields(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'contacts', 'message-templates', 'edit');
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
