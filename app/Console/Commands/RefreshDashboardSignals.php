<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Analytics\DashboardSyncRunner;
use Illuminate\Console\Command;

/**
 * RefreshDashboardSignals — F9 acceptance criteria: "Data refreshes at
 * least every 30 minutes." Scheduled in routes/console.php. Render's
 * free tier has no scheduler process, so in production this only
 * actually runs via the dashboard's manual "Sync Now" button
 * (DashboardController::syncNow) — see DashboardSyncRunner.
 */
class RefreshDashboardSignals extends Command
{
    protected $signature = 'dashboard:refresh';

    protected $description = 'Recompute AnalyticsSignal rows feeding the Unified Reporting Dashboard (F9).';

    public function handle(): int
    {
        $today = now();

        Organization::query()->each(function (Organization $organization) use ($today) {
            $errors = DashboardSyncRunner::run($organization, $today);

            foreach ($errors as $error) {
                $this->error("[{$organization->slug}] {$error}");
            }

            $this->info("Refreshed dashboard signals for organization [{$organization->slug}].");
        });

        return self::SUCCESS;
    }
}
