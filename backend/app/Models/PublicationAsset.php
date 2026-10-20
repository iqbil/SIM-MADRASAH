<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('publication_assets')]
#[Fillable([
    'content_revision_id',
    'source_file_id',
    'public_file_id',
    'publication_action_id',
    'source_document_id',
    'approval_basis',
    'revoke_reason',
])]
class PublicationAsset extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'content_revision_id' => 'integer',
            'source_file_id' => 'integer',
            'public_file_id' => 'integer',
            'publication_action_id' => 'integer',
            'source_document_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ContentRevision, $this> */
    public function contentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'content_revision_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'source_file_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function publicFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'public_file_id', 'id');
    }

    /** @return BelongsTo<Document, $this> */
    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return BelongsTo<PublicationAction, $this> */
    public function publicationAction(): BelongsTo
    {
        return $this->belongsTo(PublicationAction::class, 'publication_action_id', 'id');
    }
}
