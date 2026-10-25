<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassPeriod;
use App\Models\HomeroomAssignment;
use App\Models\Person;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentTransition;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\DB;
use Tests\MysqlAuthTestCase;

class MysqlAcademicApiTest extends MysqlAuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferenceDataSeeder::class);
        $this->seed(AccountAccessSeeder::class);
    }

    private function grant(User $user, string $role): void
    {
        UserRole::query()->forceCreate([
            'user_id' => $user->id,
            'role_id' => DB::table('roles')->where('code', $role)->value('id'),
            'effective_from' => now('UTC')->subSecond(),
            'granted_by' => $user->id,
        ]);
    }

    private function operator(): array
    {
        $user = User::factory()->create();
        $this->grant($user, 'operator');

        return [$user, ['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']]];
    }

    /** @return array{0: array<int, mixed>, 1: array<string, mixed>} */
    private function seedYearSemester(array $headers): array
    {
        $year = $this->postJson('/api/v1/academic/years', [
            'code' => '2026/2027', 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'reason' => 'Tahun ajaran baru madrasah',
        ], $headers)->assertCreated()->json('data');
        $semester = $this->postJson('/api/v1/academic/semesters', [
            'academic_year_id' => $year['id'], 'number' => 1, 'name' => 'Semester 1', 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'reason' => 'Semester ganjil tahun 2026/2027',
        ], $headers)->assertCreated()->json('data');

        return [$year, $semester];
    }

    /** @return array{0: array<int, mixed>, 1: array<string, mixed>, 2: array<int, mixed>} */
    private function seedClassAndPeriod(array $headers, array $year): array
    {
        $class = $this->postJson('/api/v1/academic/classes', [
            'code' => '7A', 'name' => 'Kelas 7A', 'grade' => 7, 'reason' => 'Rombongan belajar baru tingkat 7',
        ], $headers)->assertCreated()->json('data');
        $period = $this->postJson('/api/v1/academic/class-periods', [
            'class_id' => $class['id'], 'academic_year_id' => $year['id'], 'display_name' => 'VII-A 2026/2027', 'capacity' => 32, 'reason' => 'Pembukaan rombel tingkat 7 tahun 2026/2027',
        ], $headers)->assertCreated()->json('data');

        return [$class, $period];
    }

    public function test_access_follows_roles_and_permissions(): void
    {
        $plain = User::factory()->create();
        $plainHeaders = ['Authorization' => 'Bearer '.$this->loginUser($plain)['access_token']];
        foreach (['context', 'years', 'classes', 'class-periods', 'enrollments', 'calendar', 'teaching-assignments'] as $path) {
            $this->getJson('/api/v1/academic/'.$path, $plainHeaders)->assertForbidden();
        }
        $this->getJson('/api/v1/academic/years')->assertUnauthorized();

        $guru = User::factory()->create();
        $this->grant($guru, 'guru');
        $guruHeaders = ['Authorization' => 'Bearer '.$this->loginUser($guru)['access_token']];
        $this->getJson('/api/v1/academic/context', $guruHeaders)->assertOk();
        $this->postJson('/api/v1/academic/years', ['code' => 'X', 'name' => 'X', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'reason' => 'Penambahan tahun ajaran'], $guruHeaders)->assertForbidden();

        $operator = User::factory()->create();
        $this->grant($operator, 'operator');
        $operatorHeaders = ['Authorization' => 'Bearer '.$this->loginUser($operator)['access_token']];
        $me = $this->getJson('/api/v1/auth/me', $operatorHeaders)->assertOk()->json('data');
        $this->assertContains('academic.years.manage', $me['permissions']);
        $guruMe = $this->getJson('/api/v1/auth/me', $guruHeaders)->assertOk()->json('data');
        $this->assertContains('academic.years.view', $guruMe['permissions']);
        $this->assertNotContains('academic.years.manage', $guruMe['permissions']);
    }

    public function test_year_semester_and_active_context_rules(): void
    {
        [, $headers] = $this->operator();
        [$year, $semester] = $this->seedYearSemester($headers);

        $this->postJson('/api/v1/academic/semesters', [
            'academic_year_id' => $year['id'], 'number' => 2, 'name' => 'Semester 2', 'start_date' => '2026-12-15', 'end_date' => '2027-06-30', 'reason' => 'Semester genap bertumpuk dengan semester 1',
        ], $headers)->assertStatus(422);

        $second = $this->postJson('/api/v1/academic/semesters', [
            'academic_year_id' => $year['id'], 'number' => 2, 'name' => 'Semester 2', 'start_date' => '2027-01-01', 'end_date' => '2027-06-30', 'reason' => 'Semester genap tahun 2026/2027',
        ], $headers)->assertCreated()->json('data');

        $year = $this->putJson('/api/v1/academic/years/'.$year['id'], [
            'status' => 'open', 'expected_updated_at' => $year['updated_at'], 'reason' => 'Pembukaan tahun ajaran',
        ], $headers)->assertOk()->json('data');
        $semester = $this->putJson('/api/v1/academic/semesters/'.$semester['id'], [
            'status' => 'open', 'expected_updated_at' => $semester['updated_at'], 'reason' => 'Pembukaan semester ganjil',
        ], $headers)->assertOk()->json('data');
        $context = $this->putJson('/api/v1/academic/context', [
            'active_academic_year_id' => $year['id'], 'active_semester_id' => $semester['id'], 'expected_lock_version' => 0, 'reason' => 'Aktivasi tahun ajaran berjalan',
        ], $headers)->assertOk()->json('data');
        $this->assertSame($year['id'], $context['year']['id']);

        $this->putJson('/api/v1/academic/context', [
            'active_academic_year_id' => $year['id'], 'active_semester_id' => $second['id'], 'expected_lock_version' => 0, 'reason' => 'Perubahan dengan versi kedaluwarsa',
        ], $headers)->assertStatus(409);

        $this->putJson('/api/v1/academic/years/'.$year['id'], [
            'status' => 'closed', 'expected_updated_at' => $year['updated_at'], 'reason' => 'Menutup tahun ajaran yang masih aktif',
        ], $headers)->assertStatus(422);
    }

    public function test_class_periods_snapshot_grade_and_unique_year(): void
    {
        [, $headers] = $this->operator();
        [$year] = $this->seedYearSemester($headers);
        [$class, $period] = $this->seedClassAndPeriod($headers, $year);
        $this->assertSame(7, $period['grade_snapshot']);

        $this->postJson('/api/v1/academic/class-periods', [
            'class_id' => $class['id'], 'academic_year_id' => $year['id'], 'display_name' => 'Duplikat rombel', 'reason' => 'Rombel yang sama pada tahun sama',
        ], $headers)->assertStatus(409);

        SchoolClass::query()->findOrFail($class['id'])->forceFill(['grade' => 8])->save();
        $this->putJson('/api/v1/academic/class-periods/'.$period['id'], [
            'display_name' => 'VII-A 2026/2027 (diperbarui)', 'expected_updated_at' => $period['updated_at'], 'reason' => 'Perbaikan nama tampilan rombel',
        ], $headers)->assertOk()->assertJsonPath('data.grade_snapshot', 7);
    }

    public function test_enrollment_overlap_move_and_stale_update(): void
    {
        [, $headers] = $this->operator();
        [$year] = $this->seedYearSemester($headers);
        [, $period] = $this->seedClassAndPeriod($headers, $year);
        $otherClass = $this->postJson('/api/v1/academic/classes', [
            'code' => '7B', 'name' => 'Kelas 7B', 'grade' => 7, 'reason' => 'Rombongan belajar kedua tingkat 7',
        ], $headers)->assertCreated()->json('data');
        $otherPeriod = $this->postJson('/api/v1/academic/class-periods', [
            'class_id' => $otherClass['id'], 'academic_year_id' => $year['id'], 'display_name' => 'VII-B 2026/2027', 'reason' => 'Pembukaan rombel VII-B',
        ], $headers)->assertCreated()->json('data');

        $student = Student::factory()->create();
        $enrollment = $this->postJson('/api/v1/academic/enrollments', [
            'student_id' => $student->id, 'class_period_id' => $period['id'], 'effective_from' => '2026-07-15', 'entry_reason' => 'admission', 'reason' => 'Penerimaan siswa baru ke kelas VII-A',
        ], $headers)->assertCreated()->json('data');

        $this->postJson('/api/v1/academic/enrollments', [
            'student_id' => $student->id, 'class_period_id' => $otherPeriod['id'], 'effective_from' => '2026-08-01', 'entry_reason' => 'transfer', 'reason' => 'Keanggotaan ganda harus ditolak',
        ], $headers)->assertStatus(422);
        $this->postJson('/api/v1/academic/enrollments', [
            'student_id' => Student::factory()->create()->id, 'class_period_id' => $period['id'], 'effective_from' => '2027-07-15', 'entry_reason' => 'admission', 'reason' => 'Tanggal di luar tahun ajaran rombel',
        ], $headers)->assertStatus(422);

        $moved = $this->postJson('/api/v1/academic/enrollments/move', [
            'student_id' => $student->id, 'to_class_period_id' => $otherPeriod['id'], 'effective_date' => '2026-11-01', 'entry_reason' => 'transfer', 'reason' => 'Perpindahan kelas tengah semester',
        ], $headers)->assertCreated()->json('data');
        $old = StudentEnrollment::query()->find($enrollment['id']);
        $this->assertSame('2026-11-01', $old->effective_to->toDateString());
        $this->assertSame($otherPeriod['id'], $moved['class_period_id']);
        $this->assertSame(1, StudentTransition::query()->where('student_id', $student->id)->where('type', 'class_transfer')->count());

        $this->putJson('/api/v1/academic/enrollments/'.$moved['id'], [
            'effective_to' => '2026-12-31', 'exit_reason' => 'graduation', 'expected_updated_at' => '2000-01-01 00:00:00', 'reason' => 'Pembaruan dengan versi kedaluwarsa',
        ], $headers)->assertStatus(409);
    }

    public function test_transition_preview_and_confirm_keep_history(): void
    {
        [, $headers] = $this->operator();
        [$year] = $this->seedYearSemester($headers);
        [, $period] = $this->seedClassAndPeriod($headers, $year);
        $student = Student::factory()->create();
        $this->postJson('/api/v1/academic/enrollments', [
            'student_id' => $student->id, 'class_period_id' => $period['id'], 'effective_from' => '2026-07-15', 'entry_reason' => 'admission', 'reason' => 'Penerimaan siswa untuk uji mutasi',
        ], $headers)->assertCreated();

        $preview = $this->postJson('/api/v1/academic/transitions/preview', [
            'student_ids' => [$student->id], 'type' => 'graduation', 'effective_date' => '2027-06-01',
        ], $headers)->assertOk()->json('data');
        $this->assertTrue($preview['ready']);
        $this->assertSame('graduated', $preview['to_status']);

        $orphan = Student::factory()->create();
        $blocked = $this->postJson('/api/v1/academic/transitions/preview', [
            'student_ids' => [$orphan->id], 'type' => 'graduation', 'effective_date' => '2027-06-01',
        ], $headers)->assertOk()->json('data');
        $this->assertFalse($blocked['ready']);
        $this->assertNotNull($blocked['students'][0]['problem']);

        $confirm = $this->postJson('/api/v1/academic/transitions/confirm', [
            'student_ids' => [$student->id], 'type' => 'graduation', 'effective_date' => '2027-06-01', 'reason' => 'Kelulusan siswa tahun ajaran 2026/2027',
        ], $headers)->assertOk()->json('data');
        $this->assertSame('graduated', $confirm['to_status']);
        $fresh = Student::query()->find($student->id);
        $this->assertSame('graduated', $fresh->status->code);
        $this->assertNotNull($fresh->archived_at);
        $this->assertSame(0, StudentEnrollment::query()->where('student_id', $student->id)->whereNull('effective_to')->count());
        $this->assertSame(1, StudentTransition::query()->where('student_id', $student->id)->where('type', 'graduation')->count());

        $this->postJson('/api/v1/academic/transitions/confirm', [
            'student_ids' => [$student->id], 'type' => 'graduation', 'effective_date' => '2027-06-02', 'reason' => 'Mutasi kedua kali harus ditolak',
        ], $headers)->assertStatus(422);
    }

    public function test_teaching_and_homeroom_assignment_rules(): void
    {
        [, $headers] = $this->operator();
        [$year, $semester] = $this->seedYearSemester($headers);
        [, $period] = $this->seedClassAndPeriod($headers, $year);
        $otherYear = $this->postJson('/api/v1/academic/years', [
            'code' => '2027/2028', 'name' => '2027/2028', 'start_date' => '2027-07-01', 'end_date' => '2028-06-30', 'reason' => 'Tahun ajaran berikutnya',
        ], $headers)->assertCreated()->json('data');
        $otherSemester = $this->postJson('/api/v1/academic/semesters', [
            'academic_year_id' => $otherYear['id'], 'number' => 1, 'name' => 'Semester 1', 'start_date' => '2027-07-01', 'end_date' => '2027-12-31', 'reason' => 'Semester tahun lain',
        ], $headers)->assertCreated()->json('data');

        $employeeId = DB::table('employees')->insertGetId(['person_id' => Person::factory()->create()->id, 'employee_number' => 'EMP-'.uniqid(), 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $subjectId = DB::table('subjects')->insertGetId(['code' => 'MTK', 'name' => 'Matematika', 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);

        $this->postJson('/api/v1/academic/teaching-assignments', [
            'employee_id' => $employeeId, 'subject_id' => $subjectId, 'class_period_id' => $period['id'], 'semester_id' => $otherSemester['id'],
            'effective_from' => '2026-07-15', 'reason' => 'Semester beda tahun harus ditolak',
        ], $headers)->assertStatus(422);

        $assignment = $this->postJson('/api/v1/academic/teaching-assignments', [
            'employee_id' => $employeeId, 'subject_id' => $subjectId, 'class_period_id' => $period['id'], 'semester_id' => $semester['id'],
            'effective_from' => '2026-07-15', 'planned_weekly_jp' => 4, 'reason' => 'Penugasan mengajar matematika VII-A',
        ], $headers)->assertCreated()->json('data');

        $this->postJson('/api/v1/academic/teaching-assignments', [
            'employee_id' => $employeeId, 'subject_id' => $subjectId, 'class_period_id' => $period['id'], 'semester_id' => $semester['id'],
            'effective_from' => '2026-09-01', 'reason' => 'Penugasan tumpang tindih harus ditolak',
        ], $headers)->assertStatus(422);

        $this->putJson('/api/v1/academic/teaching-assignments/'.$assignment['id'], [
            'planned_weekly_jp' => 3, 'effective_from' => '2026-07-15', 'is_active' => false, 'expected_updated_at' => $assignment['updated_at'], 'reason' => 'Penonaktifan penugasan mengajar',
        ], $headers)->assertOk()->assertJsonPath('data.is_active', false);

        $firstHomeroom = $this->postJson('/api/v1/academic/homeroom-assignments', [
            'employee_id' => $employeeId, 'class_period_id' => $period['id'], 'effective_from' => '2026-07-15', 'reason' => 'Penunjukan wali kelas pertama',
        ], $headers)->assertCreated()->json('data');
        $this->postJson('/api/v1/academic/homeroom-assignments', [
            'employee_id' => $employeeId, 'class_period_id' => $period['id'], 'effective_from' => '2027-01-05', 'reason' => 'Pergantian wali kelas semester genap',
        ], $headers)->assertCreated();
        $this->assertSame('2027-01-05', HomeroomAssignment::query()->find($firstHomeroom['id'])->effective_to->toDateString());
    }

    public function test_detail_endpoints_return_records_and_respect_teacher_scope(): void
    {
        [, $headers] = $this->operator();
        [$year, $semester] = $this->seedYearSemester($headers);
        [$class, $period] = $this->seedClassAndPeriod($headers, $year);

        $this->getJson('/api/v1/academic/years/'.$year['id'], $headers)->assertOk()
            ->assertJsonPath('data.code', $year['code']);
        $this->getJson('/api/v1/academic/semesters/'.$semester['id'], $headers)->assertOk()
            ->assertJsonPath('data.number', 1);
        $this->getJson('/api/v1/academic/classes/'.$class['id'], $headers)->assertOk()
            ->assertJsonPath('data.grade', 7);
        $this->getJson('/api/v1/academic/class-periods/'.$period['id'], $headers)->assertOk()
            ->assertJsonPath('data.grade_snapshot', 7);

        $event = $this->postJson('/api/v1/academic/calendar', [
            'academic_year_id' => $year['id'], 'title' => 'Libur semester', 'event_type' => 'holiday',
            'start_date' => '2026-12-24', 'end_date' => '2027-01-02', 'blocks_teaching' => true, 'reason' => 'Libur akhir semester ganjil',
        ], $headers)->assertCreated()->json('data');
        $this->getJson('/api/v1/academic/calendar/'.$event['id'], $headers)->assertOk()
            ->assertJsonPath('data.title', 'Libur semester');

        $person = Person::factory()->create();
        $teacher = User::factory()->for($person)->create();
        $this->grant($teacher, 'guru');
        $employeeId = DB::table('employees')->insertGetId(['person_id' => $person->id, 'employee_number' => 'EMP-'.uniqid(), 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $subjectId = DB::table('subjects')->insertGetId(['code' => 'PKN', 'name' => 'PKN', 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $assignment = $this->postJson('/api/v1/academic/teaching-assignments', [
            'employee_id' => $employeeId, 'subject_id' => $subjectId, 'class_period_id' => $period['id'], 'semester_id' => $semester['id'],
            'effective_from' => '2026-07-15', 'reason' => 'Penugasan untuk uji detail',
        ], $headers)->assertCreated()->json('data');
        $enrollment = $this->postJson('/api/v1/academic/enrollments', [
            'student_id' => Student::factory()->create()->id, 'class_period_id' => $period['id'], 'effective_from' => '2026-07-15', 'entry_reason' => 'admission', 'reason' => 'Keanggotaan untuk uji detail',
        ], $headers)->assertCreated()->json('data');

        $teacherHeaders = ['Authorization' => 'Bearer '.$this->loginUser($teacher)['access_token']];
        $this->getJson('/api/v1/academic/teaching-assignments/'.$assignment['id'], $teacherHeaders)->assertOk();
        $this->getJson('/api/v1/academic/enrollments/'.$enrollment['id'], $teacherHeaders)->assertOk();
        $this->getJson('/api/v1/academic/years/'.$year['id'], $teacherHeaders)->assertOk();

        $otherClass = SchoolClass::query()->create(['code' => '7C', 'name' => 'Kelas 7C', 'grade' => 7, 'is_active' => true]);
        $otherPeriod = ClassPeriod::query()->create(['class_id' => $otherClass->id, 'academic_year_id' => AcademicYear::query()->sole()->id, 'display_name' => 'VII-C', 'grade_snapshot' => 7, 'status' => 'planned']);
        $outside = new StudentEnrollment(['student_id' => Student::factory()->create()->id, 'class_period_id' => $otherPeriod->id, 'effective_from' => '2026-07-15', 'entry_reason' => 'admission']);
        $outside->forceFill(['created_by' => User::query()->firstOrFail()->id])->save();
        $this->getJson('/api/v1/academic/enrollments/'.$outside->id, $teacherHeaders)->assertNotFound();
        $plainHeaders = ['Authorization' => 'Bearer '.$this->loginUser(User::factory()->create())['access_token']];
        $this->getJson('/api/v1/academic/years/'.$year['id'], $plainHeaders)->assertForbidden();
    }

    public function test_teacher_sees_only_their_own_assignments_and_classes(): void
    {
        [, $headers] = $this->operator();
        [$year, $semester] = $this->seedYearSemester($headers);
        [, $period] = $this->seedClassAndPeriod($headers, $year);
        $otherClass = $this->postJson('/api/v1/academic/classes', [
            'code' => '7B', 'name' => 'Kelas 7B', 'grade' => 7, 'reason' => 'Rombongan belajar pembanding',
        ], $headers)->assertCreated()->json('data');
        $otherPeriod = $this->postJson('/api/v1/academic/class-periods', [
            'class_id' => $otherClass['id'], 'academic_year_id' => $year['id'], 'display_name' => 'VII-B 2026/2027', 'reason' => 'Rombel pembanding',
        ], $headers)->assertCreated()->json('data');

        $person = Person::factory()->create();
        $teacher = User::factory()->for($person)->create();
        $this->grant($teacher, 'guru');
        $employeeId = DB::table('employees')->insertGetId(['person_id' => $person->id, 'employee_number' => 'EMP-'.uniqid(), 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $subjectId = DB::table('subjects')->insertGetId(['code' => 'BIO', 'name' => 'Biologi', 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $this->postJson('/api/v1/academic/teaching-assignments', [
            'employee_id' => $employeeId, 'subject_id' => $subjectId, 'class_period_id' => $period['id'], 'semester_id' => $semester['id'],
            'effective_from' => '2026-07-15', 'reason' => 'Guru mengajar di kelas VII-A saja',
        ], $headers)->assertCreated();

        $scoped = Student::factory()->create();
        $elsewhere = Student::factory()->create();
        $this->postJson('/api/v1/academic/enrollments', [
            'student_id' => $scoped->id, 'class_period_id' => $period['id'], 'effective_from' => '2026-07-15', 'entry_reason' => 'admission', 'reason' => 'Siswa di kelas yang diajar guru',
        ], $headers)->assertCreated();
        $this->postJson('/api/v1/academic/enrollments', [
            'student_id' => $elsewhere->id, 'class_period_id' => $otherPeriod['id'], 'effective_from' => '2026-07-15', 'entry_reason' => 'admission', 'reason' => 'Siswa di kelas lain',
        ], $headers)->assertCreated();

        $teacherHeaders = ['Authorization' => 'Bearer '.$this->loginUser($teacher)['access_token']];
        $assignments = $this->getJson('/api/v1/academic/teaching-assignments', $teacherHeaders)->assertOk()->json('data');
        $this->assertCount(1, $assignments);
        $this->assertSame($period['id'], $assignments[0]['class_period_id']);

        $enrollments = $this->getJson('/api/v1/academic/enrollments?active_only=1&per_page=100', $teacherHeaders)->assertOk()->json('data');
        $this->assertSame([$scoped->id], array_column($enrollments, 'student_id'));

        $periods = $this->getJson('/api/v1/academic/class-periods?per_page=100', $teacherHeaders)->assertOk()->json('data');
        $this->assertSame([$period['id']], array_column($periods, 'id'));
        $this->getJson('/api/v1/academic/class-periods/'.$otherPeriod['id'], $teacherHeaders)->assertNotFound();
        $classes = $this->getJson('/api/v1/academic/classes?per_page=100', $teacherHeaders)->assertOk()->json('data');
        $this->assertSame([$period['class_id']], array_column($classes, 'id'));

        $this->postJson('/api/v1/academic/homeroom-assignments', [
            'employee_id' => $employeeId, 'class_period_id' => $period['id'], 'effective_from' => '2026-07-20', 'reason' => 'Guru tidak boleh mengelola penugasan',
        ], $teacherHeaders)->assertForbidden();
    }
}
