<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brand Voice — the single authoritative definition of how EQUIPER
 * communicates (Business Ontology, Knowledge Domain). Only one may be
 * "active" per organization at a time (enforced in the model, see
 * App\Models\BrandVoice::activate()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_voices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('title');
            $table->text('tone_guidelines');
            $table->text('vocabulary_notes')->nullable();
            $table->text('things_to_avoid')->nullable();
            $table->text('brand_facts')->nullable(); // e.g. official Italian-brand agency positioning
            $table->string('status')->default('draft'); // draft | active | under_revision | superseded
            $table->foreignUuid('authored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_voices');
    }
};
