<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('pricing', [
            'plans' => Plan::allCached(),
        ]);
    }
}
