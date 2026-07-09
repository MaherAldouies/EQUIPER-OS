<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\AI\ContentSeoGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * GenerateProductContentJob — F6, dispatched from two places by design:
 * App\Listeners\DispatchContentGenerationJob (in-process, same
 * deployment) AND App\Console\Commands\ConsumeProductEnrichedEvents
 * (Redis Streams, for any future out-of-process subscriber). Both are
 * legitimate per the Infrastructure Architecture document — this Job
 * implements ShouldBeUnique keyed by productId specifically so that
 * dual-triggering never causes a double-generation race condition; the
 * second dispatch is a safe no-op, not a duplicate content bug.
 *
 * Wrapping the Anthropic API call in a queued Job (rather than calling
 * ContentSeoGenerationService synchronously) gives production-ready
 * retry/backoff behavior for a flaky external API call, without
 * blocking the HTTP request/webhook that triggered enrichment.
 */
class GenerateProductContentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** How long the uniqueness lock holds — covers slow API latency plus all retries. */
    public int $uniqueFor = 900;

    /**
     * Exponential-ish backoff: 30s, 2min, 5min — reasonable for a
     * rate-limited external AI API, not so aggressive it burns the
     * cost ceiling from config('equiperos.ai.monthly_cost_ceiling_usd')
     * on retries alone.
     */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly string $productId,
    ) {}

    public function uniqueId(): string
    {
        return $this->productId;
    }

    public function handle(ContentSeoGenerationService $generationService): void
    {
        $product = Product::query()->findOrFail($this->productId);

        try {
            $generationService->generateForProduct($product);
        } catch (RuntimeException $e) {
            // Loop-detection guardrail exception (generation attempt
            // limit already reached) — this is NOT a transient failure,
            // retrying will never help. Fail permanently, don't burn
            // queue attempts on it.
            Log::warning('GenerateProductContentJob: non-retryable guard hit', [
                'product_id' => $this->productId,
                'message' => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('GenerateProductContentJob permanently failed', [
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
        ]);

        // TODO (v1.1): raise a Task for manual content creation when AI
        // generation permanently fails, so the product doesn't silently
        // stay without SEO/content — deliberately out of v1.0 scope per
        // the PRD's cut-list discipline, but flagged here as the real
        // gap it is.
    }
}
