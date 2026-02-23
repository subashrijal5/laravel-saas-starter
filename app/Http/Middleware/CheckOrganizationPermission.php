<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user?->current_organization_id) {
            abort(403, 'No active organization.');
        }

        foreach ($permissions as $permission) {
            if (! $user->hasOrganizationPermission($permission)) {
                Log::warning('Middleware: Permission denied', [
                    'user_id' => $user->id,
                    'organization_id' => $user->current_organization_id,
                    'permission' => $permission,
                ]);

                abort(403, 'You do not have the required permission.');
            }
        }

        return $next($request);
    }
}
