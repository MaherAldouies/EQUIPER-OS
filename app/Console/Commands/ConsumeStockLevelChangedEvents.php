<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * ConsumeStockLevelChangedEvents — F14 (Simple Automation Rule, P2).
 *
 * Deliberately rules-based, not AI — per the architectural distinction
 * in the AI Operating Core document (Section 5) between deterministic
 * automation and AI-driven reasoning. One hardcoded rule type only,
 * per the PRD's explicit scope limitation for v1.0.
 */
class ConsumeStockLevelChangedEvents extends Command
{
    protected $signature = 'events:consume-stock-alerts {--loop}';

    protected $description = 'Subscribe to StockLevelChanged and raise a Task when a product goes low/out of stock (F14).';

    private const CONSUMER_GROUP = 'low-stock-automation';

    public function handle(): int
    {
        $stream = config('equiperos.event_stream');
        $consumerName = 'consumer-'.gethostname();

        try {
            Redis::xgroup('CREATE', $stream, self::CONSUMER_GROUP, '0', true);
        } catch (Throwable) {
            // group already exists
        }

        do {
            $entries = Redis::xreadgroup(
                self::CONSUMER_GROUP,
                $consumerName,
                [$stream => '>'],
                count: 10,
                block: 2000,
            );

            foreach ($entries[$stream] ?? [] as $entryId => $fields) {
                if (($fields['event_type'] ?? null) === 'StockLevelChanged') {
                    $this->maybeRaiseAlert($fields);
                }

                Redis::xack($stream, self::CONSUMER_GROUP, $entryId);
            }
        } while ($this->option('loop'));

        return self::SUCCESS;
    }

    private function maybeRaiseAlert(array $fields): void
    {
        $payload = json_decode($fields['payload'], true);
        $product = Product::query()->find($payload['salla_product_id'] ?? null)
            ?? Product::query()->where('salla_product_id', $payload['salla_product_id'] ?? '')->first();

        if (! $product || $product->stock_status === 'in_stock') {
            return;
        }

        // Business rule (Business Ontology, Automation Rule entity):
        // "Maximum one alert per Product per 24 hours" — basic noise guard.
        $cacheKey = "low-stock-alert:{$product->id}";
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, now()->addDay());

        $task = Task::query()->create([
            'organization_id' => $product->organization_id,
            'title' => "تنبيه مخزون منخفض: {$product->name}",
            'description' => "حالة المخزون الحالية: {$product->stock_status} ({$product->stock_quantity} وحدة)",
            'status' => 'created',
        ]);

        $task->recordEvent(eventType: 'AutomationTriggered', payload: [
            'rule' => 'low_stock_alert',
            'product_id' => $product->id,
            'stock_status' => $product->stock_status,
        ]);
    }
}
