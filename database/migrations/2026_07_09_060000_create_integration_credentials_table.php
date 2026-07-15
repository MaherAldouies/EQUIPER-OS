<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deliberately a separate table from `integrations` (which is
 * health/status only, per that migration's own doc comment — "Actual
 * API keys live in a Secrets Manager, never here"). This table is the
 * minimal v1.0 stand-in for that Secrets Manager: encrypted-at-rest
 * OAuth tokens, one row per Integration, never joined into dashboard
 * queries that only need status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestamps();

            $table->unique('integration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_credentials');
    }
};
