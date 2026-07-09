<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSignal;
use App\Models\Integration;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');
        $today = now()->format('Y-m-d');

        // Ontology rule: never show a misleading zero — surface
        // "insufficient data" explicitly when a signal doesn't exist yet.
        $signal = fn (string $key, string $source = 'salla') => AnalyticsSignal::query()
            ->where('organization_id', $organization->id)
            ->where('metric_key', $key)
            ->where('source', $source)
            ->where('signal_date', $today)
            ->first();

        $canViewRevenue = $request->user()->hasPermission('dashboard.view_revenue');

        return view('dashboard.index', [
            'revenueSignal' => $canViewRevenue ? $signal('daily_revenue') : null,
            'orderCountSignal' => $canViewRevenue ? $signal('daily_order_count') : null,
            'contentPipelineSignal' => $signal('content_pipeline_pending_count', 'internal'),
            'organicClicksSignal' => $signal('organic_clicks', 'google_search_console'),
            'canViewRevenue' => $canViewRevenue,
            'integrations' => Integration::query()->where('organization_id', $organization->id)->get(),
        ]);
    }
}
