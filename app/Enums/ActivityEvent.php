<?php

namespace App\Enums;

enum ActivityEvent: string
{
    case Login = 'login';
    case Logout = 'logout';
    case FailedLogin = 'failed_login';
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Converted = 'converted';

    public function label(): string
    {
        return __('agricart.activity.events.'.$this->value);
    }

    public function isAuth(): bool
    {
        return in_array($this, [self::Login, self::Logout, self::FailedLogin], true);
    }
}
