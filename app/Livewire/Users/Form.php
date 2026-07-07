<?php

namespace App\Livewire\Users;

use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserService;
use App\Support\PakistaniBanks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $userId = null;

    public string $mode = 'create';

    public string $user_number = '';

    public string $name = '';

    public ?int $role_id = null;

    public string $status = 'active';

    public string $email = '';

    public string $address = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var list<array{contact_person: string, phone_number: string}> */
    public array $phoneRows = [
        ['contact_person' => '', 'phone_number' => ''],
    ];

    /** @var list<array{bank_name: string, account_title: string, account_number: string}> */
    public array $bankRows = [
        ['bank_name' => '', 'account_title' => '', 'account_number' => ''],
    ];

    /** @var array<int, string> */
    public array $bankSearches = [''];

    public ?TemporaryUploadedFile $profilePhotoUpload = null;

    public ?TemporaryUploadedFile $cnicFrontUpload = null;

    public ?TemporaryUploadedFile $cnicBackUpload = null;

    public ?string $storedProfilePhotoPath = null;

    public ?string $storedCnicFrontPath = null;

    public ?string $storedCnicBackPath = null;

    public ?string $profilePhotoPreviewUrl = null;

    public ?string $cnicFrontPreviewUrl = null;

    public ?string $cnicBackPreviewUrl = null;

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('user-form-open')]
    public function openForm(?int $userId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->userId = $userId;

        if ($userId) {
            $this->loadUser($userId);
        }
    }

    #[On('user-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function updatedProfilePhotoUpload(): void
    {
        $this->validateOnly('profilePhotoUpload', [
            'profilePhotoUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->profilePhotoPreviewUrl = $this->profilePhotoUpload?->temporaryUrl();
    }

    public function updatedCnicFrontUpload(): void
    {
        $this->validateOnly('cnicFrontUpload', [
            'cnicFrontUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->cnicFrontPreviewUrl = $this->cnicFrontUpload?->temporaryUrl();
    }

    public function updatedCnicBackUpload(): void
    {
        $this->validateOnly('cnicBackUpload', [
            'cnicBackUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->cnicBackPreviewUrl = $this->cnicBackUpload?->temporaryUrl();
    }

    public function addPhoneRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->phoneRows[] = ['contact_person' => '', 'phone_number' => ''];
    }

    public function removePhoneRow(int $index): void
    {
        if ($this->isReadonly() || count($this->phoneRows) <= 1) {
            return;
        }

        unset($this->phoneRows[$index]);
        $this->phoneRows = array_values($this->phoneRows);
    }

    public function addBankRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[] = ['bank_name' => '', 'account_title' => '', 'account_number' => ''];
        $this->bankSearches[] = '';
    }

    public function removeBankRow(int $index): void
    {
        if ($this->isReadonly() || count($this->bankRows) <= 1) {
            return;
        }

        unset($this->bankRows[$index]);
        unset($this->bankSearches[$index]);
        $this->bankRows = array_values($this->bankRows);
        $this->bankSearches = array_values($this->bankSearches);
    }

    public function selectBank(int $index, string $bankName): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[$index]['bank_name'] = $bankName;
        $this->bankSearches[$index] = '';
    }

    public function clearBank(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[$index]['bank_name'] = '';
        $this->bankSearches[$index] = '';
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('users-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('users-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.users.form', [
            'roleOptions' => Role::query()->orderBy('name')->pluck('name', 'id')->all(),
            'statusOptions' => collect(UserService::statuses())
                ->mapWithKeys(fn (string $status): array => [
                    $status => __("agricart.users.statuses.{$status}"),
                ])
                ->all(),
            'filteredBanksByRow' => $this->filteredBanksByRow(),
        ]);
    }

    /**
     * @return array<int, list<string>>
     */
    private function filteredBanksByRow(): array
    {
        $banks = PakistaniBanks::englishNames();
        $results = [];

        foreach ($this->bankSearches as $index => $search) {
            $needle = mb_strtolower(trim($search));

            if ($needle === '') {
                $results[$index] = [];

                continue;
            }

            $results[$index] = array_values(array_filter(
                $banks,
                fn (string $bank): bool => str_contains(mb_strtolower($bank), $needle),
            ));
        }

        return $results;
    }

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        $user = auth()->user();

        if (! $user instanceof \App\Models\User || ! $user->hasPermission('settings.users.manage')) {
            $this->setStatus(__('agricart.access.denied'), 'danger');

            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'role_id' => ['required', 'integer', 'exists:roles,id'],
                'status' => ['required', Rule::in(UserService::statuses())],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($this->userId),
                ],
                'address' => ['nullable', 'string', 'max:2000'],
                'phoneRows.*.phone_number' => ['nullable', 'string', 'max:30'],
                'phoneRows.*.contact_person' => ['nullable', 'string', 'max:255'],
                'bankRows.*.bank_name' => ['nullable', 'string', 'max:255'],
                'bankRows.*.account_title' => ['nullable', 'string', 'max:255'],
                'bankRows.*.account_number' => ['nullable', 'string', 'max:64'],
                'profilePhotoUpload' => ['nullable', 'image', 'max:5120'],
                'cnicFrontUpload' => ['nullable', 'image', 'max:5120'],
                'cnicBackUpload' => ['nullable', 'image', 'max:5120'],
            ];

            if ($this->mode === 'create') {
                $rules['password'] = ['required', 'string', 'min:8', 'same:password_confirmation'];
                $rules['password_confirmation'] = ['required', 'string'];
            } elseif (filled($this->password)) {
                $rules['password'] = ['string', 'min:8', 'same:password_confirmation'];
                $rules['password_confirmation'] = ['required_with:password', 'string'];
            }

            $this->validate($rules);
            $this->persistUploadedFiles();

            $service = app(UserService::class);
            $payload = [
                'user_number' => filled($this->user_number) ? $this->user_number : null,
                'name' => $this->name,
                'role_id' => $this->role_id,
                'status' => $this->status,
                'email' => $this->email,
                'address' => $this->address ?: null,
                'phone_numbers' => $service->normalizePhoneRows($this->phoneRows),
                'bank_accounts' => $service->normalizeBankRows($this->bankRows),
                'profile_photo_path' => $this->storedProfilePhotoPath,
                'cnic_front_path' => $this->storedCnicFrontPath,
                'cnic_back_path' => $this->storedCnicBackPath,
            ];

            if ($this->mode === 'create' || filled($this->password)) {
                $payload['password'] = $this->password;
            }

            if ($this->userId) {
                $user = User::query()->findOrFail($this->userId);
                $user = $service->update($user, $payload);
                $this->user_number = (string) ($user->user_number ?? '');
                $this->setStatus(__('agricart.users.messages.updated', ['name' => $this->name]), 'success');
            } else {
                $user = $service->create($payload);
                $this->userId = $user->id;
                $this->user_number = (string) ($user->user_number ?? '');
                $this->mode = 'edit';
                $this->password = '';
                $this->password_confirmation = '';
                $this->setStatus(__('agricart.users.messages.created', ['name' => $this->name]), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.users.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus($exception->getMessage(), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function loadUser(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->user_number = (string) ($user->user_number ?? '');
        $this->name = $user->name;
        $this->role_id = $user->role_id;
        $this->status = $user->status ?? 'active';
        $this->email = $user->email;
        $this->address = (string) ($user->address ?? '');

        $phones = is_array($user->phone_numbers) ? $user->phone_numbers : [];
        $this->phoneRows = $phones !== []
            ? array_values(array_map(static fn (array $row): array => [
                'contact_person' => (string) ($row['contact_person'] ?? ''),
                'phone_number' => (string) ($row['phone_number'] ?? ''),
            ], $phones))
            : [['contact_person' => '', 'phone_number' => '']];

        $banks = is_array($user->bank_accounts) ? $user->bank_accounts : [];
        $this->bankRows = $banks !== []
            ? array_values(array_map(static fn (array $row): array => [
                'bank_name' => (string) ($row['bank_name'] ?? ''),
                'account_title' => (string) ($row['account_title'] ?? ''),
                'account_number' => (string) ($row['account_number'] ?? ''),
            ], $banks))
            : [['bank_name' => '', 'account_title' => '', 'account_number' => '']];

        $this->bankSearches = array_fill(0, count($this->bankRows), '');

        $this->storedProfilePhotoPath = $user->profile_photo_path;
        $this->storedCnicFrontPath = $user->cnic_front_path;
        $this->storedCnicBackPath = $user->cnic_back_path;
        $this->profilePhotoPreviewUrl = $this->storageUrl($user->profile_photo_path);
        $this->cnicFrontPreviewUrl = $this->storageUrl($user->cnic_front_path);
        $this->cnicBackPreviewUrl = $this->storageUrl($user->cnic_back_path);
    }

    private function persistUploadedFiles(): void
    {
        if ($this->profilePhotoUpload !== null) {
            $this->storedProfilePhotoPath = $this->profilePhotoUpload->store('users/profile-photos', 'public');
            $this->profilePhotoPreviewUrl = Storage::disk('public')->url($this->storedProfilePhotoPath);
            $this->profilePhotoUpload = null;
        }

        if ($this->cnicFrontUpload !== null) {
            $this->storedCnicFrontPath = $this->cnicFrontUpload->store('users/cnic', 'public');
            $this->cnicFrontPreviewUrl = Storage::disk('public')->url($this->storedCnicFrontPath);
            $this->cnicFrontUpload = null;
        }

        if ($this->cnicBackUpload !== null) {
            $this->storedCnicBackPath = $this->cnicBackUpload->store('users/cnic', 'public');
            $this->cnicBackPreviewUrl = Storage::disk('public')->url($this->storedCnicBackPath);
            $this->cnicBackUpload = null;
        }
    }

    private function storageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'userId',
            'user_number',
            'name',
            'role_id',
            'status',
            'email',
            'address',
            'password',
            'password_confirmation',
            'profilePhotoUpload',
            'cnicFrontUpload',
            'cnicBackUpload',
            'storedProfilePhotoPath',
            'storedCnicFrontPath',
            'storedCnicBackPath',
            'profilePhotoPreviewUrl',
            'cnicFrontPreviewUrl',
            'cnicBackPreviewUrl',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->status = User::STATUS_PENDING;
        $this->statusType = 'success';
        $this->saving = false;
        $this->phoneRows = [['contact_person' => '', 'phone_number' => '']];
        $this->bankRows = [['bank_name' => '', 'account_title' => '', 'account_number' => '']];
        $this->bankSearches = [''];
        $this->assignDefaultRole();
    }

    private function assignDefaultRole(): void
    {
        if ($this->role_id !== null) {
            return;
        }

        $defaultRoleId = Role::query()->where('slug', 'super-admin')->value('id');

        if ($defaultRoleId) {
            $this->role_id = (int) $defaultRoleId;
        }
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('user-form-busy', saving: $this->saving);
    }
}
