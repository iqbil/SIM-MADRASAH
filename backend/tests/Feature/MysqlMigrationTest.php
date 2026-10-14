<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MysqlMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Run with phpunit.mysql.xml on an isolated MySQL 8.4 test database.');
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

    public function test_two_profiles_cannot_use_the_same_person(): void
    {
        $person = $this->person('Pegawai yang juga wali');
        $this->insert('employees', ['person_id' => $person, 'employee_number' => 'EMP-1']);
        $this->insert('guardians', ['person_id' => $person, 'internal_code' => 'WALI-1']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_employees_person_id');
        $this->insert('employees', ['person_id' => $person, 'employee_number' => 'EMP-2']);
    }

    public function test_accounts_without_email_remain_distinct_and_identity_is_required(): void
    {
        $first = $this->user();
        $second = $this->user();

        $this->assertDatabaseHas('users', ['id' => $first, 'email' => null]);
        $this->assertDatabaseHas('users', ['id' => $second, 'email' => null]);

        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $second)->update(['person_id' => null]);
    }

    public function test_absence_is_unique_per_student_date_and_requires_matching_enrollment(): void
    {
        [$user, $student, $class, $enrollment, $policy, $status] = $this->attendanceContext();
        $values = ['student_id' => $student, 'attendance_date' => '2026-10-14', 'class_period_id' => $class, 'enrollment_id' => $enrollment, 'attendance_status_id' => $status, 'policy_id' => $policy, 'source_type' => 'manual', 'recorded_by' => $user, 'recorded_at' => '2026-10-14 00:00:00'];
        $this->insert('student_daily_attendances', $values);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_student_daily_attendances_student_id_attendance_date');
        $this->insert('student_daily_attendances', $values);
    }

    public function test_attendance_cannot_reference_another_students_enrollment(): void
    {
        [$user, $student, $class, $enrollment, $policy, $status] = $this->attendanceContext();
        $other = $this->insert('students', ['person_id' => $this->person('Siswa lain'), 'internal_code' => 'OTHER', 'status_id' => DB::table('students')->where('id', $student)->value('status_id')]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('fk_student_daily_attendances_enrollment_id_student_');
        $this->insert('student_daily_attendances', ['student_id' => $other, 'attendance_date' => '2026-10-14', 'class_period_id' => $class, 'enrollment_id' => $enrollment, 'attendance_status_id' => $status, 'policy_id' => $policy, 'source_type' => 'manual', 'recorded_by' => $user, 'recorded_at' => '2026-10-14 00:00:00']);
    }

    public function test_active_semester_cannot_belong_to_another_year(): void
    {
        $user = $this->user();
        $year = $this->year($user, '2026/2027');
        $otherYear = $this->year($user, '2027/2028');
        $semester = $this->semester($user, $year);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('fk_academic_contexts_active_academic_year_id_active_');
        $this->insert('academic_contexts', ['singleton_key' => 1, 'active_academic_year_id' => $otherYear, 'active_semester_id' => $semester, 'changed_by' => $user]);
    }

    public function test_payroll_pointer_cannot_reference_another_employees_version(): void
    {
        $user = $this->user();
        $period = $this->period($user);
        $first = $this->payroll($user, $period, 'EMP-A');
        $second = $this->payroll($user, $period, 'EMP-B');
        $version = $this->version($user, $second);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('fk_payrolls_id_active_version_id');
        DB::table('payrolls')->where('id', $first)->update(['active_version_id' => $version]);
    }

    #[DataProvider('invalidPayrolls')]
    public function test_invalid_payroll_totals_and_lock_approvals_are_rejected(array $changes, string $constraint): void
    {
        $maker = $this->user();
        $reviewer = $this->user();
        $approver = $this->user();
        $payroll = $this->payroll($maker, $this->period($maker), 'EMP-A');
        $version = $this->version($maker, $payroll);
        $values = ['status' => 'locked', 'reviewed_by' => $reviewer, 'reviewed_at' => '2026-10-14 01:00:00', 'approved_by' => $approver, 'approved_at' => '2026-10-14 02:00:00', 'locked_at' => '2026-10-14 02:00:00'];
        if (($changes['approved_by'] ?? null) === 'maker') {
            $changes['approved_by'] = $maker;
        }

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage($constraint);
        DB::table('payroll_versions')->where('id', $version)->update(array_replace($values, $changes));
    }

    public static function invalidPayrolls(): array
    {
        return [
            'total differs from components' => [['net_total' => '2600001.00'], 'ck_payroll_versions_net'],
            'lock without approval timestamp' => [['approved_at' => null], 'ck_payroll_versions_lock'],
            'maker cannot approve' => [['approved_by' => 'maker'], 'ck_payroll_versions_separate_duties'],
        ];
    }

    public function test_nonexecuted_lesson_cannot_have_paid_jp(): void
    {
        [$user, $meeting, $employee] = $this->meeting();
        $realization = $this->insert('teaching_realizations', ['meeting_id' => $meeting, 'created_by' => $user]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_realization_revisions_nonexecution');
        $this->insert('realization_revisions', ['realization_id' => $realization, 'revision_number' => 1, 'actual_employee_id' => $employee, 'execution_status' => 'not_held', 'verification_status' => 'not_submitted', 'actual_jp' => '1.000', 'actual_minutes' => 40, 'nonexecution_reason' => 'Libur', 'created_by' => $user]);
    }

    public function test_substitute_history_allows_multiple_revocations_but_one_active_teacher(): void
    {
        [$user, $meeting, $employee] = $this->meeting();
        $values = ['meeting_id' => $meeting, 'substitute_employee_id' => $employee, 'assigned_by' => $user, 'assigned_at' => '2026-10-14 00:00:00', 'reason' => 'Pengganti'];
        $this->insert('substitute_assignments', $values + ['revoked_at' => '2026-10-14 01:00:00']);
        $this->insert('substitute_assignments', $values + ['revoked_at' => '2026-10-14 02:00:00']);
        $this->insert('substitute_assignments', $values);

        $this->assertSame(3, DB::table('substitute_assignments')->where('meeting_id', $meeting)->count());
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_substitute_assignments_meeting_id_active_marker');
        $this->insert('substitute_assignments', $values);
    }

    public function test_summary_attachment_cannot_use_a_note_from_another_case(): void
    {
        [$user, $student] = $this->attendanceContext();
        $category = $this->insert('bk_categories', ['code' => 'PRIVATE', 'name' => 'Internal']);
        $employee = $this->employee('BK');
        $case = $this->insert('bk_cases', ['student_id' => $student, 'category_id' => $category, 'assigned_employee_id' => $employee, 'case_number' => 'CASE-1', 'opened_on' => '2026-10-14', 'status' => 'open', 'title' => 'Internal', 'created_by' => $user]);
        $otherCase = $this->insert('bk_cases', ['student_id' => $student, 'category_id' => $category, 'assigned_employee_id' => $employee, 'case_number' => 'CASE-2', 'opened_on' => '2026-10-14', 'status' => 'open', 'title' => 'Internal', 'created_by' => $user]);
        $note = $this->insert('bk_notes', ['case_id' => $otherCase, 'note_type' => 'counseling', 'occurred_at' => '2026-10-14 00:00:00', 'internal_content' => 'Synthetic private note', 'created_by' => $user]);
        $file = $this->insert('files', ['storage_disk' => 'private', 'storage_path' => 'test/file.pdf', 'original_name' => 'file.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size_bytes' => 1, 'sha256' => str_repeat('a', 64), 'classification' => 'bk_internal', 'scan_status' => 'clean', 'uploaded_by' => $user]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('fk_bk_files_case_id_note_id');
        $this->insert('bk_files', ['case_id' => $case, 'note_id' => $note, 'file_id' => $file, 'visibility' => 'internal', 'created_by' => $user]);
    }

    public function test_duplicate_outgoing_numbers_are_rejected_even_for_different_letters(): void
    {
        $user = $this->user();
        $decision = $this->decision();
        $category = $this->insert('document_categories', ['code' => 'TU', 'name' => 'TU', 'access_class' => 'internal']);
        $sequence = $this->insert('letter_number_sequences', ['scope_key' => 'outgoing:2026', 'scope_type' => 'outgoing_letter', 'year' => 2026, 'prefix' => 'MTs-AK', 'format_template' => 'NNNN/MTs-AK/YYYY', 'next_value' => 2, 'decision_id' => $decision, 'created_by' => $user]);
        $values = ['direction' => 'outgoing', 'status' => 'issued', 'category_id' => $category, 'number_sequence_id' => $sequence, 'sequence_number' => 1, 'formatted_number' => '0001/MTs-AK/2026', 'letter_date' => '2026-10-14', 'sender' => 'TU', 'recipient' => 'Test', 'subject' => 'Test', 'issued_by' => $user, 'issued_at' => '2026-10-14 00:00:00', 'created_by' => $user];
        $this->insert('letters', $values);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_letters_number_sequence_id_sequence_number');
        $this->insert('letters', $values);
    }

    public function test_a_meeting_cannot_be_claimed_by_two_payroll_versions(): void
    {
        [$user, $payroll, $sourceValues] = $this->teachingSource();
        $firstVersion = $this->version($user, $payroll);
        $secondVersion = $this->version($user, $payroll, 2);
        $claimValues = ['meeting_id' => $sourceValues['meeting_id'], 'payroll_id' => $payroll, 'beneficiary_employee_id' => $sourceValues['beneficiary_employee_id'], 'claimed_at' => '2026-10-14 00:00:00', 'claimed_by' => $user];
        $sources = [];
        foreach ([$firstVersion, $secondVersion] as $version) {
            $line = $this->insert('payroll_lines', ['payroll_version_id' => $version, 'component_id' => $sourceValues['component_id'], 'line_key' => 'teaching', 'component_code_snapshot' => 'TEACHING', 'description_snapshot' => 'Mengajar', 'component_type_snapshot' => 'teaching', 'quantity' => '1.000', 'rate_snapshot' => '25000.00', 'amount' => '25000.00', 'direction' => 'credit', 'formula_snapshot' => '{}']);
            $values = $sourceValues;
            unset($values['component_id']);
            $sources[] = $this->insert('payroll_line_sources', $values + ['payroll_line_id' => $line, 'payroll_version_id' => $version]);
        }
        $this->insert('payroll_source_claims', $claimValues + ['payroll_version_id' => $firstVersion, 'payroll_line_source_id' => $sources[0]]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_payroll_source_claims_meeting_id');
        $this->insert('payroll_source_claims', $claimValues + ['payroll_version_id' => $secondVersion, 'payroll_line_source_id' => $sources[1]]);
    }

    public function test_one_payroll_source_cannot_mix_a_meeting_and_additional_duty(): void
    {
        [$user, $payroll, $sourceValues] = $this->teachingSource();
        $allowance = $this->insert('honor_components', ['code' => 'ALLOWANCE', 'name' => 'Tambahan', 'component_type' => 'allowance', 'calculation_method' => 'prorata']);
        $duty = $this->insert('employee_additional_duties', ['employee_id' => $sourceValues['beneficiary_employee_id'], 'component_id' => $allowance, 'description' => 'Tugas tambahan', 'effective_from' => '2026-07-01', 'created_by' => $user]);
        $version = $this->version($user, $payroll);
        $line = $this->insert('payroll_lines', ['payroll_version_id' => $version, 'component_id' => $sourceValues['component_id'], 'line_key' => 'teaching', 'component_code_snapshot' => 'TEACHING', 'description_snapshot' => 'Mengajar', 'component_type_snapshot' => 'teaching', 'quantity' => '1.000', 'rate_snapshot' => '25000.00', 'amount' => '25000.00', 'direction' => 'credit', 'formula_snapshot' => '{}']);
        unset($sourceValues['component_id']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_payroll_line_sources_one_source');
        $this->insert('payroll_line_sources', $sourceValues + ['payroll_line_id' => $line, 'payroll_version_id' => $version, 'additional_duty_id' => $duty]);
    }

    public function test_baseline_policy_cannot_pretend_to_have_business_approval(): void
    {
        [$user] = $this->teachingSource();
        $policy = DB::table('honor_policy_versions')->value('id');

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_honor_policy_versions_approval_metadata');
        DB::table('honor_policy_versions')->where('id', $policy)->update(['approved_by' => $user, 'approved_at' => '2026-10-14 00:00:00']);
    }

    public function test_guardian_relationship_cannot_be_verified_without_actor_and_timestamp(): void
    {
        [$user, $student] = $this->attendanceContext();
        $guardian = $this->insert('guardians', ['person_id' => $this->person('Wali'), 'internal_code' => 'WALI-1']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_student_guardians_verified_metadata');
        $this->insert('student_guardians', ['student_id' => $student, 'guardian_id' => $guardian, 'relationship' => 'legal_guardian', 'verification_status' => 'verified', 'effective_from' => '2026-07-01', 'created_by' => $user]);
    }

    public function test_referenced_people_cannot_be_deleted(): void
    {
        $person = $this->person('Pegawai');
        $this->insert('employees', ['person_id' => $person, 'employee_number' => 'EMP-1']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('fk_employees_person_id');
        DB::table('persons')->where('id', $person)->delete();
    }

    public function test_effective_intervals_cannot_end_on_their_start_date(): void
    {
        $employee = $this->employee('EMP-A');
        $category = $this->insert('employee_categories', ['code' => 'GURU', 'name' => 'Guru']);
        $user = $this->user();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_employee_category_assignments_effective_range');
        $this->insert('employee_category_assignments', ['employee_id' => $employee, 'category_id' => $category, 'effective_from' => '2026-10-14', 'effective_to' => '2026-10-14', 'reason' => 'Kategori awal', 'created_by' => $user]);
    }

    private function insert(string $table, array $values): int
    {
        return DB::table($table)->insertGetId($values + ['created_at' => '2026-10-14 00:00:00.000000', 'updated_at' => '2026-10-14 00:00:00.000000']);
    }

    private function person(string $name): int
    {
        return $this->insert('persons', ['full_name' => $name]);
    }

    private function user(): int
    {
        $person = $this->person('Test user');

        return $this->insert('users', ['person_id' => $person, 'name' => 'Test user', 'username' => 'test_'.$person, 'email' => null, 'password' => 'synthetic-hash', 'status' => 'active']);
    }

    private function employee(string $number): int
    {
        return $this->insert('employees', ['person_id' => $this->person($number), 'employee_number' => $number]);
    }

    private function decision(): int
    {
        return $this->insert('business_decisions', ['code' => 'DEC-TEST', 'title' => 'Test baseline', 'status' => 'baseline', 'decision_text' => 'Synthetic baseline', 'source_reference' => 'Test', 'recorded_at' => '2026-10-14 00:00:00', 'version' => 1, 'effective_from' => '2026-07-01']);
    }

    private function year(int $user, string $code): int
    {
        return $this->insert('academic_years', ['code' => $code, 'name' => $code, 'start_date' => '2026-07-01', 'end_date' => '2027-07-01', 'status' => 'open', 'created_by' => $user]);
    }

    private function semester(int $user, int $year): int
    {
        return $this->insert('semesters', ['academic_year_id' => $year, 'number' => 1, 'name' => 'Ganjil', 'start_date' => '2026-07-01', 'end_date' => '2027-01-01', 'status' => 'open', 'created_by' => $user]);
    }

    private function period(int $user): int
    {
        return $this->insert('honor_periods', ['code' => '2026-10', 'name' => 'Oktober', 'start_date' => '2026-10-01', 'end_date' => '2026-11-01', 'status' => 'open', 'created_by' => $user]);
    }

    private function payroll(int $user, int $period, string $employeeNumber): int
    {
        return $this->insert('payrolls', ['employee_id' => $this->employee($employeeNumber), 'honor_period_id' => $period, 'payroll_type' => 'regular', 'created_by' => $user]);
    }

    private function version(int $user, int $payroll, int $number = 1): int
    {
        return $this->insert('payroll_versions', ['payroll_id' => $payroll, 'version_number' => $number, 'status' => 'draft', 'policy_snapshot' => '{}', 'employee_snapshot' => '{}', 'source_fingerprint' => str_repeat('b', 64), 'teaching_total' => '1800000.00', 'allowance_total' => '800000.00', 'deduction_total' => '0.00', 'adjustment_total' => '0.00', 'net_total' => '2600000.00', 'calculated_by' => $user, 'calculated_at' => '2026-10-14 00:00:00']);
    }

    private function attendanceContext(): array
    {
        $user = $this->user();
        $year = $this->year($user, '2026/2027');
        $semester = $this->semester($user, $year);
        $class = $this->insert('classes', ['code' => '7-A', 'name' => 'VII A', 'grade' => 7]);
        $classPeriod = $this->insert('class_periods', ['class_id' => $class, 'academic_year_id' => $year, 'display_name' => 'VII A', 'grade_snapshot' => 7, 'status' => 'open']);
        $studentStatus = $this->insert('student_statuses', ['code' => 'active', 'name' => 'Aktif']);
        $student = $this->insert('students', ['person_id' => $this->person('Siswa'), 'internal_code' => 'STU-1', 'status_id' => $studentStatus]);
        $enrollment = $this->insert('student_enrollments', ['student_id' => $student, 'class_period_id' => $classPeriod, 'effective_from' => '2026-07-01', 'entry_reason' => 'admission', 'created_by' => $user]);
        $policy = $this->insert('attendance_policies', ['semester_id' => $semester, 'version_number' => 1, 'status' => 'baseline', 'mode' => 'daily', 'input_deadline_time' => '23:59:00', 'correction_deadline_days' => 7, 'requires_correction_approval' => 1, 'allow_late_correction_with_mandate' => 1, 'effective_from' => '2026-07-01', 'decision_id' => $this->decision()]);
        $status = $this->insert('attendance_statuses', ['domain' => 'student', 'code' => 'hadir', 'name' => 'Hadir']);

        return [$user, $student, $classPeriod, $enrollment, $policy, $status, $semester];
    }

    private function teachingSource(): array
    {
        [$user, $meeting, $employee] = $this->meeting();
        $period = $this->period($user);
        $payroll = $this->insert('payrolls', ['employee_id' => $employee, 'honor_period_id' => $period, 'payroll_type' => 'regular', 'created_by' => $user]);
        $category = $this->insert('employee_categories', ['code' => 'GURU', 'name' => 'Guru']);
        $categoryAssignment = $this->insert('employee_category_assignments', ['employee_id' => $employee, 'category_id' => $category, 'effective_from' => '2026-07-01', 'reason' => 'Kategori awal', 'created_by' => $user]);
        $policy = $this->insert('honor_policy_versions', ['code' => 'HONOR', 'version_number' => 1, 'status' => 'baseline', 'source_basis' => 'verified', 'jp_minutes' => 40, 'recognized_quantity_rule' => '{}', 'rate_selection_rule' => '{}', 'rounding_rule' => '{}', 'substitute_rule' => '{}', 'holiday_rule' => '{}', 'allowance_rule' => '{}', 'deduction_rule' => '{}', 'approval_rule' => '{}', 'effective_from' => '2026-07-01', 'decision_id' => DB::table('business_decisions')->value('id'), 'created_by' => $user]);
        $component = $this->insert('honor_components', ['code' => 'TEACHING', 'name' => 'Mengajar', 'component_type' => 'teaching', 'calculation_method' => 'per_jp']);
        $scope = $this->insert('honor_rate_scopes', ['component_id' => $component, 'scope_key' => 'component:'.$component.':global', 'created_by' => $user]);
        $rate = $this->insert('honor_rates', ['rate_scope_id' => $scope, 'amount' => '25000.00', 'unit' => 'jp', 'effective_from' => '2026-07-01', 'policy_version_id' => $policy, 'reason' => 'Tarif uji', 'approved_by' => $user, 'approved_at' => '2026-10-14 00:00:00', 'created_by' => $user]);
        $realization = $this->insert('teaching_realizations', ['meeting_id' => $meeting, 'created_by' => $user]);
        $revision = $this->insert('realization_revisions', ['realization_id' => $realization, 'revision_number' => 1, 'actual_employee_id' => $employee, 'execution_status' => 'held', 'verification_status' => 'approved', 'actual_jp' => '1.000', 'actual_minutes' => 40, 'created_by' => $user]);
        DB::table('teaching_realizations')->where('id', $realization)->update(['current_revision_id' => $revision]);
        $verifier = $this->user();
        $permission = $this->insert('permissions', ['code' => 'realization.verify', 'module' => 'academic', 'action' => 'verify']);
        $mandate = $this->insert('authorization_mandates', ['user_id' => $verifier, 'permission_id' => $permission, 'scope_type' => 'institution', 'effective_from' => '2026-07-01 00:00:00', 'granted_by' => $user, 'reason' => 'Verifikator uji']);
        $verification = $this->insert('realization_verifications', ['realization_revision_id' => $revision, 'action' => 'approve', 'verifier_user_id' => $verifier, 'mandate_id' => $mandate, 'verified_at' => '2026-10-14 00:00:00', 'operation_key' => 'verify-test']);

        return [$user, $payroll, ['component_id' => $component, 'source_key' => 'meeting:'.$meeting, 'meeting_id' => $meeting, 'realization_revision_id' => $revision, 'verification_id' => $verification, 'rate_id' => $rate, 'policy_version_id' => $policy, 'source_date' => '2026-10-14', 'scheduled_employee_id' => $employee, 'actual_employee_id' => $employee, 'beneficiary_employee_id' => $employee, 'category_assignment_id' => $categoryAssignment, 'employee_category_id' => $category, 'quantity_snapshot' => '1.000', 'rate_snapshot' => '25000.00', 'amount_snapshot' => '25000.00', 'source_snapshot' => '{}']];
    }

    private function meeting(): array
    {
        [$user, , $class, , , , $semester] = $this->attendanceContext();
        $employee = $this->employee('GURU');
        $subject = $this->insert('subjects', ['code' => 'MAT', 'name' => 'Matematika']);
        $assignment = $this->insert('teaching_assignments', ['employee_id' => $employee, 'subject_id' => $subject, 'class_period_id' => $class, 'semester_id' => $semester, 'effective_from' => '2026-07-01', 'created_by' => $user]);
        $timetable = $this->insert('timetables', ['semester_id' => $semester, 'code' => 'MAIN', 'name' => 'Jadwal', 'created_by' => $user]);
        $version = $this->insert('timetable_versions', ['timetable_id' => $timetable, 'version_number' => 1, 'effective_from' => '2026-07-01', 'status' => 'draft', 'created_by' => $user]);
        $entry = $this->insert('schedule_entries', ['timetable_id' => $timetable, 'entry_key' => '00000000-0000-0000-0000-000000000001', 'created_by' => $user]);
        $entryVersion = $this->insert('schedule_entry_versions', ['schedule_entry_id' => $entry, 'timetable_version_id' => $version, 'teaching_assignment_id' => $assignment, 'day_of_week' => 3, 'start_time_snapshot' => '07:00:00', 'end_time_snapshot' => '07:40:00', 'planned_jp' => '1.000', 'planned_minutes' => 40, 'effective_from' => '2026-07-01']);
        $meeting = $this->insert('meetings', ['schedule_entry_id' => $entry, 'schedule_entry_version_id' => $entryVersion, 'meeting_date' => '2026-10-14', 'occurrence_number' => 1, 'semester_id' => $semester, 'class_period_id' => $class, 'subject_id' => $subject, 'scheduled_employee_id' => $employee, 'start_time_snapshot' => '07:00:00', 'end_time_snapshot' => '07:40:00', 'planned_jp_snapshot' => '1.000', 'planned_minutes_snapshot' => 40, 'created_by' => $user]);

        return [$user, $meeting, $employee];
    }
}
