<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * GoogleOAuthController — "Connect with Google" flow for GA4 / Merchant
 * Center: the simpler alternative to the service-account fields on the
 * Integrations settings page. Needs a one-time OAuth Client ID/Secret
 * (see Settings\Integrations::saveGoogleOAuthApp — created once in
 * Google Cloud Console), then a single Google sign-in + consent click
 * per platform — no JSON key to copy, no separate "grant access" step
 * inside GA4 Admin / Merchant Center Users.
 */
class GoogleOAuthController extends Controller
{
    private const SCOPES = [
        'google_analytics' => 'https://www.googleapis.com/auth/analytics.readonly',
        'google_merchant' => 'https://www.googleapis.com/auth/content',
    ];

    public function connect(Request $request, string $provider)
    {
        Gate::authorize('integration.configure');

        $organizationId = $request->user()->organization_id;

        $clientId = Integration::config($organizationId, 'google', 'client_id');
        if (! $clientId) {
            return redirect()->route('settings.integrations')
                ->with('status', 'احفظ Client ID و Client Secret الخاصين بتطبيق Google أولًا (كارت "تطبيق Google").');
        }

        $state = Str::random(32);
        session(['google_oauth_state' => $state, 'google_oauth_provider' => $provider]);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => route('integrations.google.callback'),
            'response_type' => 'code',
            'scope' => self::SCOPES[$provider],
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        $authUrl = Integration::config($organizationId, 'google', 'auth_url', config('equiperos.google.auth_url'));

        return redirect($authUrl.'?'.http_build_query($params));
    }

    public function callback(Request $request)
    {
        Gate::authorize('integration.configure');

        $organizationId = $request->user()->organization_id;
        $provider = session('google_oauth_provider');
        $expectedState = session('google_oauth_state');
        session()->forget(['google_oauth_state', 'google_oauth_provider']);

        if (! $provider || ! isset(self::SCOPES[$provider]) || $request->query('state') !== $expectedState) {
            return redirect()->route('settings.integrations')->with('status', 'فشل التحقق من الربط — حاول تاني.');
        }

        if ($request->has('error') || ! $request->has('code')) {
            return redirect()->route('settings.integrations')->with('status', 'تم إلغاء الربط مع Google.');
        }

        $clientId = Integration::config($organizationId, 'google', 'client_id');
        $clientSecret = Integration::config($organizationId, 'google', 'client_secret');
        $tokenUrl = Integration::config($organizationId, 'google', 'token_url', config('equiperos.google.token_url'));

        $response = Http::asForm()->post($tokenUrl, [
            'code' => $request->query('code'),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => route('integrations.google.callback'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            return redirect()->route('settings.integrations')->with('status', 'فشل ربط Google: '.$response->body());
        }

        $body = $response->json();

        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $organizationId, 'provider' => $provider],
            ['status' => 'configuring']
        );

        $credential = $integration->credential ?: $integration->credential()->create([]);

        $credential->update(array_filter([
            'access_token' => $body['access_token'] ?? null,
            'refresh_token' => $body['refresh_token'] ?? $credential->refresh_token,
            'expires_at' => isset($body['expires_in']) ? now()->addSeconds((int) $body['expires_in']) : null,
        ], fn ($value) => $value !== null));

        return redirect()->route('settings.integrations')->with('status', 'تم الربط بحساب Google بنجاح.');
    }
}
