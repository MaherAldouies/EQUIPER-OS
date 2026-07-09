<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO Asset — on-page SEO elements tied to a Product (Business Ontology,
 * SEO Domain). v1.0 scope: meta title + meta description only, generated
 * by the AI Reasoning Service and gated behind Approval before use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('asset_type'); // meta_title | meta_description
            $table->text('value');
            $table->string('status')->default('generated'); // generated | reviewed | applied | outdated
            $table->timestamps();

            $table->index(['product_id', 'asset_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_assets');
    }
};
