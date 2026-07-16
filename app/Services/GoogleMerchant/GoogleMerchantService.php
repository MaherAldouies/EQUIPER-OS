<?php

namespace App\Services\GoogleMerchant;

use App\Models\AnalyticsSignal;
use App\Models\Integration;
use App\Services\Google\GoogleServiceAccountToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * GoogleMerchantService — F9 Dashboard: pulls product feed health
 * (active product count, count with issues) from the Content API for
 * Shopping, via the same service-account auth as GoogleAnalyticsService.
 */
class GoogleMerchantService
{
    private const SCOPE = 'https://www.googleapis.com/auth/content';

    public function __construct(
        private readonly string $organizationId,
    ) {}

    public function pullDailySummary(\DateTimeInterface $date): void
    {
        $merchantId = Integration::config($this->organizationId, 'google_merchant', 'merchant_id');
        $clientEmail = Integration::config($this->organizationId, 'google_merchant', 'client_email');
        $privateKey = Integration::config($this->organizationId, 'google_merchant', 'private_key');

        if (! $merchantId || ! $clientEmail || ! $privateKey) {
            return; // not configured yet — nothing to sync
        }

        try {
            $token = GoogleServiceAccountToken::mint((string) $clientEmail, (string) $privateKey, self::SCOPE);

            $baseUrl = Integration::config($this->organizationId, 'google_merchant', 'api_base_url', config('equiperos.google_merchant.api_base_url'));

            $response = Http::withToken($token)
                ->baseUrl($baseUrl)
                ->get("/{$merchantId}/productstatuses", ['maxResults' => 250]);

            if (! $response->successful()) {
                throw new RuntimeException("Merchant Center productstatuses failed with status {$response->status()}: {$response->body()}");
            }

            $resources = $response->json('resources', []);
            $total = count($resources);
            $withIssues = count(array_filter($resources, fn ($r) => ! empty($r['itemLevelIssues'])));

            DB::transaction(function () use ($date, $total, $withIssues) {
                $this->upsert('merchant_active_products', (float) $total, $date);
                $this->upsert('merchant_products_with_issues', (float) $withIssues, $date);
            });

            $this->integration()->markHealthy();
        } catch (Throwable $e) {
            Log::error('Google Merchant Center pull failed', ['error' => $e->getMessage()]);
            $this->integration()->markDegraded($e->getMessage());
            throw $e;
        }
    }

    private function upsert(string $metricKey, float $value, \DateTimeInterface $date): void
    {
        AnalyticsSignal::query()->updateOrCreate(
            [
                'organization_id' => $this->organizationId,
                'metric_key' => $metricKey,
                'source' => 'google_merchant',
                'signal_date' => $date->format('Y-m-d'),
            ],
            ['value' => $value, 'unit' => 'count', 'confidence' => 'normal']
        );
    }

    private function integration(): Integration
    {
        return Integration::query()->firstOrCreate(
            ['organization_id' => $this->organizationId, 'provider' => 'google_merchant'],
            ['status' => 'configuring']
        );
    }
}
