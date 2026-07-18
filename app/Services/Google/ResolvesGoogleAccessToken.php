<?php

namespace App\Services\Google;

use App\Models\Integration;
use App\Models\IntegrationCredential;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * ResolvesGoogleAccessToken — shared by GoogleAnalyticsService and
 * GoogleMerchantService. Prefers a "Connect with Google" OAuth
 * credential (access_token/refresh_token on IntegrationCredential) when
 * one exists; falls back to the service-account JWT-bearer flow
 * (client_email/private_key in secrets) otherwise. Requires the using
 * class to have `private readonly string $organizationId` and a
 * `private function integration(): Integration` method.
 */
trait ResolvesGoogleAccessToken
{
    private function resolveAccessToken(string $provider, string $scope): ?string
    {
        $credential = $this->integration()->credential;

        if ($credential && $credential->refresh_token) {
            if ($credential->access_token && ! $credential->isExpired()) {
                return (string) $credential->access_token;
            }

            return $this->refreshOAuthToken($credential);
        }

        $clientEmail = Integration::config($this->organizationId, $provider, 'client_email');
        $privateKey = Integration::config($this->organizationId, $provider, 'private_key');

        if ($clientEmail && $privateKey) {
            return GoogleServiceAccountToken::mint((string) $clientEmail, (string) $privateKey, $scope);
        }

        return null;
    }

    private function refreshOAuthToken(IntegrationCredential $credential): string
    {
        $clientId = Integration::config($this->organizationId, 'google', 'client_id');
        $clientSecret = Integration::config($this->organizationId, 'google', 'client_secret');
        $tokenUrl = Integration::config($this->organizationId, 'google', 'token_url', config('equiperos.google.token_url'));

        $response = Http::asForm()->post($tokenUrl, [
            'refresh_token' => $credential->refresh_token,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Google OAuth token refresh failed with status {$response->status()}: {$response->body()}");
        }

        $body = $response->json();

        $credential->forceFill([
            'access_token' => $body['access_token'],
            'expires_at' => now()->addSeconds((int) ($body['expires_in'] ?? 3600)),
        ])->save();

        return (string) $credential->access_token;
    }
}
