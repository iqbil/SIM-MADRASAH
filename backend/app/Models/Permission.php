<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('permissions')]
#[Fillable([
    'code',
    'module',
    'action',
    'description',
    'is_sensitive',
])]
class Permission extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_sensitive' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'is_sensitive' => 'boolean',
        ];
    }

    /** @return HasMany<RolePermission, $this> */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'permission_id', 'id');
    }

    /** @return HasMany<AuthorizationMandate, $this> */
    public function authorizationMandates(): HasMany
    {
        return $this->hasMany(AuthorizationMandate::class, 'permission_id', 'id');
    }
}
