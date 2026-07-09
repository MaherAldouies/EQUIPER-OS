<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

/**
 * AnalyticsSignal — Business Ontology, Analytics Domain. Normalized,
 * comparable measurement derived from raw activity across any source
 * domain. See DashboardAggregationService for how these are produced.
 */
class AnalyticsSignal extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'metric_key', 'value', 'unit', 'source',
        'confidence', 'signal_date', 'dimensions',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'signal_date' => 'date',
        'dimensions' => 'array',
    ];

    /**
     * Per the Ontology's explicit business rule: never treat a
     * low-confidence signal as ground truth in downstream reasoning.
     */
    public function isReliable(): bool
    {
        return $this->confidence !== 'low';
    }
}
