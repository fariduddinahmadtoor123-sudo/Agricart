<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Services\Access\BootstrapSuperAdminService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (filled($email) && filled($password)) {
            $user = User::query()->where('email', $email)->first();

            if ($user instanceof User && Hash::check($password, $user->password)) {
                $user = app(BootstrapSuperAdminService::class)->assignBootstrapOwnerIfEligible($user);

                $message = $user->loginBlockedMessage();

                if ($message !== null) {
                    throw ValidationException::withMessages([
                        'data.email' => $message,
                    ]);
                }
            }
        }

        return parent::authenticate();
    }
}
