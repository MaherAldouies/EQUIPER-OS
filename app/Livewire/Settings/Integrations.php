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

    public string $meta_ig_user_id = '';

    public string $meta_page_id = '';

    public string $tiktok_privacy_level = 'PUBLIC_TO_EVERYONE';

    public string $x_client_id = '';

    public string $x_user_id = '';

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

    public string $x_access_token = '';

    public string $x_refresh_token = '';

    public function mount(): void
    {
        Gate::authorize('integration.configure');

        // Non-secret identifiers are safe to prefill for editing.
        $orgId = auth()->user()->organization_id;
        $this->salla_client_id = (string) Integration::config($orgId, 'salla', 'client_id', '');
        $this->whatsapp_phone_number_id = (string) Integration::config($orgId, 'whatsapp', 'phone_number_id', '');
        $this->meta_ig_user_id = (string) Integration::config($orgId, 'meta', 'ig_user_id', '');
        $this->meta_page_id = (string) Integration::config($orgId, 'meta', 'page_id', '');
        $this->tiktok_privacy_level = (string) Integration::config($orgId, 'tiktok', 'privacy_level', 'PUBLIC_TO_EVERYONE');
        $this->x_client_id = (string) Integration::config($orgId, 'x', 'client_id', '');
        $this->x_user_id = (string) Integration::config($orgId, 'x', 'user_id', '');
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

        $this->saveProvider('meta', ['ig_user_id' => $this->meta_ig_user_id, 'page_id' => $this->meta_page_id], [
            'verify_token' => $this->meta_verify_token,
            'app_secret' => $this->meta_app_secret,
        ], accessToken: $this->meta_access_token);

        $this->reset(['meta_access_token', 'meta_verify_token', 'meta_app_secret']);
        session()->flash('status', 'تم حفظ إعدادات Instagram/Facebook.');
    }

    public function saveTiktok(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('tiktok', ['privacy_level' => $this->tiktok_privacy_level], [], accessToken: $this->tiktok_access_token);

        $this->reset(['tiktok_access_token']);
        session()->flash('status', 'تم حفظ إعدادات تيك توك.');
    }

    public function saveX(): void
    {
        Gate::authorize('integration.configure');

        $this->saveProvider('x', ['client_id' => $this->x_client_id, 'user_id' => $this->x_user_id], [], accessToken: $this->x_access_token, refreshToken: $this->x_refresh_token);

        $this->reset(['x_access_token', 'x_refresh_token']);
        session()->flash('status', 'تم حفظ إعدادات X (تويتر).');
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
            ],
        ]);
    }
}
