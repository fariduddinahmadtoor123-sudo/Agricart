<?php

namespace App\Livewire\Users;

use App\Enums\CategoryAiStatus;
use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserEnrichmentService;
use App\Services\Access\UserService;
use App\Services\Staff\StaffPermissions;
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
    use AuthorizesSensitiveActions, WithFileUploads;

    public ?int $userId = null;

    public string $mode = 'create';

    /** @var 'settings'|'staff' */
    public string $formContext = 'settings';

    public string $user_number = '';

    public string $name = '';

    public string $name_ur = '';

    public ?int $role_id = null;

    public string $status = 'active';

    public string $email = '';

    public string $address = '';

    public string $address_ur = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var list<array{contact_person: string, contact_person_ur: string, phone_number: string}> */
    public array $phoneRows = [
        ['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => ''],
    ];

    /** @var list<array{bank_name: string, account_title: string, account_title_ur: string, account_number: string}> */
    public array $bankRows = [
        ['bank_name' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''],
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

    public bool $enriching = false;

    public string $ai_status = 'none';

    public bool $suppressAiEnrich = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('user-form-open')]
    public function openForm(?int $userId = null, string $mode = 'create', string $formContext = 'settings'): void
    {
        $context = $formContext === 'staff' ? 'staff' : 'settings';
        $this->authorizeUserForm($context, $userId, $mode);
        $this->formContext = $context;
        $this->resetForm();
        $this->mode = $mode;
        $this->userId = $userId;

        if ($userId) {
            if (! $this->loadUser($userId)) {
                $this->userId = null;
                $this->mode = 'create';
            }
        }
    }

    #[On('user-form-action')]
    public function handlePanelAction(string $action): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            'ai' => $this->enrichWithAi(),
            default => null,
        };
    }

    public function updated($property): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->suppressAiEnrich || $this->isReadonly() || $this->enriching) {
            return;
        }

        if (preg_match('/^(name_ur|name)$/', (string) $property)) {
            $this->maybeAutoEnrichFromField();

            return;
        }

        if (preg_match('/^phoneRows\.\d+\.(contact_person_ur|contact_person)$/', (string) $property)
            || preg_match('/^bankRows\.\d+\.account_title(_ur)?$/', (string) $property)) {
            $this->maybeAutoEnrichFromField();
        }
    }

    public function enrichWithAi(bool $fromAuto = false): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->isReadonly()) {
            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        if (! $fromAuto) {
            $this->statusMessage = null;
        }
        $this->dispatchBusyState();

        try {
            $result = app(UserEnrichmentService::class)->enrich($this->enrichmentPayload());

            if (! $result['success']) {
                $this->ai_status = CategoryAiStatus::Failed->value;
                if (! $fromAuto || ! str_contains($result['message'], __('agricart.users.ai.input_required'))) {
                    $this->setStatus($result['message'], $fromAuto ? 'warning' : 'danger');
                }

                return;
            }

            $this->applyEnrichmentData($result['data'] ?? [], $fromAuto);
            $this->ai_status = CategoryAiStatus::Enriched->value;

            if (! $fromAuto) {
                $this->setStatus($result['message'], 'success');
            }
        } catch (\Throwable) {
            $this->ai_status = CategoryAiStatus::Failed->value;
            if (! $fromAuto) {
                $this->setStatus(__('agricart.users.ai.failed'), 'danger');
            }
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    private function maybeAutoEnrichFromField(): void
    {
        $this->enrichWithAi(fromAuto: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrichmentPayload(): array
    {
        return [
            'name_en' => $this->name,
            'name_ur' => $this->name_ur,
            'phone_rows' => array_map(static fn (array $row): array => [
                'contact_person' => (string) ($row['contact_person'] ?? ''),
                'contact_person_ur' => (string) ($row['contact_person_ur'] ?? ''),
            ], $this->phoneRows),
            'bank_rows' => array_map(static fn (array $row): array => [
                'bank_name' => (string) ($row['bank_name'] ?? ''),
                'account_title' => (string) ($row['account_title'] ?? ''),
                'account_title_ur' => (string) ($row['account_title_ur'] ?? ''),
            ], $this->bankRows),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data, bool $fromAuto = false): void
    {
        $this->suppressAiEnrich = true;

        if (filled($data['name_en'] ?? null) && blank($this->name)) {
            $this->name = (string) $data['name_en'];
        }

        if (filled($data['name_ur'] ?? null) && blank($this->name_ur)) {
            $this->name_ur = (string) $data['name_ur'];
        }

        if (filled($data['address_en'] ?? null) && blank($this->address)) {
            $this->address = (string) $data['address_en'];
        }

        if (filled($data['address_ur'] ?? null) && blank($this->address_ur)) {
            $this->address_ur = (string) $data['address_ur'];
        }

        if (isset($data['phone_rows']) && is_array($data['phone_rows'])) {
            $this->mergePhoneEnrichmentRows($data['phone_rows']);
        }

        if (isset($data['bank_rows']) && is_array($data['bank_rows'])) {
            $this->mergeBankEnrichmentRows($data['bank_rows']);
        }

        $this->suppressAiEnrich = false;
    }

    /**
     * @param  list<mixed>  $rows
     */
    private function mergePhoneEnrichmentRows(array $rows): void
    {
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! isset($this->phoneRows[$index])) {
                $this->phoneRows[] = ['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => ''];
            }

            if (filled($row['contact_person'] ?? null) && blank($this->phoneRows[$index]['contact_person'] ?? '')) {
                $this->phoneRows[$index]['contact_person'] = (string) $row['contact_person'];
            }

            if (filled($row['contact_person_ur'] ?? null) && blank($this->phoneRows[$index]['contact_person_ur'] ?? '')) {
                $this->phoneRows[$index]['contact_person_ur'] = (string) $row['contact_person_ur'];
            }

            if (filled($row['phone_number'] ?? null) && blank($this->phoneRows[$index]['phone_number'])) {
                $this->phoneRows[$index]['phone_number'] = (string) $row['phone_number'];
            }
        }
    }

    /**
     * @param  list<mixed>  $rows
     */
    private function mergeBankEnrichmentRows(array $rows): void
    {
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! isset($this->bankRows[$index])) {
                $this->bankRows[] = ['bank_name' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''];
                $this->bankSearches[] = '';
            }

            if (filled($row['account_title'] ?? null) && blank($this->bankRows[$index]['account_title'] ?? '')) {
                $this->bankRows[$index]['account_title'] = (string) $row['account_title'];
            }

            if (filled($row['account_title_ur'] ?? null) && blank($this->bankRows[$index]['account_title_ur'] ?? '')) {
                $this->bankRows[$index]['account_title_ur'] = (string) $row['account_title_ur'];
            }
        }
    }

    public function updatedProfilePhotoUpload(): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);
        $this->validateOnly('profilePhotoUpload', [
            'profilePhotoUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->profilePhotoPreviewUrl = $this->profilePhotoUpload?->temporaryUrl();
    }

    public function updatedCnicFrontUpload(): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);
        $this->validateOnly('cnicFrontUpload', [
            'cnicFrontUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->cnicFrontPreviewUrl = $this->cnicFrontUpload?->temporaryUrl();
    }

    public function updatedCnicBackUpload(): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);
        $this->validateOnly('cnicBackUpload', [
            'cnicBackUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->cnicBackPreviewUrl = $this->cnicBackUpload?->temporaryUrl();
    }

    public function addPhoneRow(): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->isReadonly()) {
            return;
        }

        $this->phoneRows[] = ['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => ''];
    }

    public function removePhoneRow(int $index): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->isReadonly() || count($this->phoneRows) <= 1) {
            return;
        }

        unset($this->phoneRows[$index]);
        $this->phoneRows = array_values($this->phoneRows);
    }

    public function addBankRow(): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[] = ['bank_name' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''];
        $this->bankSearches[] = '';
    }

    public function removeBankRow(int $index): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

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
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[$index]['bank_name'] = $bankName;
        $this->bankSearches[$index] = '';
    }

    public function clearBank(int $index): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->isReadonly()) {
            return;
        }

        $this->bankRows[$index]['bank_name'] = '';
        $this->bankSearches[$index] = '';
    }

    public function saveAndClose(): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->persist()) {
            $this->dispatch('users-saved');
            $this->dispatch('staff-members-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->persist()) {
            $this->dispatch('users-saved');
            $this->dispatch('staff-members-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        $authorized = $this->canAccessFormOptions();
        $statuses = $this->formContext === 'staff'
            ? [User::STATUS_ACTIVE, User::STATUS_INACTIVE]
            : UserService::statuses();

        return view('livewire.users.form', [
            'roleOptions' => $authorized
                ? Role::query()->orderBy('name')->pluck('name', 'id')->all()
                : [],
            'statusOptions' => collect($statuses)
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
        $this->authorizeUserForm($this->formContext, $this->userId);

        if ($this->isReadonly()) {
            return false;
        }

        $user = auth()->user();

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $allowedStatuses = $this->formContext === 'staff'
                ? [User::STATUS_ACTIVE, User::STATUS_INACTIVE]
                : UserService::statuses();

            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'name_ur' => ['nullable', 'string', 'max:255'],
                'role_id' => ['required', 'integer', 'exists:roles,id'],
                'status' => ['required', Rule::in($allowedStatuses)],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($this->userId),
                ],
                'address' => ['nullable', 'string', 'max:2000'],
                'address_ur' => ['nullable', 'string', 'max:2000'],
                'phoneRows.*.phone_number' => ['nullable', 'string', 'max:30'],
                'phoneRows.*.contact_person' => ['nullable', 'string', 'max:255'],
                'phoneRows.*.contact_person_ur' => ['nullable', 'string', 'max:255'],
                'bankRows.*.bank_name' => ['nullable', 'string', 'max:255'],
                'bankRows.*.account_title' => ['nullable', 'string', 'max:255'],
                'bankRows.*.account_title_ur' => ['nullable', 'string', 'max:255'],
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
                'name_ur' => $this->name_ur ?: null,
                'role_id' => $this->role_id,
                'status' => $this->status,
                'email' => $this->email,
                'address' => $this->address ?: null,
                'address_ur' => $this->address_ur ?: null,
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
                $messageKey = $this->formContext === 'staff'
                    ? 'agricart.staff.members.messages.updated'
                    : 'agricart.users.messages.updated';
                $this->setStatus(__($messageKey, ['name' => $this->name]), 'success');
            } else {
                $user = $service->create($payload);
                $this->userId = $user->id;
                $this->user_number = (string) ($user->user_number ?? '');
                $this->mode = 'edit';
                $this->password = '';
                $this->password_confirmation = '';
                $messageKey = $this->formContext === 'staff'
                    ? 'agricart.staff.members.messages.created'
                    : 'agricart.users.messages.created';
                $this->setStatus(__($messageKey, ['name' => $this->name]), 'success');
            }

            $this->bindDocumentMediaNames((int) $this->userId);
            $this->cnicFrontPreviewUrl = $this->secureDocumentUrlForId((int) $this->userId, 'front', $this->storedCnicFrontPath);
            $this->cnicBackPreviewUrl = $this->secureDocumentUrlForId((int) $this->userId, 'back', $this->storedCnicBackPath);

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $failedKey = $this->formContext === 'staff'
                ? 'agricart.staff.members.messages.save_failed'
                : 'agricart.users.messages.save_failed';
            $this->setStatus($message ?: __($failedKey), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function loadUser(int $userId): bool
    {
        $user = User::query()->findOrFail($userId);

        if ($this->formContext === 'staff' && ! in_array($user->status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true)) {
            $this->setStatus(__('agricart.staff.members.messages.not_on_roster'), 'danger');

            return false;
        }

        $this->user_number = (string) ($user->user_number ?? '');
        $this->name = $user->name;
        $this->name_ur = (string) ($user->name_ur ?? '');
        $this->role_id = $user->role_id;
        $this->status = $user->status ?? 'active';
        $this->email = $user->email;
        $this->address = (string) ($user->address ?? '');
        $this->address_ur = (string) ($user->address_ur ?? '');

        $phones = is_array($user->phone_numbers) ? $user->phone_numbers : [];
        $this->phoneRows = $phones !== []
            ? array_values(array_map(static fn (array $row): array => [
                'contact_person' => (string) ($row['contact_person'] ?? ''),
                'contact_person_ur' => (string) ($row['contact_person_ur'] ?? ''),
                'phone_number' => (string) ($row['phone_number'] ?? ''),
            ], $phones))
            : [['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => '']];

        $banks = is_array($user->bank_accounts) ? $user->bank_accounts : [];
        $this->bankRows = $banks !== []
            ? array_values(array_map(static fn (array $row): array => [
                'bank_name' => (string) ($row['bank_name'] ?? ''),
                'account_title' => (string) ($row['account_title'] ?? ''),
                'account_title_ur' => (string) ($row['account_title_ur'] ?? ''),
                'account_number' => (string) ($row['account_number'] ?? ''),
            ], $banks))
            : [['bank_name' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => '']];

        $this->bankSearches = array_fill(0, count($this->bankRows), '');

        $this->storedProfilePhotoPath = $user->profile_photo_path;
        $this->storedCnicFrontPath = $user->cnic_front_path;
        $this->storedCnicBackPath = $user->cnic_back_path;
        $this->profilePhotoPreviewUrl = $this->storageUrl($user->profile_photo_path);
        $this->cnicFrontPreviewUrl = $this->secureDocumentUrl($user, 'front');
        $this->cnicBackPreviewUrl = $this->secureDocumentUrl($user, 'back');

        return true;
    }

    private function persistUploadedFiles(): void
    {
        $storage = app(\App\Services\Media\OptimizedImageStorage::class);
        $person = $this->documentPersonName();

        if ($this->profilePhotoUpload !== null) {
            $label = $person.' — Profile photo';
            $this->storedProfilePhotoPath = $storage->store(
                $this->profilePhotoUpload,
                'users/profile-photos',
                originalName: $label.'.webp',
                displayName: $label,
                metadata: array_filter(['user_id' => $this->userId]),
            );
            $this->profilePhotoPreviewUrl = Storage::disk('public')->url($this->storedProfilePhotoPath);
            $this->profilePhotoUpload = null;
        }

        if ($this->cnicFrontUpload !== null) {
            $label = $person.' — ID card front';
            $this->storedCnicFrontPath = $storage->store(
                $this->cnicFrontUpload,
                'users/cnic',
                disk: 'local',
                originalName: $label.'.jpg',
                displayName: $label,
                metadata: array_filter([
                    'user_id' => $this->userId,
                    'side' => 'front',
                ]),
            );
            $this->cnicFrontUpload = null;
            if ($this->userId) {
                $this->cnicFrontPreviewUrl = $this->secureDocumentUrlForId($this->userId, 'front', $this->storedCnicFrontPath);
            }
        }

        if ($this->cnicBackUpload !== null) {
            $label = $person.' — ID card back';
            $this->storedCnicBackPath = $storage->store(
                $this->cnicBackUpload,
                'users/cnic',
                disk: 'local',
                originalName: $label.'.jpg',
                displayName: $label,
                metadata: array_filter([
                    'user_id' => $this->userId,
                    'side' => 'back',
                ]),
            );
            $this->cnicBackUpload = null;
            if ($this->userId) {
                $this->cnicBackPreviewUrl = $this->secureDocumentUrlForId($this->userId, 'back', $this->storedCnicBackPath);
            }
        }
    }

    private function documentPersonName(): string
    {
        $name = trim($this->name);

        return $name !== '' ? $name : 'Staff';
    }

    private function bindDocumentMediaNames(int $userId): void
    {
        $library = app(\App\Services\Media\MediaLibraryService::class);
        $person = $this->documentPersonName();

        if (filled($this->storedProfilePhotoPath)) {
            $library->registerPath(
                $this->storedProfilePhotoPath,
                $person.' — Profile photo.webp',
                $person.' — Profile photo',
                'users',
                'public',
                ['user_id' => $userId],
            );
        }

        if (filled($this->storedCnicFrontPath)) {
            $library->registerPath(
                $this->storedCnicFrontPath,
                $person.' — ID card front.jpg',
                $person.' — ID card front',
                'users',
                'local',
                ['user_id' => $userId, 'side' => 'front'],
            );
        }

        if (filled($this->storedCnicBackPath)) {
            $library->registerPath(
                $this->storedCnicBackPath,
                $person.' — ID card back.jpg',
                $person.' — ID card back',
                'users',
                'local',
                ['user_id' => $userId, 'side' => 'back'],
            );
        }
    }

    private function storageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    private function secureDocumentUrl(User $user, string $side): ?string
    {
        $path = $side === 'front' ? $user->cnic_front_path : $user->cnic_back_path;

        return $this->secureDocumentUrlForId($user->id, $side, $path);
    }

    private function secureDocumentUrlForId(int $userId, string $side, ?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return route('admin.users.documents.show', [
            'user' => $userId,
            'side' => $side,
        ], absolute: false);
    }

    private function resetForm(): void
    {
        $this->reset([
            'userId',
            'user_number',
            'name',
            'name_ur',
            'role_id',
            'status',
            'email',
            'address',
            'address_ur',
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
        $this->status = $this->formContext === 'staff' ? User::STATUS_ACTIVE : User::STATUS_PENDING;
        $this->statusType = 'success';
        $this->saving = false;
        $this->phoneRows = [['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => '']];
        $this->bankRows = [['bank_name' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => '']];
        $this->bankSearches = [''];

        if ($this->formContext === 'settings') {
            $this->assignDefaultRole();
        } else {
            $this->role_id = null;
        }
    }

    private function canManageForm(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->formContext === 'staff') {
            if ($this->userId) {
                return $user->hasPermission('staff.edit');
            }

            return $user->hasPermission('staff.create') || $user->hasPermission('staff.edit');
        }

        return $user->hasPermission('settings.users.manage');
    }

    private function canAccessFormOptions(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->formContext === 'staff') {
            return StaffPermissions::canViewRoster($user) || StaffPermissions::canManageRoster($user);
        }

        return $user->hasPermission('settings.users.manage');
    }

    private function authorizeUserForm(string $context, ?int $userId, ?string $mode = null): void
    {
        $mode ??= $this->mode;

        if ($context === 'staff') {
            $permissions = match (true) {
                $mode === 'view' => ['staff.view', 'staff.edit', 'staff.create'],
                $userId !== null => ['staff.edit'],
                default => ['staff.create', 'staff.edit'],
            };
        } else {
            $permissions = ['settings.users.manage'];
        }

        $this->authorizeAny($permissions);
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
        $this->dispatch('user-form-busy', enriching: $this->enriching, saving: $this->saving);
    }
}
