<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role & Permission — per the Business Ontology (Administration Domain).
 * v1.0 ships four roles: Owner, Marketing Manager, SEO Specialist, Designer.
 * Deliberately simple RBAC (additive-only permissions per role) — the
 * Ontology explicitly defers fine-grained per-entity-instance permissions
 * to a later version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('key'); // owner | marketing_manager | seo_specialist | designer
            $table->string('name');
            $table->string('status')->default('active'); // active | deprecated
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // e.g. product.view, content.approve, campaign.manage
            $table->string('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
