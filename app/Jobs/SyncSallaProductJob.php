<?php

namespace App\Jobs;

use App\Services\Salla\SallaSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * SyncSallaProductJob — dispatched from SallaWebhookController so the
 * webhook HTTP response returns immediately (production-ready pattern:
 * webhook endpoints should ack fast and process asynchronously, since
 * Salla itself may retry/timeout slow webhook responses).
 */
class SyncSallaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(
        public readonly string $organizationId,
        public readonly array $sallaPayload,
    ) {}

    public function handle(): void
    {
        (new SallaSyncService($this->organizationId))->syncProduct($this->sallaPayload);
    }
}
