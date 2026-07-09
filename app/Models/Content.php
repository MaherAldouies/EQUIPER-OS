<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    use HasFactory, HasDomainEvents, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'product_id', 'title', 'body',
        'generated_by', 'brand_voice_id', 'status',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ContentAsset::class);
    }
}
