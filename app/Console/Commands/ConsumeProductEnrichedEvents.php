<?php

namespace App\Console\Commands;

use App\Jobs\GenerateProductContentJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * ConsumeProductEnrichedEvents
 *
 * Reference implementation of an Event Backbone SUBSCRIBER — reacts to
 * the ProductEnriched event by dispatching GenerateProductContentJob
 * (F6). This is the out-of-process path; App\Listeners\
 * DispatchContentGenerationJob is the in-process path for the same
 * job — GenerateProductContentJob's ShouldBeUnique lock (keyed by
 * productId) makes it safe for both to fire for the same enrichment
 * without double-generating content.
 *
 * This is the pattern every future module subscriber follows: read
 * from the Redis stream, never call another module's code directly —
 * dispatch a Job (or, for cross-cutting concerns, a Listener) instead.
 *
 * Usage: php artisan events:consume-product-enriched --loop
 */
class ConsumeProductEnrichedEvents extends Command
{
    protected $signature = 'events:consume-product-enriched {--loop}';

    protected $description = 'Subscribe to ProductEnriched events and dispatch AI Content & SEO generation (F6).';

    private const CONSUMER_GROUP = 'ai-content-seo-generation';

    public function handle(): int
    {
        $stream = config('equiperos.event_stream');
        $consumerName = 'consumer-'.gethostname();

        $this->ensureConsumerGroupExists($stream);

        do {
            $entries = Redis::xreadgroup(
                self::CONSUMER_GROUP,
                $consumerName,
                [$stream => '>'],
                count: 10,
                block: 2000,
            );

            foreach ($entries[$stream] ?? [] as $entryId => $fields) {
                if (($fields['event_type'] ?? null) !== 'ProductEnriched') {
                    Redis::xack($stream, self::CONSUMER_GROUP, $entryId);

                    continue;
                }

                try {
                    $payload = json_decode($fields['payload'], true);
                    GenerateProductContentJob::dispatch($payload['product_id']);
                    $this->info("Dispatched GenerateProductContentJob for Product {$payload['product_id']}.");
                } catch (Throwable $e) {
                    $this->error("Failed to process event {$entryId}: {$e->getMessage()}");
                    // Deliberately still ack — a dead-letter queue / retry policy
                    // is explicit future scope, not silently retried here (loop-detection guardrail).
                }

                Redis::xack($stream, self::CONSUMER_GROUP, $entryId);
            }
        } while ($this->option('loop'));

        return self::SUCCESS;
    }

    private function ensureConsumerGroupExists(string $stream): void
    {
        try {
            Redis::xgroup('CREATE', $stream, self::CONSUMER_GROUP, '0', true);
        } catch (Throwable) {
            // Group already exists — fine.
        }
    }
}
