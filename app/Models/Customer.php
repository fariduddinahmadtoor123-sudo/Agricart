<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'customer_number',
    'first_name',
    'last_name',
    'email',
    'phone',
    'password',
    'address_line1',
    'address_line2',
    'city',
    'state',
    'postal_code',
    'country',
    'accepts_marketing',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function displayLabel(): string
    {
        $name = $this->fullName();

        return $name !== '' ? $name : (string) $this->email;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'accepts_marketing' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
