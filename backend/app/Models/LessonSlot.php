<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('lesson_slots')]
#[Fillable([
    'semester_id',
    'day_of_week',
    'sequence',
    'label',
    'start_time',
    'end_time',
    'jp_units',
    'is_break',
    'is_active',
])]
class LessonSlot extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_break' => false,
        'is_active' => 1,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'semester_id' => 'integer',
            'day_of_week' => 'integer',
            'sequence' => 'integer',
            'jp_units' => 'decimal:3',
            'is_break' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /** @return HasMany<ScheduleEntrySlot, $this> */
    public function scheduleEntrySlots(): HasMany
    {
        return $this->hasMany(ScheduleEntrySlot::class, 'lesson_slot_id', 'id');
    }
}
