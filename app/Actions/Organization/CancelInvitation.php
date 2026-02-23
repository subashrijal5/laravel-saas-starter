<?php

namespace App\Actions\Organization;

use App\Models\OrganizationInvitation;
use Illuminate\Support\Facades\Log;

class CancelInvitation
{
    public function handle(OrganizationInvitation $invitation): void
    {
        Log::debug('Organization: Cancelling invitation', [
            'invitation_id' => $invitation->id,
            'organization_id' => $invitation->organization_id,
            'email' => $invitation->email,
        ]);

        $invitation->delete();
    }
}
