<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesSafeMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffInvitationNotification extends Notification
{
    use Queueable;
    use UsesSafeMailer;

    public function __construct(public readonly string $url, public readonly array $roleNames) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('TYCHIFF staff invitation')
            ->greeting('You have been invited to TYCHIFF.')
            ->line('The invitation includes these role(s): '.implode(', ', $this->roleNames).'.')
            ->action('Accept invitation', $this->url)
            ->line('This invitation expires in 7 days. If you were not expecting it, you can ignore this email.');

        return $this->avoidLoggingBearerLinks($message);
    }
}
