<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('timetables')]
#[Fillable([
    'semester_id',
    'code',
    'name',
])]
class Timetable extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'semester_id' => 'integer',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<TimetableVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(TimetableVersion::class, 'timetable_id', 'id');
    }

    /** @return HasMany<ScheduleEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class, 'timetable_id', 'id');
    }
}
