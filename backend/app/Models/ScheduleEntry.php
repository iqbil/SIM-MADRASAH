<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('schedule_entries')]
#[Fillable([
    'timetable_id',
    'entry_key',
])]
class ScheduleEntry extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'timetable_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Timetable, $this> */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'timetable_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<ScheduleEntryVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ScheduleEntryVersion::class, 'schedule_entry_id', 'id');
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'schedule_entry_id', 'id');
    }
}
