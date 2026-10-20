<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('public_staff_profiles')]
#[Fillable([
    'employee_id',
    'content_revision_id',
    'display_name',
    'public_position',
    'bio',
    'photo_public_file_id',
])]
class PublicStaffProfile extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_id' => 'integer',
            'content_revision_id' => 'integer',
            'photo_public_file_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<ContentRevision, $this> */
    public function contentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'content_revision_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function photoPublicFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_public_file_id', 'id');
    }
}
