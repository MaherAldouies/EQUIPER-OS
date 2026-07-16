<?php

namespace App\Services\Analytics;

use App\Models\Organization;
use App\Services\GoogleAnalytics\GoogleAnalyticsService;
use App\Services\GoogleMerchant\GoogleMerchantService;
use App\Services\GoogleSearchConsole\SearchConsoleService;
use Throwable;

/**
 * DashboardSyncRunner — runs every per-organization dashboard signal
 * refresh (F9). Shared by the `dashboard:refresh` scheduled command and
 * the dashboard's manual "Sync Now" button: Render's free tier runs no
 * scheduler process, so the manual button is the only way this ever
 * runs in production until the app moves off the free plan.
 */
class DashboardSyncRunner
{
    /**
     * @return array<int, string> Arabic-language error messages for any
     *                             source that failed; empty on full success.
     */
    public static function run(Organization $organization, \DateTimeInterface $date): array
    {
        $errors = [];

        $dashboard = new DashboardAggregationService($organization->id);
        $dashboard->refreshRevenueSignal($date);
        $dashboard->refreshContentPipelineSignal($date);

        try {
            (new SearchConsoleService($organization->id))->pullDailyPerformance($date);
        } catch (Throwable $e) {
            $errors[] = "Google Search Console: {$e->getMessage()}";
        }

        try {
            (new GoogleAnalyticsService($organization->id))->pullDailySummary($date);
        } catch (Throwable $e) {
            $errors[] = "Google Analytics: {$e->getMessage()}";
        }

        try {
            (new GoogleMerchantService($organization->id))->pullDailySummary($date);
        } catch (Throwable $e) {
            $errors[] = "Google Merchant Center: {$e->getMessage()}";
        }

        return $errors;
    }
}
