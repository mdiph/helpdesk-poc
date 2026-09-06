<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named role. The set of roles is fixed (see RoleName) and seeded once;
 * this table exists so users can be linked by foreign key and so role
 * metadata (description) lives in one place.
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'name' => RoleName::class,
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Note: not named is() - that would clash with Eloquent's Model::is(). */
    public function hasName(RoleName $name): bool
    {
        return $this->name === $name;
    }
}
