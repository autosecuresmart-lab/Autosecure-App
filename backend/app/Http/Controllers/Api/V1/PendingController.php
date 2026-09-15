<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\PendingIntegrations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placeholder for API areas that cannot be built yet because the external
 * integration is not documented.
 *
 * The blocker list itself lives in one place — App\Support\PendingIntegrations —
 * because the device provider drivers raise the same information. Keeping a copy
 * here would let the API answer and the driver error drift apart.
 *
 * See docs/phase-1/DEVICE-INTEGRATION.md for the full discovery write-up.
 */
class PendingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $module = (string) ($request->route('module') ?? 'unknown');

        return response()->json(PendingIntegrations::payload($module), 501);
    }
}
