<?php

namespace App\Notifications\Store;

use Illuminate\Auth\Notifications\ResetPassword as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Customer (online store) password-reset mail — separate from staff/admin resets.
 */
class CustomerResetPassword extends BaseNotification
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('agricart.customer.auth.reset_password_mail.subject'))
            ->line(__('agricart.customer.auth.reset_password_mail.line'))
            ->action(
                __('agricart.customer.auth.reset_password_mail.action'),
                $this->resetUrl($notifiable),
            )
            ->line(__('agricart.customer.auth.reset_password_mail.expire', [
                'count' => config('auth.passwords.customers.expire'),
            ]))
            ->line(__('agricart.customer.auth.reset_password_mail.ignore'));
    }
}
