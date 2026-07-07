<?php

namespace App\Livewire\Settings;

use App\Models\PrintProfile;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class ManagePrintProfiles extends Component
{
    public ?string $flashMessage = null;

    public string $flashType = 'success';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $editForm = [];

    /** @var array<string, mixed> */
    public array $createForm = [
        'name_en' => '',
        'name_ur' => '',
        'category' => PrintProfile::CATEGORY_LABEL,
        'label_kind' => PrintProfile::LABEL_KIND_PRODUCT,
        'width_mm' => 50,
        'height_mm' => 30,
        'margin_mm' => 2,
        'orientation' => 'portrait',
        'printer_hint' => '',
    ];

    public bool $showCreate = false;

    public string $search = '';

    #[On('printing-action')]
    public function handleToolbarAction(string $action): void
    {
        if ($action === 'add-profile') {
            $this->openCreate();
        }
    }

    #[On('printing-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
    }

    public function startEdit(int $profileId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $profile = PrintProfile::query()->findOrFail($profileId);

        $this->editingId = $profile->id;
        $this->editForm = [
            'name_en' => $profile->name_en,
            'name_ur' => $profile->name_ur,
            'width_mm' => $profile->width_mm,
            'height_mm' => $profile->height_mm,
            'margin_mm' => $profile->margin_mm,
            'orientation' => $profile->orientation,
            'printer_hint' => $profile->printer_hint ?? '',
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editForm = [];
    }

    public function saveEdit(): void
    {
        if (! $this->canManage() || $this->editingId === null) {
            return;
        }

        $this->validate([
            'editForm.name_en' => ['required', 'string', 'max:120'],
            'editForm.name_ur' => ['nullable', 'string', 'max:120'],
            'editForm.width_mm' => ['required', 'numeric', 'min:10', 'max:500'],
            'editForm.height_mm' => ['nullable', 'numeric', 'min:10', 'max:500'],
            'editForm.margin_mm' => ['required', 'numeric', 'min:0', 'max:30'],
            'editForm.orientation' => ['required', 'in:portrait,landscape'],
            'editForm.printer_hint' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(PrintProfileService::class)->update($this->editingId, $this->editForm);
            $this->cancelEdit();
            $this->flash(__('agricart.printing.messages.saved'), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.printing.messages.save_failed'), 'danger');
        }
    }

    public function setDefault(int $profileId): void
    {
        if (! $this->canManage()) {
            return;
        }

        app(PrintProfileService::class)->setDefault($profileId);
        $this->flash(__('agricart.printing.messages.default_set'), 'success');
    }

    public function openCreate(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->showCreate = true;
    }

    public function cancelCreate(): void
    {
        $this->showCreate = false;
    }

    public function createProfile(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->validate([
            'createForm.name_en' => ['required', 'string', 'max:120'],
            'createForm.name_ur' => ['nullable', 'string', 'max:120'],
            'createForm.category' => ['required', 'in:document,receipt,label'],
            'createForm.label_kind' => ['nullable', 'in:product,price'],
            'createForm.width_mm' => ['required', 'numeric', 'min:10', 'max:500'],
            'createForm.height_mm' => ['nullable', 'numeric', 'min:10', 'max:500'],
            'createForm.margin_mm' => ['required', 'numeric', 'min:0', 'max:30'],
            'createForm.orientation' => ['required', 'in:portrait,landscape'],
            'createForm.printer_hint' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(PrintProfileService::class)->createCustom($this->createForm);
            $this->showCreate = false;
            $this->createForm = [
                'name_en' => '',
                'name_ur' => '',
                'category' => PrintProfile::CATEGORY_LABEL,
                'label_kind' => PrintProfile::LABEL_KIND_PRODUCT,
                'width_mm' => 50,
                'height_mm' => 30,
                'margin_mm' => 2,
                'orientation' => 'portrait',
                'printer_hint' => '',
            ];
            $this->flash(__('agricart.printing.messages.created'), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.printing.messages.save_failed'), 'danger');
        }
    }

    public function delete(int $profileId): void
    {
        if (! $this->canManage()) {
            return;
        }

        try {
            app(PrintProfileService::class)->delete($profileId);
            $this->flash(__('agricart.printing.messages.deleted'), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.printing.messages.delete_failed'), 'danger');
        }
    }

    public function render(): View
    {
        $profiles = app(PrintProfileService::class)->all();
        $needle = trim($this->search);

        if ($needle !== '') {
            $profiles = $profiles->filter(function (PrintProfile $profile) use ($needle): bool {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $profile->name_en,
                    $profile->name_ur,
                    $profile->category,
                    $profile->label_kind,
                    $profile->printer_hint,
                ])));

                return str_contains($haystack, mb_strtolower($needle));
            })->values();
        }

        return view('livewire.settings.manage-print-profiles', [
            'profiles' => $profiles,
            'canManage' => $this->canManage(),
        ]);
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('settings.printing.manage'));
    }

    private function flash(string $message, string $type): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }
}
