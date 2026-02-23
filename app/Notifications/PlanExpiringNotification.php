<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlanExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Organization $organization,
        public int $daysRemaining,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->organization->currentPlan()?->name ?? 'your plan';
        $label = $this->daysRemaining === 1 ? '1 day' : "{$this->daysRemaining} days";

        return (new MailMessage)
            ->subject("{$planName} expires in {$label}")
            ->line("Your **{$planName}** plan for **{$this->organization->name}** expires in {$label}.")
            ->action('Manage Billing', route('billing.index'))
            ->line('Renew or upgrade to keep your features.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $planName = $this->organization->currentPlan()?->name ?? 'your plan';
        $label = $this->daysRemaining === 1 ? '1 day' : "{$this->daysRemaining} days";

        return [
            'type' => 'plan_expiring',
            'title' => "{$planName} expires in {$label}",
            'body' => "Your {$planName} plan for {$this->organization->name} is expiring soon.",
            'action_url' => route('billing.index'),
            'action_label' => 'Manage Billing',
            'organization_id' => $this->organization->id,
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
