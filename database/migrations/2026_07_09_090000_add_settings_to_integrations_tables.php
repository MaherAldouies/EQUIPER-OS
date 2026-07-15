<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the in-app Integrations settings page (F12 extended): lets an
 * Owner configure Salla/WhatsApp/Meta/TikTok/X credentials through the
 * UI instead of editing .env + running an artisan command. `settings`
 * holds non-secret identifiers (phone_number_id, ig_user_id, page_id,
 * client_id...); `secrets` on the credential row holds anything
 * sensitive (client_secret, webhook_secret, app_secret, verify_token),
 * encrypted at rest like access_token/refresh_token already are.
 * Both are optional — config()/.env remains a valid fallback (see
 * Integration::config()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->jsonb('settings')->nullable()->after('provider');
        });

        Schema::table('integration_credentials', function (Blueprint $table) {
            $table->text('secrets')->nullable()->after('refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn('settings');
        });

        Schema::table('integration_credentials', function (Blueprint $table) {
            $table->dropColumn('secrets');
        });
    }
};
