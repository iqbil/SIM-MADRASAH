<?php

namespace App\Services;

use App\Models\AttendanceStatus;
use App\Models\Employee;
use App\Models\EmployeeCategory;
use App\Models\Guardian;
use App\Models\Institution;
use App\Models\Position;
use App\Models\Student;
use App\Models\StudentStatus;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class MasterDataCatalog
{
    public const MODELS = ['institution' => Institution::class, 'students' => Student::class, 'guardians' => Guardian::class,
        'employees' => Employee::class, 'positions' => Position::class, 'employee-categories' => EmployeeCategory::class,
        'subjects' => Subject::class, 'student-statuses' => StudentStatus::class, 'attendance-statuses' => AttendanceStatus::class];

    public const PERSON_FIELDS = ['full_name', 'sex', 'birth_place', 'birth_date', 'address', 'phone', 'contact_email'];

    public static function model(string $resource): Model
    {
        abort_unless(isset(self::MODELS[$resource]), 404);

        return new (self::MODELS[$resource]);
    }

    public static function hasPerson(string $resource): bool
    {
        return in_array($resource, ['students', 'guardians', 'employees'], true);
    }

    /** @return array<string, mixed> */
    public static function rules(string $resource, ?Model $model = null): array
    {
        $table = self::model($resource)->getTable();
        $unique = fn (string $field): Unique => Rule::unique($table, $field)->ignore($model?->getKey());
        $nullable = fn (int $length): array => ['nullable', 'string', 'max:'.$length];
        if ($resource === 'institution') {
            return ['name' => ['required', 'string', 'max:200'], 'npsn' => $nullable(20), 'nsm' => $nullable(30), 'address' => $nullable(5000),
                'phone' => $nullable(30), 'email' => ['nullable', 'email', 'max:254'], 'website' => ['nullable', 'url:http,https', 'max:255'],
                'timezone' => ['required', Rule::in(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'])]];
        }
        if (self::hasPerson($resource)) {
            $rules = [];
            if (! $model) {
                $rules['person_id'] = ['nullable', 'integer', Rule::exists('persons', 'id')->where('is_active', true), $unique('person_id')];
            }
            $rules['person'] = [$model ? 'sometimes' : 'required_without:person_id', 'array:'.implode(',', self::PERSON_FIELDS)];
            foreach (self::PERSON_FIELDS as $field) {
                $rules['person.'.$field] = match ($field) {
                    'full_name' => ['required_with:person', 'string', 'max:200'],
                    'sex' => ['nullable', Rule::in(['L', 'P'])],
                    'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.today('Asia/Jakarta')->toDateString()],
                    'contact_email' => ['nullable', 'email', 'max:254'],
                    default => $nullable(match ($field) {
                        'birth_place' => 100, 'address' => 5000, default => 30
                    }),
                };
            }
            $code = $resource === 'employees' ? 'employee_number' : 'internal_code';
            $rules[$code] = ['required', 'string', 'max:50', $unique($code)];
            if ($resource === 'students') {
                return [...$rules, 'nis' => [...$nullable(30), $unique('nis')], 'nisn' => ['nullable', 'regex:/^[0-9]{10}$/', $unique('nisn')], 'admitted_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.today('Asia/Jakarta')->toDateString()]];
            }
            if ($resource === 'guardians') {
                return [...$rules, 'occupation' => $nullable(150)];
            }

            return [...$rules, 'nip' => [...$nullable(30), $unique('nip')], 'nuptk' => [...$nullable(30), $unique('nuptk')],
                'joined_on' => ['nullable', 'date_format:Y-m-d'], 'left_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:joined_on']];
        }
        $rules = ['code' => ['required', 'string', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', 'max:'.(in_array($resource, ['employee-categories', 'student-statuses', 'attendance-statuses'], true) ? 30 : 50)],
            'name' => ['required', 'string', 'max:'.(in_array($resource, ['positions', 'subjects'], true) ? 150 : 100)]];
        if ($resource === 'attendance-statuses') {
            $rules['domain'] = ['required', Rule::in(['employee', 'student'])];
        } else {
            $rules['code'][] = $unique('code');
        }

        return [...$rules, ...match ($resource) {
            'positions' => ['description' => $nullable(5000)],
            'subjects' => ['group_name' => $nullable(100)],
            'student-statuses' => ['is_terminal' => ['required', 'boolean']],
            default => [],
        }];
    }
}
