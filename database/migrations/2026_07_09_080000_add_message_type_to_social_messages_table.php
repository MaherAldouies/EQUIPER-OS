<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguishes a comment thread (reply target = the comment's own ID,
 * via POST /{comment-id}/replies) from a direct-message thread (reply
 * target = the sender's PSID, via POST /{page-id}/messages) — Meta
 * uses two different Graph API endpoints to reply depending on which
 * kind of conversation this is, and social_messages didn't originally
 * record that distinction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_messages', function (Blueprint $table) {
            $table->string('message_type')->default('dm')->after('provider'); // dm | comment
        });
    }

    public function down(): void
    {
        Schema::table('social_messages', function (Blueprint $table) {
            $table->dropColumn('message_type');
        });
    }
};
