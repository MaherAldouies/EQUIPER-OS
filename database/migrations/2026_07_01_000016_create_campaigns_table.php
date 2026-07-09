<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign — Business Ontology, Marketing Domain. v1.0 scope: manual
 * creation and grouping only (PRD F11) — no Advertisement/paid-spend
 * integration until v1.2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->text('goal')->nullable();
            $table->string('utm_campaign_slug')->nullable()->unique(); // for basic traffic attribution matching
            $table->string('status')->default('draft'); // draft | pending_approval | scheduled | active | completed | archived
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('campaign_content_asset', function (Blueprint $table) {
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('content_asset_id')->constrained('content_assets')->cascadeOnDelete();
            $table->primary(['campaign_id', 'content_asset_id']);
        });

        Schema::create('campaign_product', function (Blueprint $table) {
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['campaign_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_product');
        Schema::dropIfExists('campaign_content_asset');
        Schema::dropIfExists('campaigns');
    }
};
