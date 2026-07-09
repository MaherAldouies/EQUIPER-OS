<?php

namespace App\Services\GoogleSearchConsole;

use App\Models\AnalyticsSignal;
use App\Models\Integration;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SearchConsoleService — read-only integration feeding organic search
 * performance into F9's dashboard.
 *
 * STUB — OAuth flow and the actual Search Console API call are Sprint 2
 * scope; this establishes the shape and the AnalyticsSignal write path
 * so DashboardAggregationService has real organic-search data once the
 * OAuth piece is completed.
 */
class SearchConsoleService
{
    public function __construct(
        private readonly string $organizationId,
        private readonly Client $httpClient = new Client(),
    ) {}

    public function pullDailyPerformance(\DateTimeInterface $date): void
    {
        try {
            // TODO (Sprint 2): implement OAuth2 token refresh + call to
            // https://searchconsole.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query
            // Placeholder values below until that integration is built.
            $clicks = 0;
            $impressions = 0;

            DB::transaction(function () use ($date, $clicks, $impressions) {
                AnalyticsSignal::query()->updateOrCreate(
                    [
                        'organization_id' => $this->organizationId,
                        'metric_key' => 'organic_clicks',
                        'source' => 'google_search_console',
                        'signal_date' => $date->format('Y-m-d'),
                    ],
                    ['value' => $clicks, 'unit' => 'count', 'confidence' => 'low'] // low until real OAuth data flows
                );

                AnalyticsSignal::query()->updateOrCreate(
                    [
                        'organization_id' => $this->organizationId,
                        'metric_key' => 'organic_impressions',
                        'source' => 'google_search_console',
                        'signal_date' => $date->format('Y-m-d'),
                    ],
                    ['value' => $impressions, 'unit' => 'count', 'confidence' => 'low']
                );
            });

            $this->integration()->markHealthy();
        } catch (Throwable $e) {
            Log::error('Google Search Console pull failed', ['error' => $e->getMessage()]);
            $this->integration()->markDegraded($e->getMessage());
            throw $e;
        }
    }

    private function integration(): Integration
    {
        return Integration::query()->firstOrCreate(
            ['organization_id' => $this->organizationId, 'provider' => 'google_search_console'],
            ['status' => 'configuring']
        );
    }
}
