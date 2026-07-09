<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product — the canonical, enriched representation (Business Ontology,
 * Product Domain). salla_product_id + salla_raw_payload are the
 * Anti-Corruption Layer boundary: Salla's shape lives in salla_raw_payload
 * only; every other column is EQUIPER OS's own translated language.
 *
 * This table is READ-ONLY with respect to Salla-sourced fields — they are
 * only ever written by the Salla Sync Worker (see
 * app/Services/Salla/SallaSyncService.php), never by application code
 * directly (Ontology's Product entity Permission rule).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();

            // Anti-Corruption Layer boundary
            $table->string('salla_product_id')->unique();
            $table->jsonb('salla_raw_payload')->nullable();
            $table->string('salla_category_name')->nullable(); // Salla's own category, kept for comparison/audit

            // EQUIPER OS's own enriched language
            $table->string('name');
            $table->string('sku')->nullable();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('brand_name')->nullable();     // simple field in v1.0; full Brand entity deferred
            $table->string('supplier_name')->nullable();   // simple field in v1.0; full Supplier entity deferred
            $table->boolean('is_agency_brand')->default(false); // flags official Italian-brand agency products (Wega, BFC, UNOX, EKA)

            $table->string('lifecycle_state')->default('draft'); // draft | enriched | published | active | discontinued | archived
            $table->integer('stock_quantity')->default(0);
            $table->string('stock_status')->default('in_stock'); // in_stock | low_stock | out_of_stock | discontinued

            $table->timestampTz('last_synced_at')->nullable();
            $table->timestampTz('enriched_at')->nullable();
            $table->timestamps();

            $table->index('lifecycle_state');
            $table->index('stock_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
