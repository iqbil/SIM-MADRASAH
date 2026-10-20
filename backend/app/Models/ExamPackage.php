<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('exam_packages')]
#[Fillable([
    'code',
    'name',
    'subject_id',
    'grade',
    'semester_id',
    'status',
    'template_snapshot',
    'content_hash',
    'supersedes_package_id',
])]
class ExamPackage extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'subject_id' => 'integer',
            'grade' => 'integer',
            'semester_id' => 'integer',
            'owner_employee_id' => 'integer',
            'template_snapshot' => 'array',
            'finalized_by' => 'integer',
            'finalized_at' => 'immutable_datetime',
            'supersedes_package_id' => 'integer',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by', 'id');
    }

    /** @return BelongsTo<ExamPackage, $this> */
    public function supersedesPackage(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'supersedes_package_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<ExamPackageItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ExamPackageItem::class, 'exam_package_id', 'id');
    }

    /** @return HasMany<ExamPackageFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ExamPackageFile::class, 'exam_package_id', 'id');
    }
}
