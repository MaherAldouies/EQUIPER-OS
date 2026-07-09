<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integration — represents a connection to an external system (Business
 * Ontology, Administration Domain). Makes the Anti-Corruption Layer /
 * external dependency visible and monitorable (PRD F12). Credentials
 * themselves are NOT stored here — only status/health. Actual API keys
 * live in a Secrets Manager per the Infrastructure Architecture document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('provider'); // salla | google_search_console | anthropic
            $table->string('status')->default('configuring'); // configuring | connected | degraded | disconnected
            $table->timestampTz('last_successful_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
