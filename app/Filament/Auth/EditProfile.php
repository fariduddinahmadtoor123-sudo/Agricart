<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Facades\Storage;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    protected ?string $originalProfilePhotoPath = null;

    protected bool $passwordWasChanged = false;

    public static function getLabel(): string
    {
        return __('agricart.admin_profile.title');
    }

    public function mount(): void
    {
        $this->originalProfilePhotoPath = $this->getUser()->getAttribute('profile_photo_path');

        parent::mount();
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

        $user->forceFill(['remember_token' => null])->saveQuietly();

        if (
            (config('session.driver') === 'database')
            && DatabaseSchema::hasTable((string) config('session.table', 'sessions'))
        ) {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', session()->getId())
                ->delete();
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
