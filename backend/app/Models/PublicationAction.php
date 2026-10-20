<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('publication_actions')]
#[Fillable([
    'content_id',
    'content_revision_id',
    'action',
    'mandate_id',
    'reason',
    'acted_at',
    'operation_key',
])]
class PublicationAction extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'content_id' => 'integer',
            'content_revision_id' => 'integer',
            'actor_user_id' => 'integer',
            'mandate_id' => 'integer',
            'acted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Content, $this> */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'id');
    }

    /** @return BelongsTo<AuthorizationMandate, $this> */
    public function mandate(): BelongsTo
    {
        return $this->belongsTo(AuthorizationMandate::class, 'mandate_id', 'id');
    }

    /** @return BelongsTo<ContentRevision, $this> */
    public function contentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'content_revision_id', 'id');
    }

    /** @return HasMany<PublicationAsset, $this> */
    public function publicationAssets(): HasMany
    {
        return $this->hasMany(PublicationAsset::class, 'publication_action_id', 'id');
    }
}
