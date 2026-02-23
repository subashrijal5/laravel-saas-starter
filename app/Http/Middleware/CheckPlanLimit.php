<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimit
{
    public function handle(Request $request, Closure $next, string $feature, ?string $countAttribute = null): Response
    {
        $organization = $request->user()?->currentOrganization;

        if (! $organization) {
            return $next($request);
        }

        $currentCount = $countAttribute
            ? (int) $request->input($countAttribute, 0)
            : 0;

        Log::debug('Middleware: Checking plan limit', [
            'organization_id' => $organization->id,
            'feature' => $feature,
            'current_count' => $currentCount,
        ]);

        if ($organization->exceedsLimit($feature, $currentCount)) {
            Log::warning('Middleware: Plan limit exceeded', [
                'organization_id' => $organization->id,
                'feature' => $feature,
                'current_count' => $currentCount,
                'limit' => $organization->planLimit($feature),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You have reached the limit for this feature. Please upgrade your plan.',
                    'feature' => $feature,
                ], Response::HTTP_PAYMENT_REQUIRED);
            }

            return back()->withErrors([
                'plan_limit' => 'You have reached the limit for this feature. Please upgrade your plan.',
            ]);
        }

        return $next($request);
    }
}
