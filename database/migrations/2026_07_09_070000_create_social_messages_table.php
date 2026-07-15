<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SocialMessage — the unified reply inbox (Social Media Hub epic).
 * Mirrors an inbound/outbound message or comment from any connected
 * platform (WhatsApp, Instagram, Facebook — X once wired). Replies are
 * always human-authored and human-sent from EQUIPER OS; there is no
 * AI-drafted or autonomous reply path here, per explicit product
 * decision — this table is a record of a human conversation, not an
 * AI agent's output.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('provider'); // whatsapp | meta_instagram | meta_facebook | x
            $table->string('external_conversation_id');
            $table->string('external_message_id')->nullable();
            $table->string('direction'); // inbound | outbound
            $table->string('from_name')->nullable();
            $table->text('body');
            $table->string('status')->default('unread'); // unread | read | replied
            $table->foreignUuid('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('received_at');
            $table->timestamps();

            $table->index(['organization_id', 'provider', 'external_conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_messages');
    }
};
