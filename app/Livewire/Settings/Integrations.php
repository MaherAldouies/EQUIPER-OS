<?php

namespace App\Livewire\Settings;

use App\Models\Integration;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings\Integrations — lets an Owner connect Salla and the Social
 * Media Hub platforms from the app instead of editing .env and running
 * an artisan command. Secret values (tokens, client secrets, webhook
 * secrets) are write-only here: once saved, the form never redisplays
 * the decrypted value back into the browser — only whether it's
 * currently configured — matching how the rest of the app treats
 * credentials (see IntegrationCredential's encrypted casts).
 *
 * Values entered here take priority over equiperos.{provider}.* in
 * config/.env (see Integration::config()), which remains a valid
 * fallback for a fresh install with no UI configuration yet.
 */
#[Layout('layouts.app')]
class Integrations extends Component
{
    // Non-secret identifiers (Integration.settings)
    public string $salla_client_id = '';

    public string $whatsapp_phone_number_id = '';

    public string $meta_client_id = '';

    public string $meta_ig_user_id = '';

    public string $meta_page_id = '';

    public string $tiktok_privacy_level = 'PUBLIC_TO_EVERYONE';

    public string $tiktok_client_key = '';

    public string $x_client_id = '';

    public string $x_user_id = '';

    public string $google_analytics_property_id = '';

    public string $google_merchant_id = '';

    public string $google_tag_manager_container_id = '';

    public string $google_client_id = '';

    // Secrets (IntegrationCredential — access_token/refresh_token have
    // dedicated columns already; everything else goes in `secrets`)
    public string $salla_client_secret = '';

    public string $salla_webhook_secret = '';

    public string $salla_access_token = '';

    public string $salla_refresh_token = '';

    public string $whatsapp_access_token = '';

    public string $whatsapp_verify_token = '';

    public string $whatsapp_app_secret = '';

    public string $meta_access_token = '';

    public string $meta_verify_token = '';

    public string $meta_app_secret = '';

    public string $tiktok_access_token = '';

    public string $tiktok_client_secret = '';

    public string $x_access_token = '';

    public string $x_refresh_token = '';

    public string $google_analytics_client_email = '';

    public string $google_analytics_private_key = '';

    public string $google_merchant_client_email = '';

    public string $google_merchant_private_key = '';

    public string $google_client_secret = '';

    public function mount(): void
    {
        Gate::authorize('integration.configure');

        // Non-secret identifiers are safe to prefill for editing.
        $orgId = auth()->user()->organization_id;
        $this->salla_client_id = (string) Integration::config($orgId, 'salla', 'client_id', '');
        $this->whatsapp_phone_number_id = (string) Integration::config($orgId, 'whatsapp', 'phone_number_id', '');
        $this->meta_client_id = (string) Integration::config($orgId, 'meta', 'client_id', '');
        $this->meta_ig_user_id = (string) Integration::config($orgId, 'meta', 'ig_user_id', '');
        $this->meta_page_id = (string) Integration::config($orgId, 'meta', 'page_id', '');
        $this->tiktok_privacy_level = (string) Integration::config($orgId, 'tiktok', 'privacy_level', 'PUBLIC_TO_EVERYONE');
        $this->tiktok_client_key = (string) Integration::config($orgId, 'tiktok', 'client_key', '');
        $this->x_client_id = (string) Integration::config($orgId, 'x', 'client_id', '');
        $this->x_user_id = (string) Integration::config($orgId, 'x', 'user_id', '');
        $this->google_analytics_property_id = (string) Integration::config($orgId, 'google_analytics', 'property_id', '');
        $this->google_merchant_id = (string) Integration::config($orgId, 'google_merchant', 'merchant_id', '');
        $this->google_tag_manager_container_id = (string) Integration::config($orgId, 'google_tag_manager', 'container_id', '');
        $this->google_client_id = (string) Integration::config($orgId, 'google', 'client_id', '');
    }

    public function saveSalla(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('salla', ['client_id' => $this->salla_client_id], [
            'client_secret' => $this->salla_client_secret,
            'webhook_secret' => $this->salla_webhook_secret,
        ], accessToken: $this->salla_access_token, refreshToken: $this->salla_refresh_token);

        $this->reset(['salla_client_secret', 'salla_webhook_secret', 'salla_access_token', 'salla_refresh_token']);
        session()->flash('status', 'تم حفظ إعدادات سلة.');
    }

    public function saveWhatsapp(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('whatsapp', ['phone_number_id' => $this->whatsapp_phone_number_id], [
            'verify_token' => $this->whatsapp_verify_token,
            'app_secret' => $this->whatsapp_app_secret,
        ], accessToken: $this->whatsapp_access_token);

        $this->reset(['whatsapp_access_token', 'whatsapp_verify_token', 'whatsapp_app_secret']);
        session()->flash('status', 'تم حفظ إعدادات واتساب بزنس.');
    }

    public function saveMeta(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('meta', [
            'client_id' => $this->meta_client_id,
            'ig_user_id' => $this->meta_ig_user_id,
            'page_id' => $this->meta_page_id,
        ], [
            'verify_token' => $this->meta_verify_token,
            'app_secret' => $this->meta_app_secret,
        ], accessToken: $this->meta_access_token);

        $this->reset(['meta_access_token', 'meta_verify_token', 'meta_app_secret']);
        session()->flash('status', 'تم حفظ إعدادات Instagram/Facebook.');
    }

    public function saveTiktok(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('tiktok', [
            'privacy_level' => $this->tiktok_privacy_level,
            'client_key' => $this->tiktok_client_key,
        ], [
            'client_secret' => $this->tiktok_client_secret,
        ], accessToken: $this->tiktok_access_token);

        $this->reset(['tiktok_access_token', 'tiktok_client_secret']);
        session()->flash('status', 'تم حفظ إعدادات تيك توك.');
    }

    public function saveX(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('x', ['client_id' => $this->x_client_id, 'user_id' => $this->x_user_id], [], accessToken: $this->x_access_token, refreshToken: $this->x_refresh_token);

        $this->reset(['x_access_token', 'x_refresh_token']);
        session()->flash('status', 'تم حفظ إعدادات X (تويتر).');
    }

    public function saveGoogleAnalytics(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('google_analytics', ['property_id' => $this->google_analytics_property_id], [
            'client_email' => $this->google_analytics_client_email,
            'private_key' => $this->google_analytics_private_key,
        ]);

        $this->reset(['google_analytics_client_email', 'google_analytics_private_key']);
        session()->flash('status', 'تم حفظ إعدادات Google Analytics.');
    }

    public function saveGoogleMerchant(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('google_merchant', ['merchant_id' => $this->google_merchant_id], [
            'client_email' => $this->google_merchant_client_email,
            'private_key' => $this->google_merchant_private_key,
        ]);

        $this->reset(['google_merchant_client_email', 'google_merchant_private_key']);
        session()->flash('status', 'تم حفظ إعدادات Google Merchant Center.');
    }

    public function saveGoogleTagManager(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('google_tag_manager', ['container_id' => $this->google_tag_manager_container_id], []);

        // GTM has no live API call to prove connectivity — recording the
        // container ID IS the complete "connection" step for this
        // provider, so mark it healthy immediately rather than leaving
        // it stuck on "configuring" forever.
        if ($this->google_tag_manager_container_id !== '') {
            Integration::query()
                ->where('organization_id', auth()->user()->organization_id)
                ->where('provider', 'google_tag_manager')
                ->first()
                ?->markHealthy();
        }

        session()->flash('status', 'تم حفظ إعدادات Google Tag Manager.');
    }

    public function saveGoogleOAuthApp(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('google', ['client_id' => $this->google_client_id], [
            'client_secret' => $this->google_client_secret,
        ]);

        $this->reset(['google_client_secret']);
        session()->flash('status', 'تم حفظ بيانات تطبيق Google — تقدر دلوقتي تضغط "ربط بحساب Google" في كارت Analytics أو Merchant Center.');
    }

    private function saveProvider(string $provider, array $settings, array $secrets, ?string $accessToken = null, ?string $refreshToken = null): void
    {
        $orgId = auth()->user()->organization_id;

        $integration = Integration::query()->firstOrCreate(
            ['organization_id' => $orgId, 'provider' => $provider],
            ['status' => 'configuring']
        );

        // Never overwrite a non-empty setting with a blank one — an
        // empty field means "left unchanged", not "clear this value".
        $integration->update([
            'settings' => array_filter($settings, fn ($v) => $v !== '') + (array) $integration->settings,
        ]);

        $credential = $integration->credential ?: $integration->credential()->create([]);

        $newSecrets = array_filter($secrets, fn ($v) => $v !== '');
        $update = [];
        if ($newSecrets !== []) {
            $update['secrets'] = $newSecrets + (array) $credential->secrets;
        }
        if ($accessToken !== null && $accessToken !== '') {
            $update['access_token'] = $accessToken;
            $update['expires_at'] = null; // unknown expiry for a manually-pasted token; treat as always-valid until a 401 proves otherwise
        }
        if ($refreshToken !== null && $refreshToken !== '') {
            $update['refresh_token'] = $refreshToken;
        }

        if ($update !== []) {
            $credential->update($update);
        }
    }

    public function render()
    {
        $orgId = auth()->user()->organization_id;

        $status = fn (string $provider) => Integration::query()
            ->where('organization_id', $orgId)
            ->where('provider', $provider)
            ->first();

        return view('livewire.settings.integrations', [
            'googleRedirectUri' => route('integrations.google.callback'),
            'tiktokRedirectUri' => route('integrations.tiktok.callback'),
            'webhookUrls' => [
                'salla' => url('/api/webhooks/salla'),
                'whatsapp' => url('/api/webhooks/whatsapp'),
                'meta' => url('/api/webhooks/meta'),
            ],
            'statuses' => [
                'salla' => $status('salla'),
                'whatsapp' => $status('whatsapp'),
                'meta' => $status('meta'),
                'tiktok' => $status('tiktok'),
                'x' => $status('x'),
                'google_analytics' => $status('google_analytics'),
                'google_merchant' => $status('google_merchant'),
                'google_tag_manager' => $status('google_tag_manager'),
                'google' => $status('google'),
            ],
        ]);
    }
}
