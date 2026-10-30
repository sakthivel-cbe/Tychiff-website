<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesSafeMailer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class SafePasswordResetNotification extends ResetPassword
{
    use UsesSafeMailer;

    public function toMail($notifiable): MailMessage
    {
        return $this->avoidLoggingBearerLinks($this->buildMailMessage($this->resetUrl($notifiable)));
    }
}
