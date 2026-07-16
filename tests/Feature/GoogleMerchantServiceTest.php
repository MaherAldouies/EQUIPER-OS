<?php

namespace Tests\Feature;

use App\Models\AnalyticsSignal;
use App\Models\Integration;
use App\Models\Organization;
use App\Services\GoogleMerchant\GoogleMerchantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\HasFakeGoogleServiceAccountKey;
use Tests\TestCase;

class GoogleMerchantServiceTest extends TestCase
{
    use HasFakeGoogleServiceAccountKey, RefreshDatabase;

    private function makeIntegration(Organization $organization): Integration
    {
        $integration = Integration::query()->create([
            'organization_id' => $organization->id,
            'provider' => 'google_merchant',
            'status' => 'configuring',
            'settings' => ['merchant_id' => '999888777'],
        ]);

        $integration->credential()->create([
            'secrets' => [
                'client_email' => 'merchant-svc@project.iam.gserviceaccount.com',
                'private_key' => $this->fakePrivateKeyPem(),
            ],
        ]);

        return $integration;
    }

    public function test_pull_daily_summary_does_nothing_when_not_configured(): void
    {
        $organization = Organization::factory()->create();

        Http::fake();

        (new GoogleMerchantService($organization->id))->pullDailySummary(now());

        Http::assertNothingSent();
        $this->assertDatabaseCount('analytics_signals', 0);
    }

    public function test_pull_daily_summary_counts_active_products_and_issues(): void
    {
        $organization = Organization::factory()->create();
        $this->makeIntegration($organization);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token', 'expires_in' => 3600], 200),
            'https://shoppingcontent.googleapis.com/*' => Http::response([
                'resources' => [
                    ['productId' => 'p1', 'itemLevelIssues' => []],
                    ['productId' => 'p2', 'itemLevelIssues' => [['code' => 'price_mismatch']]],
                    ['productId' => 'p3', 'itemLevelIssues' => []],
                ],
            ], 200),
        ]);

        (new GoogleMerchantService($organization->id))->pullDailySummary(now());

        $this->assertSame(3.0, (float) AnalyticsSignal::query()->where('metric_key', 'merchant_active_products')->where('organization_id', $organization->id)->firstOrFail()->value);
        $this->assertSame(1.0, (float) AnalyticsSignal::query()->where('metric_key', 'merchant_products_with_issues')->where('organization_id', $organization->id)->firstOrFail()->value);

        $integration = Integration::query()->where('organization_id', $organization->id)->where('provider', 'google_merchant')->firstOrFail();
        $this->assertSame('connected', $integration->status);
    }
}
