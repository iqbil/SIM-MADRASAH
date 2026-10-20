<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('schedule_entry_slots')]
#[Fillable([
    'schedule_entry_version_id',
    'lesson_slot_id',
    'position',
    'start_time_snapshot',
    'end_time_snapshot',
    'jp_snapshot',
])]
class ScheduleEntrySlot extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'schedule_entry_version_id' => 'integer',
            'lesson_slot_id' => 'integer',
            'position' => 'integer',
            'jp_snapshot' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<ScheduleEntryVersion, $this> */
    public function scheduleEntryVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntryVersion::class, 'schedule_entry_version_id', 'id');
    }

    /** @return BelongsTo<LessonSlot, $this> */
    public function lessonSlot(): BelongsTo
    {
        return $this->belongsTo(LessonSlot::class, 'lesson_slot_id', 'id');
    }
}
