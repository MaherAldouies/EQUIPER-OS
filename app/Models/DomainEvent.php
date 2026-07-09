<?php

namespace App\Models;

use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

/**
 * DomainEvent — the append-only Event Store AND the Audit Log
 * (Business Ontology, Section 4 "Shared Kernel: Domain Event" and
 * Administration Domain's Audit Log entity — deliberately the same
 * underlying table viewed from two angles, per the Ontology document).
 *
 * NEVER updated after creation except to set published_at by the relay
 * worker. No update/delete should ever be called on this model outside
 * of that one field.
 */
class DomainEvent extends Model
{
    use HasUuidPrimaryKey;

    public $timestamps = false; // occurred_at is the only time field that matters

    protected $fillable = [
        'organization_id', 'event_type', 'aggregate_type', 'aggregate_id',
        'actor_type', 'actor_id', 'payload', 'caused_by_event_id',
        'published_at', 'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
        'occurred_at' => 'datetime',
    ];
}
