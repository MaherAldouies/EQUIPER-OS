<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics Signal — Business Ontology, Analytics Domain. Normalizes
 * raw events from any source domain into a consistent, comparable unit.
 * Business rule (verbatim from the Ontology): "A signal below a defined
 * confidence threshold must be flagged as 'low confidence' and not
 * treated as ground truth" — hence the confidence column below.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();

            $table->string('metric_key'); // e.g. daily_revenue, organic_clicks, content_pipeline_pending_count
            $table->decimal('value', 14, 2);
            $table->string('unit')->nullable(); // SAR, count, percent
            $table->string('source'); // salla | google_search_console | internal
            $table->string('confidence')->default('normal'); // low | normal | high
            $table->date('signal_date');
            $table->jsonb('dimensions')->default('{}'); // e.g. { "product_id": "...", "category_id": "..." }
            $table->timestamps();

            $table->index(['metric_key', 'signal_date']);
            $table->unique(['organization_id', 'metric_key', 'source', 'signal_date'], 'signals_unique_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_signals');
    }
};
