<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsSignal;
use App\Models\Content;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * DashboardAggregationService — F9 (Unified Reporting Dashboard).
 *
 * Produces AnalyticsSignal rows from raw data across domains. Per the
 * Business Ontology's Analytics Signal business rule: "insufficient
 * data" is represented explicitly (confidence = 'low' + a documented
 * minimum sample size) rather than showing a misleading zero.
 *
 * Intended to run on a schedule (every 30 minutes, per PRD F9
 * acceptance criteria) — wire into routes/console.php once this is
 * built out further (Sprint 2/7 scope).
 */
class DashboardAggregationService
{
    public function __construct(
        private readonly string $organizationId,
    ) {}

    public function refreshRevenueSignal(\DateTimeInterface $date): AnalyticsSignal
    {
        $revenue = Order::query()
            ->where('organization_id', $this->organizationId)
            ->whereDate('placed_at', $date)
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->sum('total_amount');

        $orderCount = Order::query()
            ->where('organization_id', $this->organizationId)
            ->whereDate('placed_at', $date)
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->count();

        $this->upsertSignal('daily_order_count', $orderCount, 'count', 'salla', $date);

        return $this->upsertSignal('daily_revenue', $revenue, 'SAR', 'salla', $date);
    }

    public function refreshContentPipelineSignal(\DateTimeInterface $date): AnalyticsSignal
    {
        $pendingCount = Content::query()
            ->where('organization_id', $this->organizationId)
            ->where('status', 'drafted')
            ->count();

        return $this->upsertSignal('content_pipeline_pending_count', $pendingCount, 'count', 'internal', $date);
    }

    private function upsertSignal(
        string $metricKey,
        float $value,
        string $unit,
        string $source,
        \DateTimeInterface $date,
        array $dimensions = [],
    ): AnalyticsSignal {
        // Ontology rule: insufficient data must be flagged, never shown
        // as a misleading zero. Here, a zero from a genuinely empty but
        // valid query (e.g. zero orders today) is still "normal"
        // confidence — it IS the true value. "Low confidence" is
        // reserved for cases with too little historical data to trust
        // a trend, which a richer version of this service would compute
        // by checking sample size across a rolling window (v1.1 scope).
        return DB::transaction(function () use ($metricKey, $value, $unit, $source, $date, $dimensions) {
            $signal = AnalyticsSignal::query()->updateOrCreate(
                [
                    'organization_id' => $this->organizationId,
                    'metric_key' => $metricKey,
                    'source' => $source,
                    'signal_date' => $date->format('Y-m-d'),
                ],
                [
                    'value' => $value,
                    'unit' => $unit,
                    'confidence' => 'normal',
                    'dimensions' => $dimensions,
                ]
            );

            $signal->recordEvent(eventType: 'SignalDetected', payload: [
                'metric_key' => $metricKey,
                'value' => $value,
            ]);

            return $signal;
        });
    }
}
