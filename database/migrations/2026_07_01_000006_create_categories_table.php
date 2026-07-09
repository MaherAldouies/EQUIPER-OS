<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Category — EQUIPER's own corrected taxonomy (Business Ontology,
 * Product Domain), distinct from Salla's raw category tree. Exists
 * specifically to resolve the known miscategorization issue (224
 * refrigerators + 38 freezers misfiled under pastry equipment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active'); // proposed | active | deprecated
            $table->uuid('deprecated_in_favor_of')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        // Added as a separate statement: on Postgres, the primary key
        // constraint for a uuid->primary() column is compiled after any
        // explicit $table->foreign() call in the same Schema::create(),
        // so a self-referencing FK declared inline fails with "no unique
        // constraint matching given keys" — the PK doesn't exist yet.
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
