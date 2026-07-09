<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Analytics\DashboardAggregationService;
use App\Services\GoogleSearchConsole\SearchConsoleService;
use Illuminate\Console\Command;

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

            $this->info("Refreshed dashboard signals for organization [{$organization->slug}].");
        });

        return self::SUCCESS;
    }
}
