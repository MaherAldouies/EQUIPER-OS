<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Analytics\DashboardAggregationService;
use App\Services\GoogleAnalytics\GoogleAnalyticsService;
use App\Services\GoogleMerchant\GoogleMerchantService;
use App\Services\GoogleSearchConsole\SearchConsoleService;
use Illuminate\Console\Command;
use Throwable;

/**
 * RefreshDashboardSignals — F9 acceptance criteria: "Data refreshes at
 * least every 30 minutes." Scheduled in routes/console.php.
 */
class RefreshDashboardSignals extends Command
{
    protected $signature = 'dashboard:refresh';

    protected $description = 'Recompute AnalyticsSignal rows feeding the Unified Reporting Dashboard (F9).';

    public function handle(): int
    {
        $today = now();

        Organization::query()->each(function (Organization $organization) use ($today) {
            $dashboard = new DashboardAggregationService($organization->id);
            $dashboard->refreshRevenueSignal($today);
            $dashboard->refreshContentPipelineSignal($today);

            (new SearchConsoleService($organization->id))->pullDailyPerformance($today);

            try {
                (new GoogleAnalyticsService($organization->id))->pullDailySummary($today);
            } catch (Throwable $e) {
                $this->error("Google Analytics pull failed for [{$organization->slug}]: {$e->getMessage()}");
            }

            try {
                (new GoogleMerchantService($organization->id))->pullDailySummary($today);
            } catch (Throwable $e) {
                $this->error("Google Merchant Center pull failed for [{$organization->slug}]: {$e->getMessage()}");
            }

            $this->info("Refreshed dashboard signals for organization [{$organization->slug}].");
        });

        return self::SUCCESS;
    }
}
