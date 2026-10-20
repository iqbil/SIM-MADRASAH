<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('employee_positions')]
#[Fillable([
    'employee_id',
    'position_id',
    'appointment_number',
    'appointment_file_id',
    'effective_from',
    'effective_to',
])]
class EmployeePosition extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_id' => 'integer',
            'position_id' => 'integer',
            'appointment_file_id' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function appointmentFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'appointment_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<EmployeeAdditionalDuty, $this> */
    public function employeeAdditionalDuties(): HasMany
    {
        return $this->hasMany(EmployeeAdditionalDuty::class, 'position_assignment_id', 'id');
    }
}
