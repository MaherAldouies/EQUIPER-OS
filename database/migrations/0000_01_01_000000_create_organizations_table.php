<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Organization — the tenant boundary (Business Ontology, Administration Domain).
 * In v1.0 there is exactly one row: EQUIPER. Every scoped table below carries
 * organization_id from day one so no future migration is needed to enable
 * multi-tenancy (per the "cheap insurance" recommendation in the PRD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active'); // active | suspended | archived
            $table->jsonb('settings')->default('{}');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
