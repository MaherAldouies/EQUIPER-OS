<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * All EQUIPER OS entities use UUID primary keys rather than auto-incrementing
 * integers — deliberate, since entity IDs are referenced inside domain_events
 * payloads and may eventually need to be globally unique across a future
 * multi-tenant SaaS deployment (Business Ontology, Organization entity).
 */
trait HasUuidPrimaryKey
{
    public static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
