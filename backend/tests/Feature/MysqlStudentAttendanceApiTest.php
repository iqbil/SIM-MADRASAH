<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\DB;
use Tests\MysqlAuthTestCase;

/**
 * Uji API absensi siswa harian (SRS v1.3 FR-07, DEC-07, BR-08/09, AC-10):
 * pencatat = guru/wali kelas pada kelas penugasannya sebelum 23.59; unik
 * siswa-tanggal; tanpa baris = belum dicatat (bukan alpa); koreksi disetujui
 * petugas berbeda; lewat 7 hari hanya pemegang mandat khusus.
 */
class MysqlStudentAttendanceApiTest extends MysqlAuthTestCase
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

    /** @return array{0: array<string, string>, 1: array<string, mixed>} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $this->grant($user, 'operator');

        return [['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']], $user->id];
    }

    /** Wali kelas dengan homeroom aktif pada periode; guru pembanding tanpa penugasan. @return array{0: array<string, string>, 1: int, 2: array<int, int>} */
    private function homeroomTeacher(array $operatorHeaders, int $periodId): array
    {
        $person = Person::factory()->create();
        $user = User::factory()->for($person)->create();
        $this->grant($user, 'wali_kelas');
        $employeeId = DB::table('employees')->insertGetId(['person_id' => $person->id, 'employee_number' => 'EMP-SA-'.uniqid(), 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $this->postJson('/api/v1/academic/homeroom-assignments', [
            'employee_id' => $employeeId, 'class_period_id' => $periodId,
            'effective_from' => today('Asia/Jakarta')->toDateString(), 'reason' => 'Penunjukan wali kelas uji absensi siswa',
        ], $operatorHeaders)->assertCreated();

        return [['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']], $employeeId, [$user->id]];
    }

    /**
     * Kelas + 2 siswa anggota; satu siswa mulai keanggotaan besok (pengujian
     * validitas keanggotaan AC-10).
     *
     * @return array{0: array<string, mixed>, 1: array<int, int>}
     */
    private function seedClass(array $operatorHeaders): array
    {
        $year = $this->postJson('/api/v1/academic/years', ['code' => '2026/2027', 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'reason' => 'Tahun ajaran uji absensi siswa'], $operatorHeaders)->assertCreated()->json('data');
        $semester = $this->postJson('/api/v1/academic/semesters', ['academic_year_id' => $year['id'], 'number' => 1, 'name' => 'Semester 1', 'start_date' => '2026-07-01', 'end_date' => '2026-12-19', 'reason' => 'Semester uji absensi siswa'], $operatorHeaders)->assertCreated()->json('data');
        $class = $this->postJson('/api/v1/academic/classes', ['code' => '7A', 'name' => 'Kelas 7A', 'grade' => 7, 'reason' => 'Rombel uji absensi siswa'], $operatorHeaders)->assertCreated()->json('data');
        $period = $this->postJson('/api/v1/academic/class-periods', ['class_id' => $class['id'], 'academic_year_id' => $year['id'], 'display_name' => 'VII-A 2026/2027', 'reason' => 'Periode uji absensi siswa'], $operatorHeaders)->assertCreated()->json('data');
        $today = today('Asia/Jakarta')->toDateString();
        $studentIds = [];
        foreach (['Siswa Satu', 'Siswa Dua'] as $name) {
            $student = Student::factory()->create();
            DB::table('persons')->where('id', $student->person_id)->update(['full_name' => $name]);
            $this->postJson('/api/v1/academic/enrollments', ['student_id' => $student->id, 'class_period_id' => $period['id'], 'effective_from' => $today, 'entry_reason' => 'admission', 'reason' => 'Keanggotaan uji absensi siswa'], $operatorHeaders)->assertCreated();
            $studentIds[] = $student->id;
        }

        return [$period, $studentIds];
    }

    public function test_access_and_duty_scope(): void
    {
        [$operatorHeaders] = $this->operator();
        [$period] = $this->seedClass($operatorHeaders);

        $plain = User::factory()->create();
        $plainHeaders = ['Authorization' => 'Bearer '.$this->loginUser($plain)['access_token']];
        $this->getJson('/api/v1/student-attendance/classes', $plainHeaders)->assertForbidden();

        // Operator tidak memegang token students.attendance (menu hanya guru/wali).
        $this->getJson('/api/v1/student-attendance/classes', $operatorHeaders)->assertForbidden();

        [$waliHeaders] = $this->homeroomTeacher($operatorHeaders, $period['id']);
        $classes = $this->getJson('/api/v1/student-attendance/classes', $waliHeaders)->assertOk()->json('data');
        $this->assertCount(1, $classes);
        $this->assertSame($period['id'], $classes[0]['id']);
        $this->assertSame(2, $classes[0]['roster']);
        $this->assertSame(0, $classes[0]['recorded']);

        // Guru tanpa penugasan kelas manapun tidak melihat kelas.
        $person = Person::factory()->create();
        $guru = User::factory()->for($person)->create();
        $this->grant($guru, 'guru');
        $guruHeaders = ['Authorization' => 'Bearer '.$this->loginUser($guru)['access_token']];
        $this->getJson('/api/v1/student-attendance/classes', $guruHeaders)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/student-attendance/roster?class_period_id='.$period['id'].'&date='.today('Asia/Jakarta')->toDateString(), $guruHeaders)->assertNotFound();
    }

    public function test_record_unique_membership_and_not_recorded_semantics(): void
    {
        [$operatorHeaders] = $this->operator();
        [$period, [$satu, $dua]] = $this->seedClass($operatorHeaders);
        [$waliHeaders] = $this->homeroomTeacher($operatorHeaders, $period['id']);
        $today = today('Asia/Jakarta')->toDateString();

        $roster = $this->getJson('/api/v1/student-attendance/roster?class_period_id='.$period['id'].'&date='.$today, $waliHeaders)->assertOk()->json('data');
        $this->assertSame('daily', $roster['policy']['mode']);
        $this->assertCount(2, $roster['rows']);
        $this->assertNull($roster['rows'][0]['attendance']);

        // Siswa di luar keanggotaan kelas ditolak; tanggal lampau ditolak bagi wali.
        $this->postJson('/api/v1/student-attendance/record', [
            'class_period_id' => $period['id'], 'date' => $today,
            'entries' => [['student_id' => $satu, 'status_code' => 'hadir'], ['student_id' => 999999, 'status_code' => 'hadir']],
            'reason' => 'Pengisian dengan siswa asing harus ditolak',
        ], $waliHeaders)->assertStatus(422);
        $this->postJson('/api/v1/student-attendance/record', [
            'class_period_id' => $period['id'], 'date' => today('Asia/Jakarta')->subDay()->toDateString(),
            'entries' => [['student_id' => $satu, 'status_code' => 'hadir']],
            'reason' => 'Pengisian hari lampau oleh wali ditolak',
        ], $waliHeaders)->assertStatus(422);

        $this->postJson('/api/v1/student-attendance/record', [
            'class_period_id' => $period['id'], 'date' => $today,
            'entries' => [['student_id' => $satu, 'status_code' => 'hadir'], ['student_id' => $dua, 'status_code' => 'sakit', 'notes' => 'Demam']],
            'reason' => 'Pengisian absensi harian kelas VII-A hari ini',
        ], $waliHeaders)->assertOk()->assertJsonPath('data.created', 2);

        // Pencatatan ganda ditolak dengan menyebut nama (AC-10: unique siswa-tanggal).
        $duplicate = $this->postJson('/api/v1/student-attendance/record', [
            'class_period_id' => $period['id'], 'date' => $today,
            'entries' => [['student_id' => $satu, 'status_code' => 'izin']],
            'reason' => 'Percobaan pencatatan ulang harus ditolak',
        ], $waliHeaders)->assertStatus(422);
        $this->assertStringContainsString('Siswa Satu', $duplicate->json('message'));

        // Rekap: yang belum diisi tampil sebagai belum_dicatat, bukan alpa.
        $recap = $this->getJson('/api/v1/student-attendance/recap?class_period_id='.$period['id']."&date_from={$today}&date_to={$today}", $waliHeaders)->assertOk()->json('data');
        $this->assertSame(1, $recap['by_date'][0]['hadir']);
        $this->assertSame(1, $recap['by_date'][0]['sakit']);
        $this->assertSame(0, $recap['by_date'][0]['alpa']);
        $this->assertSame(0, $recap['by_date'][0]['belum_dicatat']);
    }

    public function test_correction_requires_different_mandated_approver(): void
    {
        [$operatorHeaders] = $this->operator();
        [$period, [$satu]] = $this->seedClass($operatorHeaders);
        [$waliHeaders, , [$waliUserId]] = $this->homeroomTeacher($operatorHeaders, $period['id']);
        $today = today('Asia/Jakarta')->toDateString();
        $this->postJson('/api/v1/student-attendance/record', [
            'class_period_id' => $period['id'], 'date' => $today,
            'entries' => [['student_id' => $satu, 'status_code' => 'hadir']],
            'reason' => 'Pengisian awal untuk uji koreksi',
        ], $waliHeaders)->assertOk();
        $rowId = DB::table('student_daily_attendances')->where('student_id', $satu)->value('id');

        $correction = $this->postJson('/api/v1/student-attendance/corrections', [
            'student_daily_attendance_id' => $rowId,
            'after_values' => ['status_code' => 'izin', 'notes' => 'Ternyata izin acara keluarga'],
            'reason' => 'Koreksi status setelah orang tua menghubungi',
            'expected_lock_version' => 0,
        ], $waliHeaders)->assertCreated()->json('data');

        // Wali bukan pemegang mandat: tidak dapat menyetujui (apalagi miliknya sendiri).
        $this->postJson('/api/v1/student-attendance/corrections/'.$correction['id'].'/decision', [
            'decision' => 'approve', 'reason' => 'Penyetujui tanpa mandat ditolak',
        ], $waliHeaders)->assertForbidden();

        $permissionId = DB::table('permissions')->where('code', 'attendance.correct')->value('id');
        DB::table('authorization_mandates')->insert([
            'user_id' => $waliUserId, 'permission_id' => $permissionId, 'scope_type' => 'institution',
            'effective_from' => now('UTC')->subSecond(), 'granted_by' => $waliUserId,
            'reason' => 'Mandat uji koreksi siswa', 'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);
        $waliMandated = ['Authorization' => 'Bearer '.$this->loginUser(User::query()->find($waliUserId))['access_token']];
        $this->postJson('/api/v1/student-attendance/corrections/'.$correction['id'].'/decision', [
            'decision' => 'approve', 'reason' => 'Pemohon sama dengan penyetuju ditolak',
        ], $waliMandated)->assertStatus(422);

        [$officerHeaders, $officerId] = $this->operator();
        DB::table('authorization_mandates')->insert([
            'user_id' => $officerId, 'permission_id' => $permissionId, 'scope_type' => 'institution',
            'effective_from' => now('UTC')->subSecond(), 'granted_by' => $waliUserId,
            'reason' => 'Mandat penyetuju koreksi siswa', 'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);
        $officerMandated = ['Authorization' => 'Bearer '.$this->loginUser(User::query()->find($officerId))['access_token']];
        $this->postJson('/api/v1/student-attendance/corrections/'.$correction['id'].'/decision', [
            'decision' => 'approve', 'reason' => 'Disetujui petugas lain setelah verifikasi',
        ], $officerMandated)->assertOk()->assertJsonPath('data.status', 'applied');
        $this->assertSame('izin', DB::table('student_daily_attendances')
            ->join('attendance_statuses', 'attendance_statuses.id', '=', 'student_daily_attendances.attendance_status_id')
            ->where('student_daily_attendances.id', $rowId)->value('attendance_statuses.code'));
        $this->assertSame(1, DB::table('student_daily_attendances')->where('id', $rowId)->value('lock_version'));

        // Lewat batas 7 hari: hanya pemegang mandat yang boleh mengajukan (DEC-07).
        $old = today('Asia/Jakarta')->subDays(10)->toDateString();
        DB::table('student_enrollments')->where('student_id', $satu)->update(['effective_from' => $old]);
        $oldRowId = DB::table('student_daily_attendances')->insertGetId([
            'student_id' => $satu, 'attendance_date' => $old, 'class_period_id' => $period['id'],
            'enrollment_id' => DB::table('student_enrollments')->where('student_id', $satu)->value('id'),
            'attendance_status_id' => DB::table('attendance_statuses')->where(['domain' => 'student', 'code' => 'hadir'])->value('id'),
            'policy_id' => DB::table('attendance_policies')->value('id'), 'source_type' => 'manual',
            'recorded_by' => $waliUserId, 'recorded_at' => now('UTC'), 'lock_version' => 0,
            'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);
        $freshWali = ['Authorization' => 'Bearer '.$this->loginUser(User::query()->find($waliUserId))['access_token']];
        // Wali kini memanggul mandat -> boleh mengajukan koreksi terlambat.
        $late = $this->postJson('/api/v1/student-attendance/corrections', [
            'student_daily_attendance_id' => $oldRowId,
            'after_values' => ['status_code' => 'alpa'], 'reason' => 'Koreksi terlambat oleh pemegang mandat khusus',
            'expected_lock_version' => 0,
        ], $freshWali)->assertCreated()->json('data');
        $this->postJson('/api/v1/student-attendance/corrections/'.$late['id'].'/decision', [
            'decision' => 'approve', 'reason' => 'Keputusan koreksi terlambat',
        ], $officerMandated)->assertOk();

        // Wali (pemegang mandat) melihat daftar; status terisi benar.
        $list = $this->getJson('/api/v1/student-attendance/corrections?status=applied', $freshWali)->assertOk()->json('data');
        $this->assertGreaterThanOrEqual(2, count($list['rows']['data']));
    }
}
