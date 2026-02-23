<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UsageApproachingLimitNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Organization $organization,
        public string $featureName,
        public int $currentUsage,
        public int $limit,
        public int $percentage,
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
        $featureLabel = str_replace('_', ' ', $this->featureName);

        return (new MailMessage)
            ->subject("Usage alert: {$this->percentage}% of {$featureLabel} limit reached")
            ->line("Your **{$this->organization->name}** organization has used **{$this->percentage}%** of its {$featureLabel} limit ({$this->currentUsage}/{$this->limit}).")
            ->action('Upgrade Plan', route('billing.index'))
            ->line('Consider upgrading your plan to avoid interruptions.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $featureLabel = str_replace('_', ' ', $this->featureName);

        return [
            'type' => 'usage_approaching_limit',
            'title' => "{$this->percentage}% of {$featureLabel} limit reached",
            'body' => "{$this->organization->name} has used {$this->currentUsage} of {$this->limit} {$featureLabel}.",
            'action_url' => route('billing.index'),
            'action_label' => 'Upgrade Plan',
            'organization_id' => $this->organization->id,
            'feature' => $this->featureName,
            'current_usage' => $this->currentUsage,
            'limit' => $this->limit,
            'percentage' => $this->percentage,
        ];
    }
}
