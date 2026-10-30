<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesSafeMailer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class SafeVerifyEmailNotification extends VerifyEmail
{
    use UsesSafeMailer;

    public function toMail($notifiable): MailMessage
    {
        return $this->avoidLoggingBearerLinks($this->buildMailMessage($this->verificationUrl($notifiable)));
    }
}
