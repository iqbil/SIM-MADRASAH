<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bk_categories')]
#[Fillable([
    'code',
    'name',
    'description',
    'retention_policy_id',
    'is_active',
])]
class BkCategory extends DomainModel
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
            'retention_policy_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<RetentionPolicy, $this> */
    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id', 'id');
    }

    /** @return HasMany<BkCategoryGrant, $this> */
    public function bkCategoryGrants(): HasMany
    {
        return $this->hasMany(BkCategoryGrant::class, 'category_id', 'id');
    }

    /** @return HasMany<BkCase, $this> */
    public function bkCases(): HasMany
    {
        return $this->hasMany(BkCase::class, 'category_id', 'id');
    }
}
