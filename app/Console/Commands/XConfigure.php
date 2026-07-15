<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\Organization;
use Illuminate\Console\Command;

/**
 * One-time setup command: persists X_ACCESS_TOKEN/X_REFRESH_TOKEN
 * (OAuth 2.0 User Context tokens — X does not accept app-only auth for
 * posting) from .env into the encrypted IntegrationCredential row.
 */
class XConfigure extends Command
{
    protected $signature = 'x:configure';

    protected $description = 'Persist X_ACCESS_TOKEN/X_REFRESH_TOKEN from .env into the X Integration credential record.';

    public function handle(): int
    {
        $accessToken = env('X_ACCESS_TOKEN');
        $refreshToken = env('X_REFRESH_TOKEN');

        if (! $accessToken) {
            $this->error('X_ACCESS_TOKEN is not set in .env — nothing to configure.');

            return self::FAILURE;
        }

        $organization = Organization::query()->first();

        if (! $organization) {
            $this->error('No Organization found — run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'provider' => 'x'],
            ['status' => 'configuring']
        );

        $integration->credential()->updateOrCreate([], [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addHours(2),
        ]);

        $integration->markHealthy();

        $this->info('X credentials stored for organization: '.$organization->name);

        return self::SUCCESS;
    }
}
