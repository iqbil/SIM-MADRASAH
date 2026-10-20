<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('documents')]
#[Fillable([
    'category_id',
    'letter_id',
    'archive_code',
    'title',
    'description',
    'document_date',
    'status',
    'retention_policy_id',
    'retain_until',
    'legal_hold',
])]
class Document extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'legal_hold' => false,
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'category_id' => 'integer',
            'letter_id' => 'integer',
            'document_date' => 'immutable_date:Y-m-d',
            'retention_policy_id' => 'integer',
            'retain_until' => 'immutable_date:Y-m-d',
            'legal_hold' => 'boolean',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<DocumentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id', 'id');
    }

    /** @return BelongsTo<Letter, $this> */
    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class, 'letter_id', 'id');
    }

    /** @return BelongsTo<RetentionPolicy, $this> */
    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<DocumentFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class, 'document_id', 'id');
    }

    /** @return HasMany<PublicationAsset, $this> */
    public function publicationAssets(): HasMany
    {
        return $this->hasMany(PublicationAsset::class, 'source_document_id', 'id');
    }
}
