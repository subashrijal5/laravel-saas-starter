<?php

namespace App\Actions\Organization;

use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ResendInvitation
{
    public function handle(OrganizationInvitation $invitation): void
    {
        Log::debug('Organization: Resending invitation', [
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
        ]);

        if (! $invitation->isExpired() && $invitation->created_at->diffInMinutes(now()) < 1) {
            Log::debug('Organization: Resend cooldown active', [
                'invitation_id' => $invitation->id,
            ]);

            throw ValidationException::withMessages([
                'invitation' => ['Please wait before resending this invitation.'],
            ]);
        }

        $expiryDays = config('saas.invitations.expiry_days');
        $invitation->update([
            'expires_at' => $expiryDays ? now()->addDays($expiryDays) : null,
        ]);

        try {
            $existingUser = User::query()->where('email', $invitation->email)->first();

            if ($existingUser) {
                $existingUser->notify(new OrganizationInvitationNotification($invitation));
            } else {
                Notification::route('mail', $invitation->email)
                    ->notify(new OrganizationInvitationNotification($invitation));
            }
        } catch (\Exception $e) {
            Log::error('Organization: Failed to resend invitation notification', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
