<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use App\Models\User;
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
        $channels = ['mail'];

        if ($notifiable instanceof User) {
            $channels[] = 'database';
        }

        return $channels;
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $organizationName = $this->invitation->organization->name;

        return [
            'type' => 'invitation_received',
            'title' => "You've been invited to join {$organizationName}",
            'body' => "You have been invited as {$this->invitation->role}.",
            'action_url' => route('invitations.accept', $this->invitation),
            'action_label' => 'Accept Invitation',
            'organization_id' => $this->invitation->organization_id,
        ];
    }
}
