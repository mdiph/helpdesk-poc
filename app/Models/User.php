<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Role is needed for almost every authorization check. */
    protected $with = ['role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ---------------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Tickets this user opened. */
    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    /** Tickets currently assigned to this user. */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    // ---------------------------------------------------------------------
    // Role helpers (used by policies and the RoleMiddleware)
    // ---------------------------------------------------------------------

    public function roleName(): ?RoleName
    {
        return $this->role?->name;
    }

    public function hasRole(RoleName ...$roles): bool
    {
        return in_array($this->roleName(), $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin);
    }

    public function isL1(): bool
    {
        return $this->hasRole(RoleName::L1);
    }

    public function isL2(): bool
    {
        return $this->hasRole(RoleName::L2);
    }

    public function isViewer(): bool
    {
        return $this->hasRole(RoleName::Viewer);
    }

    /** Agents can be assigned tickets and work on them. */
    public function isAgent(): bool
    {
        return $this->hasRole(RoleName::Admin, RoleName::L1, RoleName::L2);
    }

    // ---------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->active()->whereHas('role', function (Builder $q) {
            $q->whereIn('name', [
                RoleName::Admin->value,
                RoleName::L1->value,
                RoleName::L2->value,
            ]);
        });
    }
}
