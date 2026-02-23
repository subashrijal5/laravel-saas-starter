<?php

namespace App\Actions\Organization;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class InviteOrganizationMember
{
    /**
     * @param  array{email: string, role: string}  $data
     */
    public function handle(Organization $organization, array $data): OrganizationInvitation
    {
        Log::debug('Organization: Inviting member', [
            'organization_id' => $organization->id,
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        $existingMember = $organization->members()->where('email', $data['email'])->exists();

        if ($existingMember) {
            throw ValidationException::withMessages([
                'email' => ['This user is already a member of the organization.'],
            ]);
        }

        $existingInvitation = $organization->invitations()->where('email', $data['email'])->exists();

        if ($existingInvitation) {
            throw ValidationException::withMessages([
                'email' => ['An invitation has already been sent to this email.'],
            ]);
        }

        if (! array_key_exists($data['role'], config('saas.roles', []))) {
            throw ValidationException::withMessages([
                'role' => ['The selected role is invalid.'],
            ]);
        }

        $expiryDays = config('saas.invitations.expiry_days');

        $invitation = $organization->invitations()->create([
            'email' => $data['email'],
            'role' => $data['role'],
            'expires_at' => $expiryDays ? now()->addDays($expiryDays) : null,
        ]);

        try {
            $existingUser = User::query()->where('email', $data['email'])->first();

            if ($existingUser) {
                $existingUser->notify(new OrganizationInvitationNotification($invitation));
            } else {
                Notification::route('mail', $data['email'])
                    ->notify(new OrganizationInvitationNotification($invitation));
            }
        } catch (\Exception $e) {
            Log::error('Organization: Failed to send invitation notification', [
                'organization_id' => $organization->id,
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Organization: Invitation sent', [
            'organization_id' => $organization->id,
            'invitation_id' => $invitation->id,
            'email' => $data['email'],
        ]);

        return $invitation;
    }
}
