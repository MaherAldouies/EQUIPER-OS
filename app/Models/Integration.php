<?php

namespace App\Models;

use App\Events\IntegrationWentDegraded;
use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

/**
 * Integration — Business Ontology, Administration Domain. Health/status
 * only; actual credentials live in the Secrets Manager, never here
 * (Infrastructure Architecture document, Section 8).
 */
class Integration extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'provider', 'status', 'last_successful_sync_at', 'last_error',
    ];

    protected $casts = [
        'last_successful_sync_at' => 'datetime',
    ];

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
