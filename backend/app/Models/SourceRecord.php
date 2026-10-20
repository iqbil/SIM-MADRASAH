<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('source_records')]
#[Fillable([
    'source_system',
    'entity_type',
    'external_id',
    'person_id',
    'student_id',
    'guardian_id',
    'employee_id',
    'subject_id',
    'class_id',
    'content_hash',
    'last_batch_id',
])]
class SourceRecord extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'person_id' => 'integer',
            'student_id' => 'integer',
            'guardian_id' => 'integer',
            'employee_id' => 'integer',
            'subject_id' => 'integer',
            'class_id' => 'integer',
            'last_batch_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<Guardian, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id', 'id');
    }

    /** @return BelongsTo<ImportBatche, $this> */
    public function lastBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatche::class, 'last_batch_id', 'id');
    }

    /** @return HasMany<ImportRow, $this> */
    public function importRows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'source_record_id', 'id');
    }
}
