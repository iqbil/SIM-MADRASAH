<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('portal_announcement_files')]
#[Fillable([
    'announcement_id',
    'file_id',
    'position',
])]
class PortalAnnouncementFile extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'announcement_id' => 'integer',
            'file_id' => 'integer',
            'position' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<PortalAnnouncement, $this> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(PortalAnnouncement::class, 'announcement_id', 'id');
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
