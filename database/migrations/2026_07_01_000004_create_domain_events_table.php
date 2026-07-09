<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain Event — the Event Backbone's durable, append-only Event Store,
 * implementing the Transactional Outbox pattern described in the
 * Event-Driven Architecture document (Section 3.2) and the Infrastructure
 * Architecture document.
 *
 * Every state-changing write in the system writes exactly one row here,
 * IN THE SAME DATABASE TRANSACTION as the state change. A separate relay
 * worker (see app/Console/Commands/RelayOutboxEvents.php) polls rows where
 * published_at IS NULL and publishes them to the Redis Streams broker.
 *
 * This table also serves as the Audit Log (Ontology, Administration Domain)
 * — it is never updated or deleted, only appended to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();

            // What happened, and to what entity
            $table->string('event_type');          // e.g. ProductEnriched, ContentDrafted, ApprovalGranted
            $table->string('aggregate_type');       // e.g. Product, Content, Approval
            $table->uuid('aggregate_id');            // the entity's ID

            // Who/what caused it — supports both human Team Members and AI Agents
            $table->string('actor_type')->default('system'); // system | user | ai_agent
            $table->uuid('actor_id')->nullable();

            // The event's data
            $table->jsonb('payload')->default('{}');

            // Causal chain — which earlier event, if any, caused this one.
            // This is what makes full "why did the system do that" traces
            // possible (Event-Driven Architecture doc, Section 3.4).
            $table->uuid('caused_by_event_id')->nullable();

            // Outbox relay state — NULL means "not yet delivered to the broker"
            $table->timestampTz('published_at')->nullable();

            $table->timestampTz('occurred_at')->useCurrent();

            // Append-only: no updated_at needed, events never change.
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index('event_type');
            $table->index('published_at'); // used by the relay worker's poll query
            $table->index('caused_by_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_events');
    }
};
