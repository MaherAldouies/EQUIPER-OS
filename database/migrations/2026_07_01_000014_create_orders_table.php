<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order — Business Ontology, Commerce Domain. "Orders are read-only
 * within EQUIPER OS — never mutated locally, only re-synced from Salla,
 * to prevent state drift with the actual source of truth." (Ontology
 * business rule, verbatim.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();

            // Anti-Corruption Layer boundary
            $table->string('salla_order_id')->unique();
            $table->jsonb('salla_raw_payload')->nullable();

            // Translated language
            $table->string('status'); // placed | confirmed | fulfilled | completed | returned | cancelled
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('customer_reference')->nullable(); // Salla customer id — Customer Domain deferred beyond v1.0
            $table->uuid('attributed_campaign_id')->nullable(); // set via UTM matching, F11

            $table->timestampTz('placed_at');
            $table->timestampTz('last_synced_at');
            $table->timestamps();

            $table->index('status');
            $table->index('placed_at');
        });

        Schema::create('order_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name_snapshot'); // preserved even if Product is later archived
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_line_items');
        Schema::dropIfExists('orders');
    }
};
