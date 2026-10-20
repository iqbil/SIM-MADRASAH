<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('content_files')]
#[Fillable([
    'content_revision_id',
    'file_id',
    'position',
    'caption',
    'alt_text',
    'purpose',
])]
class ContentFile extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'content_revision_id' => 'integer',
            'file_id' => 'integer',
            'position' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<ContentRevision, $this> */
    public function contentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'content_revision_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
