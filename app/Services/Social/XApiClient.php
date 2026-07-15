<?php

namespace App\Services\Social;

use App\Models\Integration;
use App\Models\IntegrationCredential;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * XApiClient — Social Media Hub epic, Phase 5. X (formerly Twitter)
 * requires OAuth 2.0 User Context (not app-only) to post — same
 * token-storage/refresh pattern as SallaApiClient, reusing
 * Integration + IntegrationCredential (provider = 'x'). Confirmed
 * against docs.x.com: base URL https://api.x.com/2, token refresh at
 * https://api.x.com/2/oauth2/token.
 *
 * COST WARNING: as of 2026 X has no free tier — every call here bills
 * the connected account (~$0.015/post write, ~$0.005/read). Callers
 * should not invoke mentions() on a tight polling interval.
 */
class XApiClient
{
    public function __construct(
        private readonly string $organizationId,
    ) {}

    public function postTweet(string $text, ?string $inReplyToTweetId = null): string
    {
        $body = array_filter([
            'text' => $text,
            'reply' => $inReplyToTweetId ? ['in_reply_to_tweet_id' => $inReplyToTweetId] : null,
        ]);

        $response = $this->request('post', '/tweets', $body);

        return $response['data']['id'] ?? '';
    }

    /**
     * @return array<int, array{id: string, text: string, author_id: string}>
     */
    public function mentions(string $userId, ?string $sinceId = null): array
    {
        $response = $this->request('get', "/users/{$userId}/mentions", array_filter([
            'since_id' => $sinceId,
            'tweet.fields' => 'author_id,created_at',
        ]));

        return $response['data'] ?? [];
    }

    private function request(string $method, string $path, array $params): array
    {
        $baseUrl = Integration::config($this->organizationId, 'x', 'api_base_url', config('equiperos.x.api_base_url'));

        $send = fn (string $token) => Http::withToken($token)
            ->baseUrl($baseUrl)
            ->{$method}($path, $params);

        $response = $send($this->accessToken());

        if ($response->status() === 401) {
            $response = $send($this->refreshAccessToken());
        }

        if (! $response->successful()) {
            throw new RuntimeException("X API request to {$path} failed with status {$response->status()}: {$response->body()}");
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
            throw new RuntimeException('X integration has no refresh token configured — set it up on the Integrations settings page.');
        }

        $tokenUrl = Integration::config($this->organizationId, 'x', 'token_url', config('equiperos.x.token_url'));

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credential->refresh_token,
            'client_id' => Integration::config($this->organizationId, 'x', 'client_id'),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("X token refresh failed with status {$response->status()}: {$response->body()}");
        }

        $body = $response->json();

        $credential->forceFill([
            'access_token' => $body['access_token'],
            'refresh_token' => $body['refresh_token'] ?? $credential->refresh_token,
            'expires_at' => now()->addSeconds((int) ($body['expires_in'] ?? 7200)),
        ])->save();

        return (string) $credential->access_token;
    }

    private function credential(): IntegrationCredential
    {
        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $this->organizationId, 'provider' => 'x'],
            ['status' => 'configuring']
        );

        $credential = $integration->credential;

        if (! $credential) {
            throw new RuntimeException('X integration has no stored credentials — set it up on the Integrations settings page.');
        }

        return $credential;
    }
}
