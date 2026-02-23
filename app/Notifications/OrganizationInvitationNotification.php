<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public OrganizationInvitation $invitation,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $organizationName = $this->invitation->organization->name;
        $acceptUrl = route('invitations.accept', $this->invitation);

        $mail = (new MailMessage)
            ->subject("You've been invited to join {$organizationName}")
            ->line("You have been invited to join the **{$organizationName}** organization.")
            ->action('Accept Invitation', $acceptUrl);

        if ($this->invitation->expires_at) {
            $mail->line('This invitation expires on '.$this->invitation->expires_at->toFormattedDateString().'.');
        }

        return $mail->line('If you did not expect to receive this invitation, you may discard this email.');
    }
}
