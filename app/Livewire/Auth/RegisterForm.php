<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Access\UserService;
use App\Support\PakistaniBanks;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class RegisterForm extends Component
{
    use WithFileUploads;

    public string $name = '';

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

    public ?string $profilePhotoPreviewUrl = null;

    public ?string $cnicFrontPreviewUrl = null;

    public ?string $cnicBackPreviewUrl = null;

    public bool $saving = false;

    public bool $submitted = false;

    public bool $bootstrapOwnerRegistered = false;

    public bool $isResubmit = false;

    public ?string $statusMessage = null;

    public string $statusType = 'danger';

    public function updatedProfilePhotoUpload(): void
    {
        $this->validateOnly('profilePhotoUpload', ['profilePhotoUpload' => ['nullable', 'image', 'max:5120']]);
        $this->profilePhotoPreviewUrl = $this->profilePhotoUpload?->temporaryUrl();
    }

    public function updatedCnicFrontUpload(): void
    {
        $this->validateOnly('cnicFrontUpload', ['cnicFrontUpload' => ['nullable', 'image', 'max:5120']]);
        $this->cnicFrontPreviewUrl = $this->cnicFrontUpload?->temporaryUrl();
    }

    public function updatedCnicBackUpload(): void
    {
        $this->validateOnly('cnicBackUpload', ['cnicBackUpload' => ['nullable', 'image', 'max:5120']]);
        $this->cnicBackPreviewUrl = $this->cnicBackUpload?->temporaryUrl();
    }

    public function addPhoneRow(): void
    {
        $this->phoneRows[] = ['contact_person' => '', 'phone_number' => ''];
    }

    public function removePhoneRow(int $index): void
    {
        if (count($this->phoneRows) <= 1) {
            return;
        }

        unset($this->phoneRows[$index]);
        $this->phoneRows = array_values($this->phoneRows);
    }

    public function addBankRow(): void
    {
        $this->bankRows[] = ['bank_name' => '', 'account_title' => '', 'account_number' => ''];
        $this->bankSearches[] = '';
    }

    public function removeBankRow(int $index): void
    {
        if (count($this->bankRows) <= 1) {
            return;
        }

        unset($this->bankRows[$index]);
        unset($this->bankSearches[$index]);
        $this->bankRows = array_values($this->bankRows);
        $this->bankSearches = array_values($this->bankSearches);
    }

    public function selectBank(int $index, string $bankName): void
    {
        $this->bankRows[$index]['bank_name'] = $bankName;
        $this->bankSearches[$index] = '';
    }

    public function clearBank(int $index): void
    {
        $this->bankRows[$index]['bank_name'] = '';
        $this->bankSearches[$index] = '';
    }

    public function submit(): void
    {
        $this->saving = true;
        $this->statusMessage = null;

        try {
            $rejectedUser = User::query()
                ->where('email', $this->email)
                ->where('status', User::STATUS_REJECTED)
                ->first();

            $this->isResubmit = $rejectedUser !== null;

            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($rejectedUser?->id),
                ],
                'address' => ['nullable', 'string', 'max:2000'],
                'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
                'password_confirmation' => ['required', 'string'],
                'phoneRows.*.phone_number' => ['nullable', 'string', 'max:30'],
                'phoneRows.*.contact_person' => ['nullable', 'string', 'max:255'],
                'bankRows.*.bank_name' => ['nullable', 'string', 'max:255'],
                'bankRows.*.account_title' => ['nullable', 'string', 'max:255'],
                'bankRows.*.account_number' => ['nullable', 'string', 'max:64'],
                'profilePhotoUpload' => ['nullable', 'image', 'max:5120'],
                'cnicFrontUpload' => ['nullable', 'image', 'max:5120'],
                'cnicBackUpload' => ['nullable', 'image', 'max:5120'],
            ]);

            $service = app(UserService::class);

            $user = $service->registerPublic([
                'name' => $this->name,
                'email' => $this->email,
                'address' => $this->address ?: null,
                'password' => $this->password,
                'phone_numbers' => $service->normalizePhoneRows($this->phoneRows),
                'bank_accounts' => $service->normalizeBankRows($this->bankRows),
                'profile_photo_path' => $this->storeUpload($this->profilePhotoUpload, 'users/profile-photos'),
                'cnic_front_path' => $this->storeUpload($this->cnicFrontUpload, 'users/cnic'),
                'cnic_back_path' => $this->storeUpload($this->cnicBackUpload, 'users/cnic'),
            ]);

            $this->bootstrapOwnerRegistered = $user->isSuperAdmin() && $user->status === User::STATUS_ACTIVE;

            $this->submitted = true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->statusMessage = $message ?: __('agricart.auth.register_failed');
            $this->statusType = 'danger';
        } catch (\Throwable $exception) {
            $this->statusMessage = \App\Support\FriendlyError::message($exception);
            $this->statusType = 'danger';
        } finally {
            $this->saving = false;
        }
    }

    public function render(): View
    {
        return view('livewire.auth.register-form', [
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

    private function storeUpload(?TemporaryUploadedFile $upload, string $directory): ?string
    {
        if ($upload === null) {
            return null;
        }

        return $upload->store($directory, 'public');
    }
}
