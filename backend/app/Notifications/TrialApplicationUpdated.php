<?php

namespace App\Notifications;

use App\Models\TrialApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the parent, when staff change the status, trial details or message on their application. */
class TrialApplicationUpdated extends Notification
{
    public function __construct(public TrialApplication $application)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->application;
        $mail = (new MailMessage)
            ->subject("Update on trial application {$a->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$a->player->first_name}'s application ({$a->program_name}) is now: **{$a->statusLabel()}**.");

        if ($a->status === 'trial_scheduled' && $a->trial_at) {
            $mail->line('Trial: '.$a->trial_at->format('l j F Y, g:ia').($a->trial_location ? " at {$a->trial_location}" : '').'.');
        }
        if (filled($a->message_to_parent)) {
            $mail->line('Message from the academy: '.$a->message_to_parent);
        }

        return $mail->action('View it in the parent portal', url('/portal'));
    }
}
