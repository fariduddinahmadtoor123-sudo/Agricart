<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Services\Access\BootstrapSuperAdminService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
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
