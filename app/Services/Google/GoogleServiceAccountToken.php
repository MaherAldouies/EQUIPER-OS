<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * GoogleServiceAccountToken — mints short-lived OAuth2 access tokens via
 * the service-account JWT-bearer flow (RFC 7523), shared by the Google
 * Analytics (GA4) and Google Merchant Center integrations. No user
 * consent screen is needed — the service account itself must be granted
 * access on the GA4 property / Merchant Center account directly.
 */
class GoogleServiceAccountToken
{
    public static function mint(
        string $clientEmail,
        string $privateKeyPem,
        string $scope,
        string $tokenUrl = 'https://oauth2.googleapis.com/token',
    ): string {
        $now = time();

        $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => $scope,
            'aud' => $tokenUrl,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "{$header}.{$claims}";

        // Google's downloadable service-account JSON key escapes newlines
        // as literal "\n" — normalize in case that raw JSON value was
        // pasted directly into the credential field.
        $normalizedKey = str_replace('\\n', "\n", $privateKeyPem);

        $privateKey = openssl_pkey_get_private($normalizedKey);
        if ($privateKey === false) {
            throw new RuntimeException('Invalid Google service account private key.');
        }

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $jwt = $signingInput.'.'.self::base64UrlEncode($signature);

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Google OAuth token request failed with status {$response->status()}: {$response->body()}");
        }

        return (string) $response->json('access_token');
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
