<?php

namespace App\Models;

use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The v1.0 Secrets Manager stand-in for OAuth tokens — see the
 * integration_credentials migration's doc comment for why this is a
 * separate table from Integration (health/status only).
 */
class IntegrationCredential extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = [
        'integration_id', 'access_token', 'refresh_token', 'secrets', 'expires_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        // Client secrets/webhook secrets/app secrets entered via the
        // Integrations settings page — encrypted at rest like the tokens.
        'secrets' => 'encrypted:array',
        'expires_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
