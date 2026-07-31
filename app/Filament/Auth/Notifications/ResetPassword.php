<?php

namespace App\Filament\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Staff password-reset mail — sent synchronously (Hostinger has no queue worker).
 */
class ResetPassword extends BaseNotification
{
    public string $url = '';

    protected function resetUrl($notifiable): string
    {
        return $this->url !== '' ? $this->url : parent::resetUrl($notifiable);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('agricart.auth.reset_password_mail.subject'))
            ->line(__('agricart.auth.reset_password_mail.line'))
            ->action(__('agricart.auth.reset_password_mail.action'), $this->resetUrl($notifiable))
            ->line(__('agricart.auth.reset_password_mail.expire', [
                'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]))
            ->line(__('agricart.auth.reset_password_mail.ignore'));
    }
}
