<?php

namespace App\Models;

use App\Traits\HasDomainEvents;
use App\Traits\HasUuidPrimaryKey;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User — implements the Business Ontology's "Team Member" entity
 * (Administration Domain): the human counterpart to an AI Agent.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasDomainEvents, HasUuidPrimaryKey, Notifiable;

    protected $fillable = [
        'organization_id', 'name', 'email', 'password', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Ontology rule: a Team Member cannot act outside their Role's
     * defined Permission scope — enforced at the ontology level.
     */
    public function hasPermission(string $permissionKey): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('key', $permissionKey))
            ->exists();
    }
}
