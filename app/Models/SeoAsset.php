<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SeoAsset extends Model
{
    use HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'product_id', 'asset_type', 'value', 'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function approval(): MorphOne
    {
        return $this->morphOne(Approval::class, 'approvable');
    }
}
