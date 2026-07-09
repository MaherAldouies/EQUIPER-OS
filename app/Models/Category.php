<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasDomainEvents, HasFactory, HasUuidPrimaryKey;

    protected $fillable = [
        'organization_id', 'parent_id', 'name', 'slug', 'status', 'deprecated_in_favor_of',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
