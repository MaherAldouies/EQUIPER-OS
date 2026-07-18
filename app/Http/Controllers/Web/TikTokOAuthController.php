<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * TikTokOAuthController — "Connect with TikTok" flow (Login Kit /
 * OAuth 2.0 authorization code), the one-click alternative to manually
 * generating and pasting an access token on the Integrations settings
 * page. Needs a one-time Client Key/Secret from a free TikTok for
 * Developers app (see Settings\Integrations::saveTiktok — the
 * client_key/client_secret fields on the TikTok card).
 */
class TikTokOAuthController extends Controller
{
    // video.publish: required by TikTokPublisher's Content Posting API
    // call. user.info.basic: minimal identity scope TikTok requires on
    // every app regardless of which other scopes are requested.
    private const SCOPE = 'user.info.basic,video.publish';

    public function connect(Request $request)
    {
        Gate::authorize('integration.configure');

        $organizationId = $request->user()->organization_id;

        $clientKey = Integration::config($organizationId, 'tiktok', 'client_key');
        if (! $clientKey) {
            return redirect()->route('settings.integrations')
                ->with('status', 'احفظ Client Key و Client Secret الخاصين بتطبيق TikTok أولًا (كارت TikTok).');
        }

        $state = Str::random(32);
        // TikTok's v2 authorize endpoint rejects the request outright
        // without PKCE (confirmed live: "correct the following:
        // code_challenge") — unlike Google/Meta, it's mandatory here,
        // not optional hardening.
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        session(['tiktok_oauth_state' => $state, 'tiktok_oauth_code_verifier' => $codeVerifier]);

        $params = [
            'client_key' => $clientKey,
            'scope' => self::SCOPE,
            'response_type' => 'code',
            'redirect_uri' => route('integrations.tiktok.callback'),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        $authUrl = Integration::config($organizationId, 'tiktok', 'auth_url', config('equiperos.tiktok.auth_url'));

        return redirect($authUrl.'?'.http_build_query($params));
    }

    public function callback(Request $request)
    {
        Gate::authorize('integration.configure');

        $organizationId = $request->user()->organization_id;
        $expectedState = session('tiktok_oauth_state');
        $codeVerifier = session('tiktok_oauth_code_verifier');
        session()->forget(['tiktok_oauth_state', 'tiktok_oauth_code_verifier']);

        if ($request->query('state') !== $expectedState) {
            return redirect()->route('settings.integrations')->with('status', 'فشل التحقق من الربط — حاول تاني.');
        }

        if ($request->has('error') || ! $request->has('code')) {
            return redirect()->route('settings.integrations')->with('status', 'تم إلغاء الربط مع TikTok.');
        }

        $clientKey = Integration::config($organizationId, 'tiktok', 'client_key');
        $clientSecret = Integration::config($organizationId, 'tiktok', 'client_secret');
        $tokenUrl = Integration::config($organizationId, 'tiktok', 'token_url', config('equiperos.tiktok.token_url'));

        $response = Http::asForm()->post($tokenUrl, [
            'client_key' => $clientKey,
            'client_secret' => $clientSecret,
            'code' => $request->query('code'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('integrations.tiktok.callback'),
            'code_verifier' => $codeVerifier,
        ]);

        if (! $response->successful()) {
            return redirect()->route('settings.integrations')->with('status', 'فشل ربط TikTok: '.$response->body());
        }

        $body = $response->json();

        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $organizationId, 'provider' => 'tiktok'],
            ['status' => 'configuring']
        );

        $credential = $integration->credential ?: $integration->credential()->create([]);

        $credential->update(array_filter([
            'access_token' => $body['access_token'] ?? null,
            'refresh_token' => $body['refresh_token'] ?? $credential->refresh_token,
            'expires_at' => isset($body['expires_in']) ? now()->addSeconds((int) $body['expires_in']) : null,
        ], fn ($value) => $value !== null));

        return redirect()->route('settings.integrations')->with('status', 'تم الربط بحساب TikTok بنجاح.');
    }
}
