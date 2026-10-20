<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('bk_category_grants')]
#[Fillable([
    'user_id',
    'category_id',
    'can_read_internal',
    'can_write',
    'can_share',
    'can_export',
    'can_download',
    'effective_from',
    'effective_to',
    'reason',
])]
class BkCategoryGrant extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'can_read_internal' => false,
        'can_write' => false,
        'can_share' => false,
        'can_export' => false,
        'can_download' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'user_id' => 'integer',
            'category_id' => 'integer',
            'can_read_internal' => 'boolean',
            'can_write' => 'boolean',
            'can_share' => 'boolean',
            'can_export' => 'boolean',
            'can_download' => 'boolean',
            'effective_from' => 'immutable_datetime',
            'effective_to' => 'immutable_datetime',
            'granted_by' => 'integer',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** @return BelongsTo<BkCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BkCategory::class, 'category_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'id');
    }
}
