<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content & Content Asset — Business Ontology, Content Domain.
 * Content is the idea/message; Content Asset is the channel-ready rendering.
 * Every Content Asset must trace back to a parent Content (Ontology rule:
 * "no orphaned channel assets").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('generated_by')->default('ai'); // ai | human
            $table->foreignUuid('brand_voice_id')->nullable()->constrained('brand_voices')->nullOnDelete();
            $table->string('status')->default('drafted'); // drafted | under_review | approved | published | archived
            $table->timestamps();
        });

        Schema::create('content_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('channel'); // instagram_caption | seo_meta_title | seo_meta_description | product_description
            $table->text('body');
            $table->jsonb('channel_metadata')->default('{}'); // e.g. character limits, dimensions
            $table->string('status')->default('generated'); // generated | reviewed | approved | scheduled | published | retired
            $table->timestampTz('scheduled_for')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_assets');
        Schema::dropIfExists('contents');
    }
};
