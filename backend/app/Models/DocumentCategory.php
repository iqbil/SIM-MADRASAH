<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('document_categories')]
#[Fillable([
    'code',
    'name',
    'access_class',
    'retention_policy_id',
    'is_active',
])]
class DocumentCategory extends DomainModel
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

    /** @return HasMany<DocumentCategoryGrant, $this> */
    public function documentCategoryGrants(): HasMany
    {
        return $this->hasMany(DocumentCategoryGrant::class, 'category_id', 'id');
    }

    /** @return HasMany<Letter, $this> */
    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'category_id', 'id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id', 'id');
    }
}
