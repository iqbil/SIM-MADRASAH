<?php

namespace Database\Factories;

use App\Models\AttendanceStatus;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeWorkDay;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeAttendance> */
class EmployeeAttendanceFactory extends Factory
{
    protected $model = EmployeeAttendance::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'work_day_id' => EmployeeWorkDay::factory(),
            'attendance_status_id' => fn (): int => AttendanceStatus::query()->firstOrCreate(['domain' => 'employee', 'code' => 'hadir'], ['name' => 'Hadir', 'is_active' => true])->id,
            'check_in_at' => '2026-10-20 00:01:00',
            'entry_mode' => 'self',
            'recorded_by' => function (array $attributes): int {
                $person = EmployeeWorkDay::query()->findOrFail($attributes['work_day_id'])->employee->person;

                return $person->user?->id ?? User::factory()->create(['person_id' => $person->id])->id;
            },
            'recorded_at' => '2026-10-20 00:01:00',
        ];
    }
}
