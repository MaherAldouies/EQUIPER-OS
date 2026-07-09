<?php

namespace App\Console\Commands;

use App\Jobs\SyncSallaProductJob;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ReconcileSallaProducts — F3 acceptance criteria: "a scheduled
 * reconciliation job (every 30 minutes) as a fallback for missed
 * webhooks."
 *
 * TODO (Sprint 0 spike): the actual Salla Partner API endpoint path,
 * auth flow (OAuth2 client-credentials vs. API key), pagination
 * parameter names, and response envelope shape are all placeholders
 * below pending Salla's real documentation. What IS final: the pattern
 * of dispatching one SyncSallaProductJob per product (reusing the exact
 * same idempotent, retry-safe path webhooks use), rather than syncing
 * inline in this command — a reconciliation run for a large catalog
 * must not block the scheduler or risk a partial run on timeout.
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

        $page = 1;
        $dispatched = 0;

        do {
            try {
                // Placeholder request shape — confirm against Salla's
                // actual Partner API docs before relying on this.
                $response = Http::withToken(config('equiperos.salla.client_secret'))
                    ->baseUrl(config('equiperos.salla.api_base_url'))
                    ->get('/admin/v2/products', ['page' => $page, 'per_page' => 100]);

                if (! $response->successful()) {
                    Log::error('Salla reconciliation request failed', [
                        'status' => $response->status(),
                        'page' => $page,
                    ]);
                    $this->error("Salla API returned {$response->status()} on page {$page} — aborting run.");

                    return self::FAILURE;
                }

                $body = $response->json();
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
