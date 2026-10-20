<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('contents')]
#[Fillable([
    'type',
    'slug',
    'category_id',
    'current_revision_id',
    'published_revision_id',
    'status',
    'scheduled_publish_at',
    'archived_at',
])]
class Content extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'category_id' => 'integer',
            'current_revision_id' => 'integer',
            'published_revision_id' => 'integer',
            'scheduled_publish_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<ContentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'category_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<ContentRevision, $this> */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'current_revision_id', 'id');
    }

    /** @return BelongsTo<ContentRevision, $this> */
    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'published_revision_id', 'id');
    }

    /** @return HasMany<ContentRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class, 'content_id', 'id');
    }

    /** @return HasMany<PublicationAction, $this> */
    public function publicationActions(): HasMany
    {
        return $this->hasMany(PublicationAction::class, 'content_id', 'id');
    }
}
