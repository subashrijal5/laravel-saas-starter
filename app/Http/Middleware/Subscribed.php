<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Subscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->user()?->currentOrganization;

        if (! $organization?->subscribed() && ! $organization?->onTrial()) {
            Log::debug('Middleware: Redirecting unsubscribed org to billing', [
                'user_id' => $request->user()?->id,
                'organization_id' => $organization?->id,
            ]);

            return redirect()->route('billing.index');
        }

        return $next($request);
    }
}
