<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('document_category_grants')]
#[Fillable([
    'user_id',
    'category_id',
    'can_read',
    'can_write',
    'can_download',
    'can_export',
    'effective_from',
    'effective_to',
    'reason',
])]
class DocumentCategoryGrant extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'can_read' => false,
        'can_write' => false,
        'can_download' => false,
        'can_export' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'user_id' => 'integer',
            'category_id' => 'integer',
            'can_read' => 'boolean',
            'can_write' => 'boolean',
            'can_download' => 'boolean',
            'can_export' => 'boolean',
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

    /** @return BelongsTo<DocumentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'id');
    }
}
