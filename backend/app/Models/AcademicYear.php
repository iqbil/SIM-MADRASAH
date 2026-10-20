<?php

namespace App\Models;

use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('academic_years')]
#[Fillable([
    'code',
    'name',
    'start_date',
    'end_date',
    'status',
])]
class AcademicYear extends DomainModel
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'start_date' => 'immutable_date:Y-m-d',
            'end_date' => 'immutable_date:Y-m-d',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<Semester, $this> */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class, 'academic_year_id', 'id');
    }

    /** @return HasMany<ClassPeriod, $this> */
    public function classPeriods(): HasMany
    {
        return $this->hasMany(ClassPeriod::class, 'academic_year_id', 'id');
    }

    /** @return HasMany<AcademicCalendarEvent, $this> */
    public function academicCalendarEvents(): HasMany
    {
        return $this->hasMany(AcademicCalendarEvent::class, 'academic_year_id', 'id');
    }
}
