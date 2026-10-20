<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('portal_announcement_targets')]
#[Fillable([
    'announcement_id',
    'scope_type',
    'class_period_id',
    'student_id',
    'scope_key',
])]
class PortalAnnouncementTarget extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'announcement_id' => 'integer',
            'class_period_id' => 'integer',
            'student_id' => 'integer',
        ];
    }

    /** @return BelongsTo<PortalAnnouncement, $this> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(PortalAnnouncement::class, 'announcement_id', 'id');
    }

    /** @return BelongsTo<ClassPeriod, $this> */
    public function classPeriod(): BelongsTo
    {
        return $this->belongsTo(ClassPeriod::class, 'class_period_id', 'id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
}
