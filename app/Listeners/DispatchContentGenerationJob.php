<?php

namespace App\Listeners;

use App\Events\ProductWasEnriched;
use App\Jobs\GenerateProductContentJob;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * DispatchContentGenerationJob — reacts to ProductWasEnriched by
 * dispatching GenerateProductContentJob onto the queue. Implements
 * ShouldQueue itself so even the *dispatch* doesn't block the request
 * that triggered enrichment (e.g. a bulk re-categorization + enrich
 * action from the Product controller).
 */
class DispatchContentGenerationJob implements ShouldQueue
{
    public function handle(ProductWasEnriched $event): void
    {
        GenerateProductContentJob::dispatch($event->product->id);
    }
}
