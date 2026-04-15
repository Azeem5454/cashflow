<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email — ' . config('app.name', 'TheCashFox'))
            ->view('emails.verify-email', [
                'url'   => $verificationUrl,
                'name'  => $notifiable->name,
                'email' => $notifiable->email,
            ]);
    }
}
