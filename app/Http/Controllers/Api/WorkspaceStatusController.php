<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceStatusController extends Controller
{
    /**
     * Current user's workspace (brand, portal URL, Omada provisioning state).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $workspace = $request->user()?->workspace;

        if (! $workspace) {
            return response()->json(['message' => 'No workspace'], 404);
        }

        return response()->json([
            'brand_name' => $workspace->brand_name,
            'public_slug' => $workspace->public_slug,
            'provisioning_status' => $workspace->provisioning_status,
            'provisioning_error' => $workspace->provisioning_error,
            'provisioning_summary' => $workspace->provisioningSummary(),
            'provisioning_error_summary' => $workspace->provisioningErrorSummary(),
            'provisioning_lifecycle' => $workspace->provisioningLifecycleSummary(),
            'portal_url' => $workspace->portalUrl(),
            'omada_site_configured' => $workspace->isOmadaReady(),
            'devices_last_synced_at' => $workspace->devices_last_synced_at?->toIso8601String(),
        ]);
    }
}
