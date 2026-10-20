<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('schedule_entry_versions')]
#[Fillable([
    'schedule_entry_id',
    'timetable_version_id',
    'teaching_assignment_id',
    'day_of_week',
    'start_time_snapshot',
    'end_time_snapshot',
    'planned_jp',
    'planned_minutes',
    'room',
    'effective_from',
    'effective_to',
])]
class ScheduleEntryVersion extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'schedule_entry_id' => 'integer',
            'timetable_version_id' => 'integer',
            'teaching_assignment_id' => 'integer',
            'day_of_week' => 'integer',
            'planned_jp' => 'decimal:3',
            'planned_minutes' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
        ];
    }

    /** @return BelongsTo<ScheduleEntry, $this> */
    public function scheduleEntry(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class, 'schedule_entry_id', 'id');
    }

    /** @return BelongsTo<TimetableVersion, $this> */
    public function timetableVersion(): BelongsTo
    {
        return $this->belongsTo(TimetableVersion::class, 'timetable_version_id', 'id');
    }

    /** @return BelongsTo<TeachingAssignment, $this> */
    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id', 'id');
    }

    /** @return HasMany<ScheduleEntrySlot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(ScheduleEntrySlot::class, 'schedule_entry_version_id', 'id');
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'schedule_entry_version_id', 'id');
    }
}
