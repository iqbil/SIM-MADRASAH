<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('content_revisions')]
#[Fillable([
    'content_id',
    'version_number',
    'title',
    'excerpt',
    'body',
    'body_format',
    'event_start_at',
    'event_end_at',
    'location',
    'achievement_date',
    'achievement_level',
    'contact_email',
    'contact_phone',
    'metadata',
    'revision_reason',
])]
class ContentRevision extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'content_id' => 'integer',
            'version_number' => 'integer',
            'event_start_at' => 'immutable_datetime',
            'event_end_at' => 'immutable_datetime',
            'achievement_date' => 'immutable_date:Y-m-d',
            'metadata' => 'array',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Content, $this> */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<ContentFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ContentFile::class, 'content_revision_id', 'id');
    }

    /** @return HasMany<PublicationAsset, $this> */
    public function publicationAssets(): HasMany
    {
        return $this->hasMany(PublicationAsset::class, 'content_revision_id', 'id');
    }

    /** @return HasMany<PublicStaffProfile, $this> */
    public function publicStaffProfiles(): HasMany
    {
        return $this->hasMany(PublicStaffProfile::class, 'content_revision_id', 'id');
    }

    /** @return HasMany<PublicationAction, $this> */
    public function publicationActions(): HasMany
    {
        return $this->hasMany(PublicationAction::class, 'content_revision_id', 'id');
    }

    /** @return HasMany<ContentStudentLink, $this> */
    public function studentLinks(): HasMany
    {
        return $this->hasMany(ContentStudentLink::class, 'content_revision_id', 'id');
    }
}
