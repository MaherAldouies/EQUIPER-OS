<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnalyticsSignalResource;
use App\Models\AnalyticsSignal;
use App\Models\Integration;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $organizationId = $request->user()->organization_id;
        $today = now()->format('Y-m-d');
        $canViewRevenue = $request->user()->hasPermission('dashboard.view_revenue');

        $signal = fn (string $key, string $source = 'salla') => AnalyticsSignal::query()
            ->where('organization_id', $organizationId)
            ->where('metric_key', $key)
            ->where('source', $source)
            ->where('signal_date', $today)
            ->first();

        // Ontology rule: never show a misleading zero — null means
        // "insufficient data" (matches the web dashboard's behavior).
        $asResource = fn (?AnalyticsSignal $s) => $s ? new AnalyticsSignalResource($s) : null;

        return response()->json([
            'data' => [
                'revenue' => $canViewRevenue ? $asResource($signal('daily_revenue')) : null,
                'order_count' => $canViewRevenue ? $asResource($signal('daily_order_count')) : null,
                'content_pipeline_pending' => $asResource($signal('content_pipeline_pending_count', 'internal')),
                'organic_clicks' => $asResource($signal('organic_clicks', 'google_search_console')),
                'integrations' => Integration::query()
                    ->where('organization_id', $organizationId)
                    ->get(['provider', 'status', 'last_successful_sync_at']),
            ],
        ]);
    }
}
