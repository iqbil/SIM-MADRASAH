<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassPeriod;
use App\Models\Employee;
use App\Models\EmployeeCategory;
use App\Models\Guardian;
use App\Models\HomeroomAssignment;
use App\Models\Person;
use App\Models\Position;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatus;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Tests\MysqlAuthTestCase;

class MysqlMasterDataTest extends MysqlAuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AccountAccessSeeder::class, ReferenceDataSeeder::class]);
    }

    private function actor(string $role): User
    {
        $user = User::factory()->create();
        UserRole::query()->forceCreate(['user_id' => $user->id, 'role_id' => Role::query()->where('code', $role)->firstOrFail()->id,
            'effective_from' => now('UTC')->subMinute(), 'granted_by' => $user->id]);

        return $user;
    }

    private function headersFor(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']];
    }

    public function test_only_active_super_admin_and_operator_can_manage_master(): void
    {
        $this->getJson('/api/v1/master/students')->assertUnauthorized();
        foreach (['super_admin', 'operator'] as $role) {
            $headers = $this->headersFor($this->actor($role));
            foreach (['options', 'institution', 'students', 'guardians', 'employees', 'positions', 'employee-categories', 'subjects', 'student-statuses', 'attendance-statuses', 'imports'] as $path) {
                $this->getJson('/api/v1/master/'.$path, $headers)->assertOk();
            }
        }
        foreach (['kepala_madrasah', 'guru', 'wali_kelas', 'guru_bk', 'tenaga_kependidikan', 'bendahara', 'petugas_tu', 'orang_tua_wali'] as $role) {
            $headers = $this->headersFor($this->actor($role));
            $this->getJson('/api/v1/master/students', $headers)->assertForbidden();
            $this->postJson('/api/v1/master/subjects', [], $headers)->assertForbidden();
            $this->getJson('/api/v1/master/imports', $headers)->assertForbidden();
        }
        $admin = $this->actor('operator');
        $headers = $this->headersFor($admin);
        $admin->roleAssignments()->update(['revoked_by' => $admin->id, 'revoke_reason' => 'Mandat telah berakhir']);
        $this->getJson('/api/v1/master/students', $headers)->assertForbidden();
    }

    public function test_master_crud_archives_instead_of_deleting_and_rejects_stale_writes(): void
    {
        $headers = $this->headersFor($this->actor('operator'));
        $payloads = [
            'students' => ['internal_code' => 'S-001', 'nisn' => '1234567890', 'person' => ['full_name' => 'Siswa Pertama', 'birth_date' => '2012-01-01']],
            'guardians' => ['internal_code' => 'W-001', 'person' => ['full_name' => 'Wali Pertama']],
            'employees' => ['employee_number' => 'P-001', 'joined_on' => '2026-07-01', 'person' => ['full_name' => 'Pegawai Pertama']],
            'positions' => ['code' => 'KAMAD', 'name' => 'Kepala Madrasah'],
            'employee-categories' => ['code' => 'guru', 'name' => 'Guru'],
            'subjects' => ['code' => 'MTK', 'name' => 'Matematika'],
            'student-statuses' => ['code' => 'cuti', 'name' => 'Cuti', 'is_terminal' => false],
            'attendance-statuses' => ['domain' => 'employee', 'code' => 'pelatihan', 'name' => 'Pelatihan'],
        ];
        foreach ($payloads as $resource => $body) {
            $body['reason'] = 'Pencatatan data master awal';
            $count = User::query()->count();
            $data = $this->postJson('/api/v1/master/'.$resource, $body, $headers)->assertCreated()->json('data');
            $this->assertSame($count, User::query()->count());
            $path = '/api/v1/master/'.$resource.'/'.$data['id'];
            $this->getJson($path, $headers)->assertOk()->assertJsonPath('data.id', $data['id']);
            $update = [...$body, 'expected_updated_at' => $data['updated_at']];
            if (isset($data['person'])) {
                $update['expected_person_updated_at'] = $data['person']['updated_at'];
                $update['person']['full_name'] .= ' Diperbarui';
            } else {
                $update['name'] .= ' Diperbarui';
            }
            $data = $this->putJson($path, $update, $headers)->assertOk()->json('data');
            $this->putJson($path, $update, $headers)->assertConflict();
            $this->postJson($path.'/status', ['is_active' => false, 'reason' => 'Data dinonaktifkan sementara', 'expected_updated_at' => $data['updated_at']], $headers)->assertOk();
            $this->getJson($path, $headers)->assertOk();
            $this->deleteJson($path, [], $headers)->assertMethodNotAllowed();
        }
        $this->assertDatabaseHas('student_transitions', ['type' => 'admission']);
        $this->assertGreaterThanOrEqual(24, AuditLog::query()->where('event', 'like', 'master.%')->count());
    }

    public function test_identity_reuse_duplicate_detection_and_mass_assignment_boundaries(): void
    {
        $headers = $this->headersFor($this->actor('operator'));
        $person = Person::factory()->create(['full_name' => 'Orang Yang Sama', 'birth_date' => '1980-01-01']);
        $body = ['internal_code' => 'W-02', 'person' => ['full_name' => $person->full_name, 'birth_date' => '1980-01-01'], 'reason' => 'Registrasi identitas wali'];
        $this->postJson('/api/v1/master/guardians', $body, $headers)->assertUnprocessable()->assertJsonPath('duplicate_candidates.0.id', $person->id);
        $this->postJson('/api/v1/master/guardians', ['internal_code' => 'W-02', 'person_id' => $person->id, 'reason' => $body['reason']], $headers)->assertCreated()->assertJsonPath('data.person_id', $person->id);
        $this->postJson('/api/v1/master/employees', ['employee_number' => 'P-02', 'person_id' => $person->id, 'reason' => $body['reason']], $headers)->assertCreated();
        $this->postJson('/api/v1/master/students', ['internal_code' => 'S-02', 'person' => ['full_name' => 'Siswa', 'is_active' => false], 'status_id' => 999, 'reason' => $body['reason']], $headers)->assertUnprocessable();
        $this->getJson('/api/v1/master/people?q=Orang', $headers)->assertOk()->assertJsonPath('data.0.id', $person->id);
        $this->getJson('/api/v1/master/users', $headers)->assertNotFound();
    }

    public function test_teacher_and_homeroom_reads_are_scoped_by_current_class_and_hide_personal_fields(): void
    {
        $teacher = $this->actor('wali_kelas');
        $employee = Employee::factory()->create(['person_id' => $teacher->person_id]);
        $year = AcademicYear::factory()->create(['start_date' => today('Asia/Jakarta')->subMonths(3), 'end_date' => today('Asia/Jakarta')->addMonths(9)]);
        $period = ClassPeriod::factory()->create(['academic_year_id' => $year->id]);
        HomeroomAssignment::query()->forceCreate(['employee_id' => $employee->id, 'class_period_id' => $period->id,
            'effective_from' => today('Asia/Jakarta')->subMonth(), 'created_by' => $teacher->id]);
        $mine = Student::factory()->create();
        $other = Student::factory()->create();
        StudentEnrollment::factory()->create(['student_id' => $mine->id, 'class_period_id' => $period->id]);
        $headers = $this->headersFor($teacher);
        $this->getJson('/api/v1/master/students', $headers)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonMissingPath('data.0.person.birth_date')->assertJsonMissingPath('data.0.person.address')->assertJsonMissingPath('data.0.nisn');
        $this->getJson('/api/v1/master/students/'.$other->id, $headers)->assertNotFound();
        $this->getJson('/api/v1/master/students?q='.$other->person->full_name, $headers)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/master/guardians', $headers)->assertForbidden();
        $this->postJson('/api/v1/master/students/'.$mine->id.'/status', [], $headers)->assertForbidden();
        HomeroomAssignment::query()->where('employee_id', $employee->id)->update(['effective_to' => today('Asia/Jakarta')]);
        $this->getJson('/api/v1/master/students', $headers)->assertForbidden();
    }

    public function test_institution_is_singleton_and_does_not_change_application_clock(): void
    {
        $headers = $this->headersFor($this->actor('super_admin'));
        $body = ['name' => 'MTs Al Karimiyyah', 'timezone' => 'Asia/Jakarta', 'reason' => 'Pembaruan identitas madrasah'];
        $data = $this->putJson('/api/v1/master/institution', $body, $headers)->assertOk()->json('data');
        $this->putJson('/api/v1/master/institution', [...$body, 'name' => 'MTs Diperbarui', 'expected_updated_at' => $data['updated_at']], $headers)->assertOk();
        $this->assertDatabaseCount('institutions', 1);
        $this->assertSame('UTC', config('app.timezone'));
        $this->putJson('/api/v1/master/institution', $body, $headers)->assertUnprocessable();
    }

    public function test_guardian_links_require_identity_verification_and_keep_revocation_history(): void
    {
        $headers = $this->headersFor($this->actor('operator'));
        $student = Student::factory()->create();
        $guardian = Guardian::factory()->create();
        $path = '/api/v1/master/students/'.$student->id.'/guardian-links';
        $body = ['guardian_id' => $guardian->id, 'relationship' => 'father', 'is_primary' => true,
            'effective_from' => today('Asia/Jakarta')->toDateString(), 'reason' => 'Hubungan wali diperiksa operator', 'expected_updated_at' => $student->updated_at->toISOString()];
        $data = $this->postJson($path, [...$body, 'verification_status' => 'verified'], $headers)->assertCreated()->assertJsonPath('data.verification_status', 'pending')->json();
        $this->getJson($path, $headers)->assertOk()->assertJsonCount(1, 'data');
        $action = ['expected_updated_at' => $data['meta']['owner_updated_at'], 'expected_link_updated_at' => $data['data']['updated_at'], 'reason' => 'Identitas wali telah diperiksa'];
        $this->postJson($path.'/'.$data['data']['id'].'/verify', $action, $headers)->assertUnprocessable();
        $verified = $this->postJson($path.'/'.$data['data']['id'].'/verify', [...$action, 'identity_confirmation' => true], $headers)->assertOk()->assertJsonPath('data.verification_status', 'verified')->json();
        $this->postJson($path, [...$body, 'expected_updated_at' => $verified['meta']['owner_updated_at']], $headers)->assertUnprocessable();
        $this->postJson($path.'/'.$data['data']['id'].'/revoke', ['expected_updated_at' => $verified['meta']['owner_updated_at'],
            'expected_link_updated_at' => $verified['data']['updated_at'], 'reason' => 'Hubungan wali dicabut secara resmi'], $headers)->assertOk()->assertJsonPath('data.verification_status', 'revoked');
        $this->assertDatabaseCount('student_guardians', 1);
    }

    public function test_employee_category_and_position_history_reject_overlaps_without_granting_login_roles(): void
    {
        $headers = $this->headersFor($this->actor('operator'));
        $employee = Employee::factory()->create();
        $category = EmployeeCategory::query()->create(['code' => 'guru', 'name' => 'Guru']);
        $position = Position::query()->create(['code' => 'kepala', 'name' => 'Kepala Madrasah']);
        $roles = UserRole::query()->count();
        foreach (['categories' => ['category_id' => $category->id], 'positions' => ['position_id' => $position->id]] as $relation => $target) {
            $path = '/api/v1/master/employees/'.$employee->id.'/'.$relation;
            $body = [...$target, 'effective_from' => today('Asia/Jakarta')->toDateString(), 'reason' => 'Penetapan penugasan pegawai resmi', 'expected_updated_at' => $employee->fresh()->updated_at->toISOString()];
            $data = $this->postJson($path, $body, $headers)->assertCreated()->json();
            $this->postJson($path, [...$body, 'expected_updated_at' => $data['meta']['owner_updated_at']], $headers)->assertUnprocessable();
            $this->getJson($path, $headers)->assertOk()->assertJsonCount(1, 'data');
            $this->postJson($path.'/'.$data['data']['id'].'/end', ['effective_to' => today('Asia/Jakarta')->addMonth()->toDateString(),
                'expected_updated_at' => $data['meta']['owner_updated_at'], 'expected_link_updated_at' => $data['data']['updated_at'], 'reason' => 'Penugasan akan berakhir bulan depan'], $headers)->assertOk();
        }
        $this->assertSame($roles, UserRole::query()->count());
        $this->assertDatabaseCount('employee_category_assignments', 1);
        $this->assertDatabaseCount('employee_positions', 1);
    }

    public function test_teacher_subject_assignments_expiry_and_shared_person_conflicts_are_enforced(): void
    {
        $teacher = $this->actor('guru');
        $employee = Employee::factory()->create(['person_id' => $teacher->person_id]);
        $year = AcademicYear::factory()->create(['start_date' => today('Asia/Jakarta')->subMonth(), 'end_date' => today('Asia/Jakarta')->addMonths(11)]);
        $semester = Semester::factory()->create(['academic_year_id' => $year->id, 'start_date' => $year->start_date, 'end_date' => today('Asia/Jakarta')->addMonths(5)]);
        $period = ClassPeriod::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::query()->create(['code' => 'MTK', 'name' => 'Matematika']);
        $assignment = TeachingAssignment::query()->forceCreate(['employee_id' => $employee->id, 'subject_id' => $subject->id, 'class_period_id' => $period->id,
            'semester_id' => $semester->id, 'effective_from' => $year->start_date, 'is_active' => true, 'created_by' => $teacher->id]);
        $student = Student::factory()->create();
        StudentEnrollment::factory()->create(['student_id' => $student->id, 'class_period_id' => $period->id]);
        $headers = $this->headersFor($teacher);
        $this->getJson('/api/v1/master/students/'.$student->id, $headers)->assertOk();
        $assignment->update(['is_active' => false]);
        $this->getJson('/api/v1/master/students', $headers)->assertForbidden();
        $assignment->update(['is_active' => true]);
        $semester->update(['end_date' => today('Asia/Jakarta')]);
        $this->getJson('/api/v1/master/students', $headers)->assertForbidden();

        $adminHeaders = $this->headersFor($this->actor('operator'));
        $snapshot = $this->getJson('/api/v1/master/employees/'.$employee->id, $adminHeaders)->assertOk()->json('data');
        $teacher->person->update(['phone' => '0800000000']);
        $this->putJson('/api/v1/master/employees/'.$employee->id, ['employee_number' => $employee->employee_number, 'person' => ['full_name' => 'Nama baru'],
            'expected_updated_at' => $snapshot['updated_at'], 'expected_person_updated_at' => $snapshot['person']['updated_at'], 'reason' => 'Koreksi identitas pegawai'], $adminHeaders)->assertConflict();
    }

    public function test_distinct_people_with_same_name_and_birth_date_require_audited_confirmation(): void
    {
        $headers = $this->headersFor($this->actor('operator'));
        Person::factory()->create(['full_name' => 'Nama Sama', 'birth_date' => '1980-01-01']);
        $body = ['internal_code' => 'W-UNIK', 'person' => ['full_name' => 'Nama Sama', 'birth_date' => '1980-01-01'],
            'reason' => 'Registrasi wali dengan nama sama', 'duplicate_confirmation' => 'Dokumen identitas telah diperiksa, ini orang yang berbeda.'];
        $this->postJson('/api/v1/master/guardians', $body, $headers)->assertCreated();
        $this->assertSame(2, Person::query()->where('full_name', 'Nama Sama')->count());
        $this->assertSame($body['duplicate_confirmation'], AuditLog::query()->where('event', 'master.created')->firstOrFail()->after_values['duplicate_confirmation']);
    }

    public function test_reference_semantics_and_inactive_roles_cannot_be_bypassed(): void
    {
        $operator = $this->actor('operator');
        $headers = $this->headersFor($operator);
        $status = StudentStatus::query()->where('code', 'active')->firstOrFail();
        $path = '/api/v1/master/student-statuses/'.$status->id;
        $body = ['code' => 'active', 'name' => 'Aktif belajar', 'is_terminal' => 0, 'reason' => 'Pembaruan label status aktif', 'expected_updated_at' => $status->updated_at->toISOString()];
        $data = $this->putJson($path, $body, $headers)->assertOk()->json('data');
        $this->putJson($path, [...$body, 'code' => 'graduated-new', 'expected_updated_at' => $data['updated_at']], $headers)->assertUnprocessable();
        $this->postJson($path.'/status', ['is_active' => false, 'reason' => 'Menonaktifkan status bawaan', 'expected_updated_at' => $data['updated_at']], $headers)->assertUnprocessable();
        Role::query()->where('code', 'operator')->update(['is_active' => false]);
        $this->getJson('/api/v1/master/subjects', $headers)->assertForbidden();
        $this->postJson('/api/v1/master/subjects', ['code' => 'X', 'name' => 'Nama', 'reason' => 'Pencatatan referensi baru'], $headers)->assertForbidden();
    }
}
