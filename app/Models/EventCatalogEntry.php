<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCatalogEntry extends Model
{
    protected $table = 'event_catalog';

    protected $primaryKey = 'event_type';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_type', 'aggregate_type', 'owning_domain', 'description',
        'payload_schema', 'requires_approval_downstream',
    ];

    protected $casts = [
        'payload_schema' => 'array',
        'requires_approval_downstream' => 'boolean',
    ];
}
