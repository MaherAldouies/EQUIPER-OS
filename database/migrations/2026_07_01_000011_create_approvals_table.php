<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approval — the literal implementation of "human-in-the-loop"
 * (Business Ontology, Workflow Domain). A polymorphic gate: it can block
 * any approvable entity (Content Asset, SEO Asset, ...) from progressing
 * until a human signs off. This is the AI Operating Core document's
 * single most important safety entity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();

            // Polymorphic target — what is being approved
            $table->string('approvable_type'); // e.g. App\Models\ContentAsset, App\Models\SeoAsset
            $table->uuid('approvable_id');

            $table->string('status')->default('pending'); // pending | approved | rejected | expired
            $table->foreignUuid('requested_for_role')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
