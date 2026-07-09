<?php

namespace App\Console\Commands;

use App\Models\DomainEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * RelayOutboxEvents
 *
 * The "Event Relay Worker" from the Infrastructure Architecture diagram.
 * Polls domain_events for rows not yet published (published_at IS NULL),
 * pushes each to the Redis Stream configured as REDIS_EVENT_STREAM, and
 * marks it published.
 *
 * Intended to run continuously (e.g. via `php artisan schedule:work` for
 * dev, or a dedicated long-running worker process / supervisor in
 * production — NOT as a cron job, since the acceptance criteria in the
 * PRD (F1) requires delivery within 5 seconds under normal load).
 *
 * Usage: php artisan events:relay --loop
 */
class RelayOutboxEvents extends Command
{
    protected $signature = 'events:relay {--loop : Run continuously with a short sleep between polls} {--batch=100}';

    protected $description = 'Relay unpublished domain events from the outbox to the Redis event stream.';

    public function handle(): int
    {
        $stream = config('equiperos.event_stream', 'equiper:events');
        $batchSize = (int) $this->option('batch');

        do {
            $relayed = $this->relayBatch($stream, $batchSize);

            if ($relayed > 0) {
                $this->info("Relayed {$relayed} event(s) to stream [{$stream}].");
            }

            if ($this->option('loop')) {
                usleep(500_000); // 0.5s between polls
            }
        } while ($this->option('loop'));

        return self::SUCCESS;
    }

    private function relayBatch(string $stream, int $batchSize): int
    {
        $events = DomainEvent::query()
            ->whereNull('published_at')
            ->orderBy('occurred_at')
            ->limit($batchSize)
            ->get();

        foreach ($events as $event) {
            Redis::xadd($stream, '*', [
                'id' => $event->id,
                'organization_id' => $event->organization_id,
                'event_type' => $event->event_type,
                'aggregate_type' => $event->aggregate_type,
                'aggregate_id' => $event->aggregate_id,
                'actor_type' => $event->actor_type,
                'actor_id' => $event->actor_id,
                'payload' => json_encode($event->payload),
                'caused_by_event_id' => $event->caused_by_event_id,
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ]);

            $event->forceFill(['published_at' => now()])->save();
        }

        return $events->count();
    }
}
