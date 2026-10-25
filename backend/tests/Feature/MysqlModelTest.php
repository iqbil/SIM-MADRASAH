<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceStatus;
use App\Models\ClassPeriod;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceCapture;
use App\Models\Guardian;
use App\Models\HonorComponent;
use App\Models\HonorPeriod;
use App\Models\Payroll;
use App\Models\PayrollLine;
use App\Models\PayrollVersion;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\UserRole;
use Carbon\CarbonImmutable;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class MysqlModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Run with phpunit.mysql.xml on an isolated MySQL test database.');
        }

        $this->assertSame('testing', app()->environment());
        $this->assertMatchesRegularExpression('/_tests?$/', DB::connection()->getDatabaseName());
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->app !== null && DB::getDriverName() === 'mysql' && DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_all_domain_tables_have_models_with_casts_and_relations_matching_the_real_schema(): void
    {
        $models = [];
        foreach (glob(app_path('Models/*.php')) as $path) {
            $class = 'App\\Models\\'.basename($path, '.php');
            if ((new ReflectionClass($class))->isAbstract()) {
                continue;
            }
            $model = new $class;
            $this->assertInstanceOf(Model::class, $model);
            $this->assertArrayNotHasKey($model->getTable(), $models);
            $models[$model->getTable()] = $model;
        }

        $domainTables = array_diff(Schema::getTableListing(DB::connection()->getDatabaseName(), false), ['migrations', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'personal_access_tokens']);
        $this->assertEqualsCanonicalizing(array_values($domainTables), array_keys($models));
        $this->assertCount(128, $models);
        $columnsByTable = [];
        foreach (array_keys($models) as $table) {
            $columnsByTable[$table] = array_column(Schema::getColumns($table), null, 'name');
        }

        foreach ($models as $table => $model) {
            $columns = $columnsByTable[$table];
            $this->assertSame('Y-m-d H:i:s.u', $model->getDateFormat(), $table);
            foreach ($model->getFillable() as $field) {
                $this->assertArrayHasKey($field, $columns, $table.'.'.$field);
                $this->assertNull($columns[$field]['generation'] ?? null, $table.'.'.$field);
            }
            foreach ($columns as $field => $column) {
                $type = $column['type_name'];
                $cast = $model->getCasts()[$field] ?? null;
                if ($type === 'decimal') {
                    preg_match('/decimal\(\d+,(\d+)\)/', $column['type'], $matches);
                    $this->assertSame('decimal:'.$matches[1], $cast, $table.'.'.$field);
                } elseif ($type === 'json') {
                    $this->assertSame('array', $cast, $table.'.'.$field);
                } elseif ($type === 'date') {
                    $this->assertSame('immutable_date:Y-m-d', $cast, $table.'.'.$field);
                } elseif ($type === 'datetime') {
                    $this->assertSame('immutable_datetime', $cast, $table.'.'.$field);
                } elseif ($type === 'time') {
                    $this->assertNull($cast, $table.'.'.$field.' must remain a local time string');
                }
            }

            $belongsTo = [];
            foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $model::class) {
                    continue;
                }
                $returnType = (string) $method->getReturnType();
                if (! in_array($returnType, [BelongsTo::class, HasOne::class, HasMany::class], true)) {
                    continue;
                }
                $relation = $model->{$method->getName()}();
                $relatedTable = $relation->getRelated()->getTable();
                $this->assertArrayHasKey($relatedTable, $models, $table.'.'.$method->getName());
                if ($relation instanceof BelongsTo) {
                    $foreign = $relation->getForeignKeyName();
                    $this->assertArrayHasKey($foreign, $columns, $table.'.'.$method->getName());
                    $this->assertArrayHasKey($relation->getOwnerKeyName(), $columnsByTable[$relatedTable]);
                    $belongsTo[$foreign.':'.$relatedTable] = true;
                } else {
                    $this->assertArrayHasKey($relation->getForeignKeyName(), $columnsByTable[$relatedTable]);
                    $this->assertArrayHasKey($relation->getLocalKeyName(), $columns);
                }
            }
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $idPosition = array_search('id', $foreignKey['foreign_columns'], true);
                if ($idPosition === false) {
                    // The publication asset's composite key is traversed through contentRevision and photoPublicFile.
                    $this->assertSame('publication_assets', $foreignKey['foreign_table']);
                    $this->assertTrue($model->isRelation('contentRevision'));
                    $this->assertTrue($model->isRelation('photoPublicFile'));

                    continue;
                }
                $key = $foreignKey['columns'][$idPosition].':'.$foreignKey['foreign_table'];
                $this->assertArrayHasKey($key, $belongsTo, $table.' missing relation '.$key);
            }
        }
    }

    public function test_one_person_can_have_employee_and_guardian_profiles_and_one_account(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->for($person)->create();
        $employee = Employee::factory()->for($person)->create();
        $guardian = Guardian::factory()->for($person)->create();
        $operator = User::factory()->create();
        $role = Role::query()->create(['code' => 'teacher', 'name' => 'Guru']);
        $permission = Permission::query()->create(['code' => 'test.read', 'module' => 'test', 'action' => 'read']);
        $grant = new UserRole(['effective_from' => '2026-07-01 00:00:00']);
        $grant->user()->associate($user);
        $grant->role()->associate($role);
        $grant->grantor()->associate($operator);
        $grant->save();
        $assignment = new RolePermission(['role_id' => $role->id, 'permission_id' => $permission->id]);
        $assignment->creator()->associate($operator);
        $assignment->save();
        $loaded = User::query()->with(['person.employee', 'person.guardian', 'roleAssignments.role.permissionAssignments.permission'])->findOrFail($user->id);

        $this->assertTrue($loaded->person->employee->is($employee));
        $this->assertTrue($loaded->person->guardian->is($guardian));
        $this->assertSame($permission->id, $loaded->roleAssignments->sole()->role->permissionAssignments->sole()->permission->id);
        $this->assertSame(1, User::query()->where('person_id', $person->id)->count());
    }

    public function test_academic_relations_preserve_class_snapshots_and_leading_zero_identifiers(): void
    {
        $year = AcademicYear::factory()->create();
        $semester = Semester::factory()->for($year)->create();
        $class = SchoolClass::factory()->create();
        $period = ClassPeriod::factory()->create(['class_id' => $class->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['nis' => '000123', 'nisn' => '0012345678']);
        $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'class_period_id' => $period->id]);
        $class->update(['name' => 'VIII A', 'grade' => 8]);
        $loaded = Student::query()->with('enrollments.classPeriod.academicYear.semesters')->findOrFail($student->id);

        $this->assertSame('000123', $loaded->nis);
        $this->assertSame('0012345678', $loaded->nisn);
        $this->assertTrue($loaded->enrollments->sole()->is($enrollment));
        $this->assertSame(7, $loaded->enrollments->sole()->classPeriod->grade_snapshot);
        $this->assertSame('VII A', $loaded->enrollments->sole()->classPeriod->display_name);
        $this->assertTrue($loaded->enrollments->sole()->classPeriod->academicYear->semesters->sole()->is($semester));
        $this->assertSame($year->start_date->toDateString(), $enrollment->effective_from->toDateString());
    }

    public function test_attendance_factory_builds_consistent_photo_gps_graph_and_preserves_microseconds(): void
    {
        $capture = EmployeeAttendanceCapture::factory()->create();
        $capture->recorded_at = CarbonImmutable::parse('2026-10-20 00:01:00.123456', 'UTC');
        $capture->save();
        $attendance = EmployeeAttendance::query()->with(['captures.photoFile', 'workDay.employee.person.user', 'workDay.policy'])->findOrFail($capture->employee_attendance_id);
        $loaded = $attendance->captures->sole();

        $this->assertSame($attendance->workDay->employee->person->user->id, $loaded->recorded_by);
        $this->assertSame($loaded->recorded_by, $loaded->photoFile->uploaded_by);
        $this->assertSame($attendance->workDay->policy->id, $loaded->policy_snapshot['policy_id']);
        $this->assertSame('-6.2000000', $loaded->latitude);
        $this->assertSame('106.8166667', $loaded->longitude);
        $this->assertSame('15.00', $loaded->accuracy_meters);
        $this->assertSame(102400, $loaded->photoFile->size_bytes);
        $this->assertTrue($attendance->workDay->policy->requires_photo);
        $this->assertTrue($attendance->workDay->policy->requires_gps);
        $this->assertSame('2026-10-20 00:01:00.123456', DB::table('employee_attendance_captures')->where('id', $loaded->id)->value('recorded_at'));
        $this->assertArrayNotHasKey('latitude', $loaded->toArray());
        $this->assertArrayNotHasKey('photo_file', $loaded->toArray());
    }

    public function test_payroll_version_pointer_lines_and_decimal_values_round_trip(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create();
        $period = new HonorPeriod(['code' => '2026-10', 'name' => 'Oktober', 'start_date' => '2026-10-01', 'end_date' => '2026-11-01', 'status' => 'open']);
        $period->creator()->associate($user);
        $period->save();
        $payroll = new Payroll(['employee_id' => $employee->id, 'honor_period_id' => $period->id, 'payroll_type' => 'regular']);
        $payroll->creator()->associate($user);
        $payroll->save();
        $version = new PayrollVersion(['version_number' => 1, 'policy_snapshot' => ['jp_minutes' => 40], 'employee_snapshot' => ['name' => 'Fixture'], 'source_fingerprint' => str_repeat('a', 64), 'teaching_total' => '25000.00', 'allowance_total' => '0.00', 'deduction_total' => '0.00', 'adjustment_total' => '0.00', 'net_total' => '25000.00']);
        $version->calculator()->associate($user);
        $version->calculated_at = now('UTC');
        $payroll->versions()->save($version);
        $component = HonorComponent::query()->create(['code' => 'TEACHING', 'name' => 'Mengajar', 'component_type' => 'teaching', 'calculation_method' => 'per_jp']);
        $line = new PayrollLine(['component_id' => $component->id, 'line_key' => 'teaching', 'component_code_snapshot' => 'TEACHING', 'description_snapshot' => 'Mengajar', 'component_type_snapshot' => 'teaching', 'quantity' => '1.000', 'rate_snapshot' => '25000.00', 'amount' => '25000.00', 'direction' => 'credit', 'formula_snapshot' => ['jp' => '1.000']]);
        $version->lines()->save($line);
        $payroll->activeVersion()->associate($version);
        $payroll->save();
        $loaded = Payroll::query()->with('activeVersion.lines.component')->findOrFail($payroll->id);

        $this->assertTrue($loaded->activeVersion->is($version));
        $this->assertSame('25000.00', $loaded->activeVersion->net_total);
        $this->assertSame('1.000', $loaded->activeVersion->lines->sole()->quantity);
        $this->assertSame('25000.00', $loaded->activeVersion->lines->sole()->amount);
        $this->assertSame(['jp_minutes' => 40], $loaded->activeVersion->policy_snapshot);
        $this->assertArrayNotHasKey('policy_snapshot', $loaded->activeVersion->toArray());
    }

    public function test_user_factory_supports_accounts_without_email(): void
    {
        $user = User::factory()->withoutEmail()->create();

        $this->assertNotNull($user->person_id);
        $this->assertNotEmpty($user->username);
        $this->assertNull($user->email);
        $this->assertSame($user->person->full_name, $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertSame('active', $user->status);
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_reference_seeder_is_idempotent_and_does_not_create_accounts_or_honor_rates(): void
    {
        $users = User::query()->count();
        $rates = DB::table('honor_rates')->count();
        $this->seed(ReferenceDataSeeder::class);
        AttendanceStatus::query()->where('domain', 'employee')->where('code', 'hadir')->update(['name' => 'Hadir dikonfigurasi']);
        $this->seed(ReferenceDataSeeder::class);

        $this->assertDatabaseCount('attendance_statuses', 9);
        $this->assertDatabaseCount('student_statuses', 4);
        $this->assertDatabaseCount('question_types', 2);
        $this->assertSame('Hadir dikonfigurasi', AttendanceStatus::query()->where('domain', 'employee')->where('code', 'hadir')->value('name'));
        $this->assertSame($users, User::query()->count());
        $this->assertSame($rates, DB::table('honor_rates')->count());
    }
}
