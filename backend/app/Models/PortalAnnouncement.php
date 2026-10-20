<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('portal_announcements')]
#[Fillable([
    'title',
    'body',
    'status',
    'starts_at',
    'ends_at',
])]
class PortalAnnouncement extends DomainModel
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
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'published_by' => 'integer',
            'published_at' => 'immutable_datetime',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<PortalAnnouncementTarget, $this> */
    public function targets(): HasMany
    {
        return $this->hasMany(PortalAnnouncementTarget::class, 'announcement_id', 'id');
    }

    /** @return HasMany<PortalAnnouncementFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(PortalAnnouncementFile::class, 'announcement_id', 'id');
    }
}
