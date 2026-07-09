<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event Catalog — the governance registry from the Event-Driven Architecture
 * document (Section 3.3): "an unregistered event type cannot be published"
 * (PRD F1 acceptance criteria). This is deliberately a database table, not
 * just a code constant list, so it can be inspected/audited independently
 * of any single codebase deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_catalog', function (Blueprint $table) {
            $table->string('event_type')->primary(); // e.g. ProductEnriched
            $table->string('aggregate_type');          // e.g. Product
            $table->string('owning_domain');            // e.g. Product Domain
            $table->text('description');
            $table->jsonb('payload_schema')->default('{}'); // documented expected shape
            $table->boolean('requires_approval_downstream')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_catalog');
    }
};
