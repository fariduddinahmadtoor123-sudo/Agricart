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
    public ?string $statusMessage = null;

    public string $statusTone = 'success';

    #[On('contact-message-templates-saved')]
    public function refreshList(): void
    {
        $this->statusMessage = __('contact_message_templates.updated');
        $this->statusTone = 'success';
    }

    public function openCreate(): void
    {
        if (! $this->userCan('edit')) {
            $this->flash(__('contact_message_templates.forbidden'), 'danger');

            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('contact_message_templates.create'),
            resource: __('agricart.pages.contacts.message-templates'),
            formKey: 'contact-message-templates',
            mode: 'create',
        );
    }

    public function openView(int $id): void
    {
        if (! $this->userCan('view')) {
            return;
        }

        $template = app(ContactMessageTemplateService::class)->findCustomerTemplate($id);
        if ($template === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('contact_message_templates.view', ['name' => $template->localizedName()]),
            resource: __('agricart.pages.contacts.message-templates'),
            formKey: 'contact-message-templates',
            templateId: $template->id,
            mode: 'view',
        );
    }

    public function openEdit(int $id): void
    {
        if (! $this->userCan('edit')) {
            $this->flash(__('contact_message_templates.forbidden'), 'danger');

            return;
        }

        $template = app(ContactMessageTemplateService::class)->findCustomerTemplate($id);
        if ($template === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('contact_message_templates.edit_named', ['name' => $template->localizedName()]),
            resource: __('agricart.pages.contacts.message-templates'),
            formKey: 'contact-message-templates',
            templateId: $template->id,
            mode: 'edit',
        );
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
            $this->flash(__('contact_message_templates.deleted'));
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('contact_message_templates.errors.validation'), 'danger');
        }
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
