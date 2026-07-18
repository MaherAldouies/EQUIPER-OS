<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * MetaOAuthController — "Connect with Facebook" flow for Instagram +
 * Facebook: the one-click alternative to manually generating a token in
 * Graph API Explorer and typing in Page ID / Instagram User ID by hand.
 *
 * After the standard OAuth code exchange, this walks the extra Graph
 * API steps Meta requires to reach something ContentAsset publishing
 * can actually use: short-lived user token -> long-lived user token ->
 * /me/accounts (the Pages this user manages, each with its own
 * long-lived Page access token) -> instagram_business_account per Page.
 * If the user manages exactly one Page, it's saved immediately; if
 * several, a short picker page lets them choose (App Secret and OAuth
 * setup happen once regardless of how many Pages exist).
 */
class MetaOAuthController extends Controller
{
    private const SCOPE = 'pages_show_list,pages_read_engagement,pages_manage_posts,pages_messaging,instagram_basic,instagram_content_publish,instagram_manage_comments,instagram_manage_messages,business_management';

    public function connect(Request $request)
    {
        Gate::authorize('integration.configure');

        $organizationId = $request->user()->organization_id;

        $clientId = Integration::config($organizationId, 'meta', 'client_id');
        if (! $clientId) {
            return redirect()->route('settings.integrations')
                ->with('status', 'احفظ App ID و App Secret الخاصين بتطبيق Meta أولًا (كارت Instagram + Facebook).');
        }

        $state = Str::random(32);
        session(['meta_oauth_state' => $state]);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => route('integrations.meta.callback'),
            'state' => $state,
            'scope' => self::SCOPE,
            'response_type' => 'code',
        ];

        $authUrl = Integration::config($organizationId, 'meta', 'auth_url', config('equiperos.meta.auth_url'));

        return redirect($authUrl.'?'.http_build_query($params));
    }

    public function callback(Request $request)
    {
        Gate::authorize('integration.configure');

        $organizationId = $request->user()->organization_id;
        $expectedState = session('meta_oauth_state');
        session()->forget('meta_oauth_state');

        if ($request->query('state') !== $expectedState) {
            return redirect()->route('settings.integrations')->with('status', 'فشل التحقق من الربط — حاول تاني.');
        }

        if ($request->has('error') || ! $request->has('code')) {
            return redirect()->route('settings.integrations')->with('status', 'تم إلغاء الربط مع Meta.');
        }

        $clientId = Integration::config($organizationId, 'meta', 'client_id');
        $clientSecret = Integration::config($organizationId, 'meta', 'app_secret');
        $baseUrl = Integration::config($organizationId, 'meta', 'api_base_url', config('equiperos.meta.api_base_url'));

        $shortLived = Http::get("{$baseUrl}/oauth/access_token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => route('integrations.meta.callback'),
            'code' => $request->query('code'),
        ]);

        if (! $shortLived->successful()) {
            return redirect()->route('settings.integrations')->with('status', 'فشل ربط Meta: '.$shortLived->body());
        }

        $longLived = Http::get("{$baseUrl}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'fb_exchange_token' => $shortLived->json('access_token'),
        ]);

        if (! $longLived->successful()) {
            return redirect()->route('settings.integrations')->with('status', 'فشل تجديد رمز Meta: '.$longLived->body());
        }

        $userToken = $longLived->json('access_token');

        $accounts = Http::get("{$baseUrl}/me/accounts", [
            'fields' => 'id,name,access_token',
            'access_token' => $userToken,
        ]);

        if (! $accounts->successful()) {
            return redirect()->route('settings.integrations')->with('status', 'تعذّر جلب صفحات فيسبوك: '.$accounts->body());
        }

        $pages = collect($accounts->json('data', []))->map(function (array $page) use ($baseUrl) {
            $igLookup = Http::get("{$baseUrl}/{$page['id']}", [
                'fields' => 'instagram_business_account',
                'access_token' => $page['access_token'],
            ]);

            return [
                'id' => $page['id'],
                'name' => $page['name'],
                'access_token' => $page['access_token'],
                'ig_user_id' => $igLookup->json('instagram_business_account.id'),
            ];
        })->values();

        if ($pages->isEmpty()) {
            return redirect()->route('settings.integrations')
                ->with('status', 'محصلش على أي صفحة فيسبوك تديرها — لازم تكون Admin على صفحة واحدة على الأقل.');
        }

        if ($pages->count() === 1) {
            $this->savePage($organizationId, $pages->first());

            return redirect()->route('settings.integrations')->with('status', 'تم الربط بحساب Meta بنجاح — صفحة "'.$pages->first()['name'].'".');
        }

        session(['meta_oauth_pages' => $pages->all()]);

        return redirect()->route('integrations.meta.pick-page');
    }

    public function pickPage(Request $request)
    {
        Gate::authorize('integration.configure');

        $pages = session('meta_oauth_pages', []);

        if ($pages === []) {
            return redirect()->route('settings.integrations');
        }

        return view('integrations.meta-pick-page', ['pages' => $pages]);
    }

    public function selectPage(Request $request)
    {
        Gate::authorize('integration.configure');

        $data = $request->validate(['page_id' => ['required', 'string']]);

        $pages = session('meta_oauth_pages', []);
        $page = collect($pages)->firstWhere('id', $data['page_id']);

        if (! $page) {
            return redirect()->route('settings.integrations')->with('status', 'الصفحة المختارة لم تعد متاحة — أعد الربط من جديد.');
        }

        session()->forget('meta_oauth_pages');

        $this->savePage($request->user()->organization_id, $page);

        return redirect()->route('settings.integrations')->with('status', 'تم الربط بحساب Meta بنجاح — صفحة "'.$page['name'].'".');
    }

    /**
     * @param  array{id: string, name: string, access_token: string, ig_user_id: string|null}  $page
     */
    private function savePage(string $organizationId, array $page): void
    {
        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $organizationId, 'provider' => 'meta'],
            ['status' => 'configuring']
        );

        $integration->update([
            'settings' => array_filter([
                'page_id' => $page['id'],
                'ig_user_id' => $page['ig_user_id'],
            ]) + (array) $integration->settings,
        ]);

        $credential = $integration->credential ?: $integration->credential()->create([]);
        $credential->update(['access_token' => $page['access_token'], 'expires_at' => null]);

        $integration->markHealthy();
    }
}
