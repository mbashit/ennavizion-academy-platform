<?php

namespace App\Notifications;

use App\Models\TrialApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the parent, when they submit a trial application in the portal. */
class TrialApplicationReceived extends Notification
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

        return (new MailMessage)
            ->subject("Trial application received — {$a->reference}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We've received your trial application for {$a->player->first_name} ({$a->program_name}).")
            ->line("Your reference is {$a->reference}.")
            ->line('The academy reviews every application and will update its status in your parent portal. We\'ll email you when anything changes.')
            ->action('Open the parent portal', url('/portal'));
    }
}
