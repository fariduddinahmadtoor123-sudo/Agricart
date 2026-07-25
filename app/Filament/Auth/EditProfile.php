<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Services\Access\SessionInvalidationService;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    protected ?string $originalProfilePhotoPath = null;

    protected bool $passwordWasChanged = false;

    protected bool $syncSessionPasswordHash = false;

    public static function getLabel(): string
    {
        return __('agricart.admin_profile.title');
    }

    public function mount(): void
    {
        $this->originalProfilePhotoPath = $this->getUser()->getAttribute('profile_photo_path');

        parent::mount();
    }

    public function save(): void
    {
        parent::save();

        if (! $this->syncSessionPasswordHash || ! request()->hasSession()) {
            return;
        }

        $user = $this->getUser()->fresh();

        if ($user === null) {
            return;
        }

        request()->session()->put(
            'password_hash_'.Filament::getAuthGuard(),
            $user->getAuthPassword(),
        );

        $this->syncSessionPasswordHash = false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('agricart.admin_profile.personal_section'))
                    ->description(__('agricart.admin_profile.personal_help'))
                    ->columns(2)
                    ->schema([
                        FileUpload::make('profile_photo_path')
                            ->label(__('agricart.admin_profile.photo'))
                            ->disk('public')
                            ->directory('users/profile-photos')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        $this->getNameFormComponent()
                            ->label(__('agricart.admin_profile.name_en')),
                        TextInput::make('name_ur')
                            ->label(__('agricart.admin_profile.name_ur'))
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label(__('agricart.admin_profile.address_en'))
                            ->rows(3)
                            ->maxLength(2000),
                        Textarea::make('address_ur')
                            ->label(__('agricart.admin_profile.address_ur'))
                            ->rows(3)
                            ->maxLength(2000),
                    ]),
                Section::make(__('agricart.admin_profile.contact_section'))
                    ->description(__('agricart.admin_profile.contact_help'))
                    ->columns(2)
                    ->schema([
                        $this->getEmailFormComponent()
                            ->label(__('agricart.admin_profile.email')),
                        TextInput::make('primary_phone')
                            ->label(__('agricart.admin_profile.primary_phone'))
                            ->tel()
                            ->maxLength(30)
                            ->rule('regex:/^\+?[0-9][0-9\s().-]{6,29}$/'),
                    ]),
                Section::make(__('agricart.admin_profile.security_section'))
                    ->description(__('agricart.admin_profile.security_help'))
                    ->columns(2)
                    ->schema([
                        $this->getPasswordFormComponent()
                            ->label(__('agricart.admin_profile.new_password')),
                        $this->getPasswordConfirmationFormComponent()
                            ->label(__('agricart.admin_profile.confirm_password')),
                        $this->getCurrentPasswordFormComponent()
                            ->label(__('agricart.admin_profile.current_password'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);
        $data['primary_phone'] = $this->primaryPhoneFrom($data['phone_numbers'] ?? []);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(#[SensitiveParameter] array $data): array
    {
        $this->passwordWasChanged = array_key_exists('password', $data);
        $this->syncSessionPasswordHash = $this->passwordWasChanged;

        $primaryPhone = trim((string) ($data['primary_phone'] ?? ''));
        unset($data['primary_phone']);

        $data['phone_numbers'] = $this->withPrimaryPhone(
            $this->getUser()->getAttribute('phone_numbers') ?? [],
            $primaryPhone,
            trim((string) ($data['name'] ?? $this->getUser()->getAttribute('name'))),
            trim((string) ($data['name_ur'] ?? $this->getUser()->getAttribute('name_ur'))),
        );

        return parent::mutateFormDataBeforeSave($data);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('agricart.admin_profile.new_password'))
            ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.password.validation_attribute'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->dehydrated(fn (#[SensitiveParameter] $state): bool => filled($state))
            // Plain value — User `hashed` cast hashes once (do not Hash::make here).
            ->live(debounce: 500)
            ->same('passwordConfirmation');
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')
            ->label(__('agricart.admin_profile.current_password'))
            ->validationAttribute(__('filament-panels::auth/pages/edit-profile.form.current_password.validation_attribute'))
            ->belowContent(__('filament-panels::auth/pages/edit-profile.form.current_password.below_content'))
            ->password()
            ->autocomplete('current-password')
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->dehydrated(false)
            ->rule(function (): \Closure {
                return function (string $attribute, mixed $value, \Closure $fail): void {
                    $hash = (string) ($this->getUser()->fresh()?->getRawOriginal('password') ?? '');

                    if ($hash === '' || ! Hash::check((string) $value, $hash)) {
                        $fail(__('agricart.admin_profile.current_password_invalid'));
                    }
                };
            });
    }

    protected function afterSave(): void
    {
        $user = $this->getUser();
        $newProfilePhotoPath = $user->getAttribute('profile_photo_path');

        if (
            filled($this->originalProfilePhotoPath)
            && ($this->originalProfilePhotoPath !== $newProfilePhotoPath)
        ) {
            Storage::disk('public')->delete($this->originalProfilePhotoPath);
        }

        $this->originalProfilePhotoPath = $newProfilePhotoPath;

        if (! $this->passwordWasChanged) {
            return;
        }

        if ($user instanceof User) {
            app(SessionInvalidationService::class)->wipeOthersForUser($user);
        }

        $this->passwordWasChanged = false;
    }

    private function primaryPhoneFrom(mixed $phoneNumbers): string
    {
        if (! is_array($phoneNumbers)) {
            return '';
        }

        foreach ($phoneNumbers as $row) {
            if (is_array($row) && filled($row['phone_number'] ?? null)) {
                return (string) $row['phone_number'];
            }
        }

        return '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function withPrimaryPhone(mixed $phoneNumbers, string $phone, string $name, string $nameUr): array
    {
        $rows = is_array($phoneNumbers)
            ? array_values(array_filter($phoneNumbers, 'is_array'))
            : [];

        $primaryIndex = null;

        foreach ($rows as $index => $row) {
            if (filled($row['phone_number'] ?? null)) {
                $primaryIndex = $index;
                break;
            }
        }

        if ($phone === '') {
            if ($primaryIndex !== null) {
                unset($rows[$primaryIndex]);
            }

            return array_values($rows);
        }

        $primaryRow = [
            'contact_person' => $name,
            'contact_person_ur' => $nameUr,
            'phone_number' => $phone,
        ];

        if ($primaryIndex === null) {
            array_unshift($rows, $primaryRow);
        } else {
            $rows[$primaryIndex] = [...$rows[$primaryIndex], ...$primaryRow];
        }

        return array_values($rows);
    }
}
