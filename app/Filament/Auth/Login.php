<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Services\Access\BootstrapSuperAdminService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if (filled($email) && filled($password)) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user instanceof User) {
                // Use the canonical DB email so Auth::attempt matches on case-sensitive collations.
                $this->form->fill([
                    ...$data,
                    'email' => (string) $user->email,
                ]);

                $hash = (string) $user->getRawOriginal('password');

                if ($hash !== '' && Hash::check($password, $hash)) {
                    $user = app(BootstrapSuperAdminService::class)->assignBootstrapOwnerIfEligible($user);

                    $message = $user->loginBlockedMessage();

                    if ($message !== null) {
                        throw ValidationException::withMessages([
                            'data.email' => $message,
                        ]);
                    }

                    if (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
                        throw ValidationException::withMessages([
                            'data.email' => __('agricart.auth.inactive'),
                        ]);
                    }
                }
            }
        }

        return parent::authenticate();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(array_values(array_filter([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
                $this->getForgotPasswordFormComponent(),
            ])));
    }

    /**
     * Keep the password field clean; forgot-password sits below Remember me
     * where staff expect it (Filament's label-row hint is easy to miss).
     */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }

    protected function getForgotPasswordFormComponent(): ?Component
    {
        if (! filament()->hasPasswordReset()) {
            return null;
        }

        return Html::make(new HtmlString(
            Blade::render(
                '<p class="agricart-login-forgot"><a href="{{ $url }}" class="agricart-login-forgot__link" tabindex="-1">{{ $label }}</a></p>',
                [
                    'url' => filament()->getRequestPasswordResetUrl(),
                    'label' => __('filament-panels::auth/pages/login.actions.request_password_reset.label'),
                ],
            )
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'email' => trim((string) ($data['email'] ?? '')),
            'password' => (string) ($data['password'] ?? ''),
        ];
    }
}
