<?php

namespace App\Services\Salla;

use App\Models\Integration;
use App\Models\IntegrationCredential;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * SallaApiClient — the only class that speaks Salla's HTTP/OAuth
 * dialect for reconciliation polling (webhooks are handled separately
 * by SallaWebhookController). Confirmed against docs.salla.dev:
 * base URL https://api.salla.dev/admin/v2, Bearer token auth, access
 * tokens valid 14 days, refresh tokens valid 1 month via
 * https://accounts.salla.sa/oauth2/token (grant_type=refresh_token).
 */
class SallaApiClient
{
    public function __construct(
        private readonly string $organizationId,
    ) {}

    /**
     * @return array{data: array, pagination: array}
     */
    public function products(int $page = 1, int $perPage = 100): array
    {
        return $this->get('/products', ['page' => $page, 'per_page' => $perPage]);
    }

    /**
     * @return array{data: array, pagination: array}
     */
    public function orders(int $page = 1, int $perPage = 100): array
    {
        return $this->get('/orders', ['page' => $page, 'per_page' => $perPage]);
    }

    private function get(string $path, array $query): array
    {
        $baseUrl = Integration::config($this->organizationId, 'salla', 'api_base_url', config('equiperos.salla.api_base_url'));

        $response = Http::withToken($this->accessToken())
            ->baseUrl($baseUrl)
            ->get($path, $query);

        if ($response->status() === 401) {
            // Access token expired mid-run — refresh once and retry.
            $response = Http::withToken($this->refreshAccessToken())
                ->baseUrl($baseUrl)
                ->get($path, $query);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Salla API request to {$path} failed with status {$response->status()}: {$response->body()}");
        }

        return $response->json();
    }

    private function accessToken(): string
    {
        $credential = $this->credential();

        if ($credential->isExpired()) {
            return $this->refreshAccessToken();
        }

        return (string) $credential->access_token;
    }

    private function refreshAccessToken(): string
    {
        $credential = $this->credential();

        if (! $credential->refresh_token) {
            throw new RuntimeException('Salla integration has no refresh token configured — run `php artisan salla:configure`.');
        }

        $tokenUrl = Integration::config($this->organizationId, 'salla', 'token_url', config('equiperos.salla.token_url'));

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credential->refresh_token,
            'client_id' => Integration::config($this->organizationId, 'salla', 'client_id'),
            'client_secret' => Integration::config($this->organizationId, 'salla', 'client_secret'),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Salla token refresh failed with status {$response->status()}: {$response->body()}");
        }

        $body = $response->json();

        $credential->forceFill([
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? $credential->refresh_token,
            'expires_at' => now()->addSeconds((int) ($body['expires_in'] ?? 1209600)),
        ])->save();

        return (string) $credential->access_token;
    }

    private function credential(): IntegrationCredential
    {
        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $this->organizationId, 'provider' => 'salla'],
            ['status' => 'configuring']
        );

        $credential = $integration->credential;

        if (! $credential) {
            throw new RuntimeException('Salla integration has no stored credentials — run `php artisan salla:configure`.');
        }

        return $credential;
    }
}
