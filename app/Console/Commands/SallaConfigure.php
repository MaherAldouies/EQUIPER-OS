<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Models\Organization;
use Illuminate\Console\Command;

/**
 * One-time setup command: persists the SALLA_ACCESS_TOKEN /
 * SALLA_REFRESH_TOKEN issued from the Salla Partners Portal (Custom
 * App / Easy Mode — no OAuth redirect flow needed for a single-store
 * integration) into the encrypted IntegrationCredential row.
 */
class SallaConfigure extends Command
{
    protected $signature = 'salla:configure';

    protected $description = 'Persist SALLA_ACCESS_TOKEN/SALLA_REFRESH_TOKEN from .env into the Salla Integration credential record.';

    public function handle(): int
    {
        $accessToken = env('SALLA_ACCESS_TOKEN');
        $refreshToken = env('SALLA_REFRESH_TOKEN');

        if (! $accessToken) {
            $this->error('SALLA_ACCESS_TOKEN is not set in .env — nothing to configure.');

            return self::FAILURE;
        }

        $organization = Organization::query()->first();

        if (! $organization) {
            $this->error('No Organization found — run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'provider' => 'salla'],
            ['status' => 'configuring']
        );

        $integration->credential()->updateOrCreate([], [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addDays(14),
        ]);

        $integration->markHealthy();

        $this->info('Salla credentials stored for organization: '.$organization->name);

        return self::SUCCESS;
    }
}
