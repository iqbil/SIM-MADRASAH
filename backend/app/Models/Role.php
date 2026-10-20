<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('roles')]
#[Fillable([
    'code',
    'name',
    'description',
    'is_active',
])]
class Role extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => 1,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<UserRole, $this> */
    public function userAssignments(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id', 'id');
    }

    /** @return HasMany<RolePermission, $this> */
    public function permissionAssignments(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id', 'id');
    }
}
