<?php

namespace App\Console\Commands;

use App\Jobs\SyncSallaProductJob;
use App\Models\Organization;
use App\Services\Salla\SallaApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ReconcileSallaProducts — F3 acceptance criteria: "a scheduled
 * reconciliation job (every 30 minutes) as a fallback for missed
 * webhooks." Confirmed against docs.salla.dev: GET /products against
 * https://api.salla.dev/admin/v2, pagination shape
 * {count,total,perPage,currentPage,totalPages,links}. Dispatches one
 * SyncSallaProductJob per product (same idempotent, retry-safe path
 * webhooks use) rather than syncing inline — a reconciliation run for a
 * large catalog must not block the scheduler or risk a partial run on
 * timeout.
 */
class ReconcileSallaProducts extends Command
{
    protected $signature = 'salla:reconcile';

    protected $description = 'Reconciliation fallback: re-pull the full product catalog from Salla (PRD F3).';

    public function handle(): int
    {
        $organization = Organization::query()->first();

        if (! $organization) {
            $this->warn('No Organization found — skipping reconciliation.');

            return self::SUCCESS;
        }

        $client = new SallaApiClient($organization->id);

        $page = 1;
        $dispatched = 0;

        do {
            try {
                $body = $client->products($page);
                $products = $body['data'] ?? [];

                foreach ($products as $sallaPayload) {
                    SyncSallaProductJob::dispatch($organization->id, $sallaPayload);
                    $dispatched++;
                }

                $hasMorePages = ($body['pagination']['currentPage'] ?? $page) < ($body['pagination']['totalPages'] ?? $page);
                $page++;
            } catch (Throwable $e) {
                Log::error('Salla reconciliation failed', ['error' => $e->getMessage(), 'page' => $page]);
                $this->error("Reconciliation failed on page {$page}: {$e->getMessage()}");

                return self::FAILURE;
            }
        } while ($hasMorePages);

        $this->info("Dispatched {$dispatched} SyncSallaProductJob(s) across ".($page - 1).' page(s).');

        return self::SUCCESS;
    }
}
