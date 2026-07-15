<?php

namespace App\Models;

use App\Events\IntegrationWentDegraded;
use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Integration — Business Ontology, Administration Domain. Health/status
 * only; actual credentials live in the Secrets Manager, never here
 * (Infrastructure Architecture document, Section 8) — see
 * IntegrationCredential, the v1.0 stand-in for that Secrets Manager.
 */
class Integration extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'provider', 'status', 'settings', 'last_successful_sync_at', 'last_error',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_successful_sync_at' => 'datetime',
    ];

    public function credential(): HasOne
    {
        return $this->hasOne(IntegrationCredential::class);
    }

    /**
     * Resolves a config value for a given organization+provider,
     * preferring what the Owner entered in the Integrations settings
     * page (Integration.settings for non-secret values,
     * IntegrationCredential.secrets for sensitive ones) over the
     * equiperos.{provider}.{key} config/.env value — so a fresh
     * install still works from .env alone, but the UI is the primary,
     * friendlier path once configured.
     */
    public static function config(string $organizationId, string $provider, string $key, mixed $default = null): mixed
    {
        $integration = static::query()
            ->where('organization_id', $organizationId)
            ->where('provider', $provider)
            ->first();

        $fromSettings = data_get($integration?->settings, $key);
        if ($fromSettings !== null) {
            return $fromSettings;
        }

        $fromSecrets = data_get($integration?->credential?->secrets, $key);
        if ($fromSecrets !== null) {
            return $fromSecrets;
        }

        return \Illuminate\Support\Facades\Config::get("equiperos.{$provider}.{$key}", $default);
    }

    public function markDegraded(string $error): void
    {
        $this->forceFill(['status' => 'degraded', 'last_error' => $error])->save();

        $this->recordEvent(eventType: 'IntegrationDegraded', payload: [
            'provider' => $this->provider,
            'error' => $error,
        ]);

        // Triggers NotifyOwnerOfIntegrationDegradation (F12 acceptance
        // criteria: "a status change ... sends an immediate alert").
        IntegrationWentDegraded::dispatch($this, $error);
    }

    public function markHealthy(): void
    {
        $this->forceFill([
            'status' => 'connected',
            'last_error' => null,
            'last_successful_sync_at' => now(),
        ])->save();
    }
}
