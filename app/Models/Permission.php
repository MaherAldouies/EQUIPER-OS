<?php

namespace App\Models;

use App\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['key', 'description', 'status'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
