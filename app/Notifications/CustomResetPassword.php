<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Reset Your Password — ' . config('app.name', 'TheCashFox'))
            ->view('emails.reset-password', [
                'url'   => $url,
                'name'  => $notifiable->name,
                'email' => $notifiable->email,
            ]);
    }
}
