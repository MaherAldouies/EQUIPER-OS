<?php

namespace App\Services\GoogleAnalytics;

use App\Models\AnalyticsSignal;
use App\Models\Integration;
use App\Services\Google\ResolvesGoogleAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * GoogleAnalyticsService — F9 Dashboard: pulls daily sessions/users/
 * conversions from the GA4 Data API (read-only). Supports two ways to
 * authorize (see ResolvesGoogleAccessToken): "Connect with Google"
 * OAuth (simplest — one sign-in + consent click), or a manually pasted
 * service-account key for setups that need a non-interactive credential.
 */
class GoogleAnalyticsService
{
    use ResolvesGoogleAccessToken;

    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function __construct(
        private readonly string $organizationId,
    ) {}

    public function pullDailySummary(\DateTimeInterface $date): void
    {
        $propertyId = Integration::config($this->organizationId, 'google_analytics', 'property_id');

        if (! $propertyId) {
            return; // not configured yet — nothing to sync
        }

        try {
            $token = $this->resolveAccessToken('google_analytics', self::SCOPE);

            if (! $token) {
                return; // property ID saved but no credential connected yet
            }

            $baseUrl = Integration::config($this->organizationId, 'google_analytics', 'api_base_url', config('equiperos.google_analytics.api_base_url'));

            $response = Http::withToken($token)
                ->baseUrl($baseUrl)
                ->post("/properties/{$propertyId}:runReport", [
                    'dateRanges' => [['startDate' => $date->format('Y-m-d'), 'endDate' => $date->format('Y-m-d')]],
                    'metrics' => [['name' => 'sessions'], ['name' => 'activeUsers'], ['name' => 'conversions']],
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("GA4 runReport failed with status {$response->status()}: {$response->body()}");
            }

            $values = $response->json('rows.0.metricValues', []);
            $sessions = (float) ($values[0]['value'] ?? 0);
            $users = (float) ($values[1]['value'] ?? 0);
            $conversions = (float) ($values[2]['value'] ?? 0);

            DB::transaction(function () use ($date, $sessions, $users, $conversions) {
                $this->upsert('ga4_sessions', $sessions, $date);
                $this->upsert('ga4_users', $users, $date);
                $this->upsert('ga4_conversions', $conversions, $date);
            });

            $this->integration()->markHealthy();
        } catch (Throwable $e) {
            Log::error('Google Analytics pull failed', ['error' => $e->getMessage()]);
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
                'source' => 'google_analytics',
                'signal_date' => $date->format('Y-m-d'),
            ],
            ['value' => $value, 'unit' => 'count', 'confidence' => 'normal']
        );
    }

    private function integration(): Integration
    {
        return Integration::query()->firstOrCreate(
            ['organization_id' => $this->organizationId, 'provider' => 'google_analytics'],
            ['status' => 'configuring']
        );
    }
}
