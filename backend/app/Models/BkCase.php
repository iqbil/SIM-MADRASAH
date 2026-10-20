<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bk_cases')]
#[Fillable([
    'student_id',
    'category_id',
    'assigned_employee_id',
    'case_number',
    'opened_on',
    'closed_on',
    'status',
    'title',
])]
#[Hidden(['title'])]
class BkCase extends DomainModel
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
            'student_id' => 'integer',
            'category_id' => 'integer',
            'assigned_employee_id' => 'integer',
            'opened_on' => 'immutable_date:Y-m-d',
            'closed_on' => 'immutable_date:Y-m-d',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<BkCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BkCategory::class, 'category_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<BkNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(BkNote::class, 'case_id', 'id');
    }

    /** @return HasMany<BkFollowup, $this> */
    public function followups(): HasMany
    {
        return $this->hasMany(BkFollowup::class, 'case_id', 'id');
    }

    /** @return HasMany<BkSummary, $this> */
    public function summaries(): HasMany
    {
        return $this->hasMany(BkSummary::class, 'case_id', 'id');
    }

    /** @return HasMany<BkFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(BkFile::class, 'case_id', 'id');
    }
}
