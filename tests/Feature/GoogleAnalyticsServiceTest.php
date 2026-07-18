<?php

namespace Tests\Feature;

use App\Models\AnalyticsSignal;
use App\Models\Integration;
use App\Models\Organization;
use App\Services\GoogleAnalytics\GoogleAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\Concerns\HasFakeGoogleServiceAccountKey;
use Tests\TestCase;

class GoogleAnalyticsServiceTest extends TestCase
{
    use HasFakeGoogleServiceAccountKey, RefreshDatabase;

    private function makeIntegration(Organization $organization): Integration
    {
        $integration = Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'google_analytics',
            'status' => 'configuring',
            'settings' => ['property_id' => 'properties/123456'],
        ]);

        $integration->credential()->create([
            'secrets' => [
                'client_email' => 'svc@project.iam.gserviceaccount.com',
                'private_key' => $this->fakePrivateKeyPem(),
            ],
        ]);

        return $integration;
    }

    public function test_pull_daily_summary_does_nothing_when_not_configured(): void
    {
        $organization = Organization::factory()->create();

        Http::fake();

        (new GoogleAnalyticsService($organization->id))->pullDailySummary(now());

        Http::assertNothingSent();
        $this->assertDatabaseCount('analytics_signals', 0);
    }

    public function test_pull_daily_summary_writes_signals_and_marks_healthy(): void
    {
        $organization = Organization::factory()->create();
        $this->makeIntegration($organization);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600], 200),
            'https://analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [
                    ['metricValues' => [['value' => '42'], ['value' => '17'], ['value' => '3']]],
                ],
            ], 200),
        ]);

        $today = now();
        (new GoogleAnalyticsService($organization->id))->pullDailySummary($today);

        $this->assertSame(42.0, (float) AnalyticsSignal::query()->where('metric_key', 'ga4_sessions')->where('organization_id', $organization->id)->firstOrFail()->value);
        $this->assertSame(17.0, (float) AnalyticsSignal::query()->where('metric_key', 'ga4_users')->where('organization_id', $organization->id)->firstOrFail()->value);
        $this->assertSame(3.0, (float) AnalyticsSignal::query()->where('metric_key', 'ga4_conversions')->where('organization_id', $organization->id)->firstOrFail()->value);

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'google_analytics')->firstOrFail();
        $this->assertSame('connected', $integration->status);
    }

    public function test_pull_daily_summary_prefers_oauth_credential_over_service_account(): void
    {
        $organization = Organization::factory()->create();

        Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'google',
            'settings' => ['client_id' => 'oauth-client-id'],
        ])->credential()->create(['secrets' => ['client_secret' => 'oauth-client-secret']]);

        $integration = Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'google_analytics',
            'status' => 'configuring',
            'settings' => ['property_id' => 'properties/123456'],
        ]);
        // Expired access token + a refresh_token: forces the OAuth refresh path.
        $integration->credential()->create([
            'access_token' => 'stale-token',
            'refresh_token' => 'oauth-refresh-token',
            'expires_at' => now()->subHour(),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'refreshed-token', 'expires_in' => 3600], 200),
            'https://analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [['metricValues' => [['value' => '5'], ['value' => '2'], ['value' => '1']]]],
            ], 200),
        ]);

        (new GoogleAnalyticsService($organization->id))->pullDailySummary(now());

        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'oauth-refresh-token');

        $this->assertSame(5.0, (float) AnalyticsSignal::query()->where('metric_key', 'ga4_sessions')->where('organization_id', $organization->id)->firstOrFail()->value);
    }

    public function test_pull_daily_summary_marks_degraded_on_api_failure(): void
    {
        $organization = Organization::factory()->create();
        $this->makeIntegration($organization);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600], 200),
            'https://analyticsdata.googleapis.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->expectException(RuntimeException::class);

        try {
            (new GoogleAnalyticsService($organization->id))->pullDailySummary(now());
        } finally {
            $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'google_analytics')->firstOrFail();
            $this->assertSame('degraded', $integration->status);
        }
    }
}
