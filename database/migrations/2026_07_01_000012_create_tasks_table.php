<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task — a concrete, assignable unit of work (Business Ontology,
 * Workflow Domain). Automatically created whenever an Approval is
 * requested (PRD F13).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('related_approval_id')->nullable()->constrained('approvals')->nullOnDelete();
            $table->string('status')->default('created'); // created | assigned | in_progress | completed | cancelled
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
