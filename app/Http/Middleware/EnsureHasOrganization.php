<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->current_organization_id) {
            Log::debug('Middleware: Redirecting user without organization', [
                'user_id' => $user?->id,
            ]);

            return redirect()->route('organizations.create');
        }

        return $next($request);
    }
}
