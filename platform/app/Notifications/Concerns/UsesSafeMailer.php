<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait UsesSafeMailer
{
    protected function avoidLoggingBearerLinks(MailMessage $message): MailMessage
    {
        if (config('mail.default') === 'log') {
            return $message->mailer('array');
        }

        return $message;
    }
}
