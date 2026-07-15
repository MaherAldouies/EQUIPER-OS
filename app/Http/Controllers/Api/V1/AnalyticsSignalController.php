<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnalyticsSignalResource;
use App\Models\AnalyticsSignal;
use Illuminate\Http\Request;

class AnalyticsSignalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('dashboard.view_revenue'), 403);

        $signals = AnalyticsSignal::query()
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->filled('metric_key'), fn ($q) => $q->where('metric_key', $request->string('metric_key')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('signal_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('signal_date', '<=', $request->date('to')))
            ->orderByDesc('signal_date')
            ->paginate(100);

        return AnalyticsSignalResource::collection($signals);
    }
}
