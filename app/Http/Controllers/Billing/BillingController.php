<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\SubscribeOrganization;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $request->user()->currentOrganization;

        Log::debug('Billing: Viewing billing page', [
            'user_id' => $request->user()->id,
            'organization_id' => $organization?->id,
        ]);

        return Inertia::render('billing/index', [
            'plans' => Plan::allCached(),
            'currentPlan' => $organization?->currentPlan(),
            'isSubscribed' => $organization?->subscribed() ?? false,
            'isOnTrial' => $organization?->onTrial() ?? false,
            'trialEndsAt' => $organization?->trialEndsAt()?->toISOString(),
        ]);
    }

    public function checkout(Request $request, SubscribeOrganization $action): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'string'],
            'interval' => ['required', 'string', 'in:monthly,yearly'],
        ]);

        $organization = $request->user()->currentOrganization;

        try {
            $checkout = $action->handle($organization, $request->only('plan', 'interval'));

            return redirect($checkout->url);
        } catch (\Exception $e) {
            Log::error('Billing: Checkout request failed', [
                'user_id' => $request->user()->id,
                'organization_id' => $organization?->id,
                'plan' => $request->input('plan'),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function portal(Request $request): RedirectResponse
    {
        $organization = $request->user()->currentOrganization;

        Log::debug('Billing: Redirecting to billing portal', [
            'user_id' => $request->user()->id,
            'organization_id' => $organization?->id,
        ]);

        try {
            return $organization->redirectToBillingPortal(route('billing.index'));
        } catch (\Exception $e) {
            Log::error('Billing: Failed to redirect to billing portal', [
                'organization_id' => $organization?->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
