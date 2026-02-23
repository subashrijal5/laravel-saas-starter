<?php

use App\Http\Middleware\CheckOrganizationPermission;
use App\Http\Middleware\CheckPlanLimit;
use App\Http\Middleware\EnsureHasOrganization;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\Subscribed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'org' => EnsureHasOrganization::class,
            'org.permission' => CheckOrganizationPermission::class,
            'subscribed' => Subscribed::class,
            'plan.limit' => CheckPlanLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (InvalidRequestException $e) {
            $organization = auth()->user()?->currentOrganization;

            Log::error('Stripe: Invalid request', [
                'organization_id' => $organization?->id,
                'stripe_error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
            ]);

            return false;
        });

        $exceptions->render(function (InvalidRequestException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A billing error occurred. Please try again or contact support.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->back()->withErrors([
                'billing' => 'A billing error occurred. Please try again or contact support.',
            ]);
        });
    })->create();
