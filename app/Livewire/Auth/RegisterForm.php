<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Access\UserService;
use App\Services\Media\MediaLibraryService;
use App\Services\Media\OptimizedImageStorage;
use App\Support\FriendlyError;
use App\Support\PakistaniBanks;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class RegisterForm extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $name_ur = '';

    public string $email = '';

    public string $address = '';

    public string $address_ur = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var list<array{contact_person: string, phone_number: string}> */
    public array $phoneRows = [
        ['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => ''],
    ];

    /** @var list<array{bank_name: string, account_title: string, account_number: string}> */
    public array $bankRows = [
        ['bank_name' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''],
    ];

    /** @var array<int, string> */
    public array $bankSearches = [''];

    public ?int $openBankPickerIndex = null;

    public ?TemporaryUploadedFile $profilePhotoUpload = null;

    public ?TemporaryUploadedFile $cnicFrontUpload = null;

    public ?TemporaryUploadedFile $cnicBackUpload = null;

    public ?string $profilePhotoPreviewUrl = null;

    public ?string $cnicFrontPreviewUrl = null;

    public ?string $cnicBackPreviewUrl = null;

    public bool $saving = false;

    public bool $submitted = false;

    public ?string $submittedReference = null;

    public bool $isResubmit = false;

    public string $website = '';

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
        $this->phoneRows[] = ['contact_person' => '', 'contact_person_ur' => '', 'phone_number' => ''];
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
        $this->bankRows[] = ['bank_name' => '', 'account_title' => '', 'account_title_ur' => '', 'account_number' => ''];
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
        $this->openBankPickerIndex = null;
    }

    public function openBankPicker(int $index): void
    {
        if (array_key_exists($index, $this->bankRows)) {
            $this->openBankPickerIndex = $index;
        }
    }

    public function selectBank(int $index, string $bankName): void
    {
        if (
            ! array_key_exists($index, $this->bankRows)
            || ! in_array($bankName, PakistaniBanks::englishNames(), true)
        ) {
            return;
        }

        $this->bankRows[$index]['bank_name'] = $bankName;
        $this->bankSearches[$index] = '';
        $this->openBankPickerIndex = null;
    }

    public function clearBank(int $index): void
    {
        $this->bankRows[$index]['bank_name'] = '';
        $this->bankSearches[$index] = '';
        $this->openBankPickerIndex = $index;
    }

    public function submit(): void
    {
        $this->saving = true;
        $this->statusMessage = null;
        $storedPaths = [];

        try {
            if (filled($this->website)) {
                $this->submitted = true;

                return;
            }

            $this->email = mb_strtolower(trim($this->email));
            $ipHash = sha1((string) request()->ip());
            $rateLimitKey = 'staff-registration:'.sha1($ipHash.'|'.$this->email);
            $ipRateLimitKey = 'staff-registration-ip:'.$ipHash;

            if (
                RateLimiter::tooManyAttempts($rateLimitKey, 10)
                || RateLimiter::tooManyAttempts($ipRateLimitKey, 30)
            ) {
                throw ValidationException::withMessages([
                    'email' => [__('agricart.auth.register_rate_limited')],
                ]);
            }

            RateLimiter::hit($rateLimitKey, 60);
            RateLimiter::hit($ipRateLimitKey, 3600);

            $rejectedUser = User::query()
                ->where('email', $this->email)
                ->where('status', User::STATUS_REJECTED)
                ->first();

            $this->isResubmit = $rejectedUser !== null;

            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'name_ur' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($rejectedUser?->id),
                ],
                'address' => ['required', 'string', 'max:2000'],
                'address_ur' => ['required', 'string', 'max:2000'],
                'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
                'password_confirmation' => ['required', 'string'],
                'phoneRows.*.phone_number' => ['required', 'string', 'max:30'],
                'phoneRows.*.contact_person' => ['required', 'string', 'max:255'],
                'phoneRows.*.contact_person_ur' => ['required', 'string', 'max:255'],
                'bankRows.*.bank_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::in(PakistaniBanks::englishNames()),
                ],
                'bankRows.*.account_title' => ['required', 'string', 'max:255'],
                'bankRows.*.account_title_ur' => ['required', 'string', 'max:255'],
                'bankRows.*.account_number' => ['required', 'string', 'max:64'],
                'profilePhotoUpload' => ['required', 'image', 'max:5120'],
                'cnicFrontUpload' => ['required', 'image', 'max:5120'],
                'cnicBackUpload' => ['required', 'image', 'max:5120'],
            ]);

            $service = app(UserService::class);
            $storedPaths['profile_photo_path'] = $this->storeUpload($this->profilePhotoUpload, 'users/profile-photos');
            $storedPaths['cnic_front_path'] = $this->storeUpload($this->cnicFrontUpload, 'users/cnic', 'local');
            $storedPaths['cnic_back_path'] = $this->storeUpload($this->cnicBackUpload, 'users/cnic', 'local');

            $user = $service->registerPublic([
                'name' => $this->name,
                'name_ur' => $this->name_ur ?: null,
                'email' => $this->email,
                'address' => $this->address,
                'address_ur' => $this->address_ur ?: null,
                'password' => $this->password,
                'phone_numbers' => $service->normalizePhoneRows($this->phoneRows),
                'bank_accounts' => $service->normalizeBankRows($this->bankRows),
                ...$storedPaths,
            ]);

            $storedPaths = [];
            RateLimiter::clear($rateLimitKey);
            $this->submittedReference = $user->user_number;
            $this->submitted = true;
            $this->dispatch('staff-registration-result', success: true);
        } catch (ValidationException $exception) {
            $this->deleteStoredPaths($storedPaths);
            $message = collect($exception->errors())->flatten()->first();
            $this->statusMessage = $message ?: __('agricart.auth.register_failed');
            $this->statusType = 'danger';
            $this->dispatch('staff-registration-result', success: false);
        } catch (\Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            $this->statusMessage = FriendlyError::message($exception);
            $this->statusType = 'danger';
            $this->dispatch('staff-registration-result', success: false);
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
     * @return array<int, array<string, string>>
     */
    private function filteredBanksByRow(): array
    {
        $results = [];

        foreach ($this->bankSearches as $index => $search) {
            $matchingEntries = trim($search) === ''
                ? array_keys(PakistaniBanks::entries())
                : array_keys(PakistaniBanks::search($search, 'en'));
            $options = [];

            foreach ($matchingEntries as $bankKey) {
                $bankName = PakistaniBanks::english($bankKey);

                if ($bankName !== '') {
                    $options[$bankName] = PakistaniBanks::bilingualLabel($bankKey);
                }
            }

            asort($options);
            $results[$index] = $options;
        }

        return $results;
    }

    private function storeUpload(
        ?TemporaryUploadedFile $upload,
        string $directory,
        string $disk = 'public',
    ): ?string {
        if ($upload === null) {
            return null;
        }

        return app(OptimizedImageStorage::class)->store($upload, $directory, disk: $disk);
    }

    /**
     * @param  array<string, string|null>  $paths
     */
    private function deleteStoredPaths(array $paths): void
    {
        foreach ($paths as $field => $path) {
            if (filled($path)) {
                $disk = in_array($field, ['cnic_front_path', 'cnic_back_path'], true)
                    ? 'local'
                    : 'public';
                if ($disk === 'public') {
                    app(MediaLibraryService::class)->deleteFileIfUnused($path);
                } else {
                    Storage::disk($disk)->delete($path);
                }
            }
        }
    }
}
