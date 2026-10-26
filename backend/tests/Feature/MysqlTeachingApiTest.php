<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\DB;
use Tests\MysqlAuthTestCase;

/**
 * Uji API realisasi mengajar (SRS v1.3 FR-06, BR-04/05, AC-07): guru
 * pelaksana (terjadwal/pengganti) mencatat lalu mengajukan realisasi;
 * verifikasi oleh pemegang mandat yang berbeda dari pengaju; revisi baru
 * hanya sebelum diajukan/setelah ditolak; tidak terjadi JP ganda.
 */
class MysqlTeachingApiTest extends MysqlAuthTestCase
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

    /** @return array{0: User, 1: array<string, string>} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $this->grant($user, 'operator');

        return [$user, ['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']]];
    }

    /** Guru dengan pegawai aktif terhubung. @return array{0: User, 1: array<string, string>, 2: int} */
    private function teacher(): array
    {
        $person = Person::factory()->create();
        $user = User::factory()->for($person)->create();
        $this->grant($user, 'guru');
        $employeeId = DB::table('employees')->insertGetId(['person_id' => $person->id, 'employee_number' => 'EMP-TR-'.uniqid(), 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);

        return [$user, ['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']], $employeeId];
    }

    /** Mandat verifikasi realisasi untuk pengguna. */
    private function grantVerifyMandate(User $user, int $grantedBy): int
    {
        $permissionId = DB::table('permissions')->where('code', 'realizations.verify')->value('id');
        $this->assertNotNull($permissionId);

        return DB::table('authorization_mandates')->insertGetId([
            'user_id' => $user->id, 'permission_id' => $permissionId, 'scope_type' => 'institution',
            'effective_from' => now('UTC')->subSecond(), 'granted_by' => $grantedBy,
            'reason' => 'Mandat verifikator realisasi uji', 'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);
    }

    /**
     * Satu pertemuan Senin terbit untuk guru terjadwal + operator headers.
     *
     * @return array{0: array<string, string>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function seedOneMeeting(array $operatorHeaders, int $employeeId): array
    {
        $year = $this->postJson('/api/v1/academic/years', ['code' => '2026/2027', 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'reason' => 'Tahun ajaran uji realisasi'], $operatorHeaders)->assertCreated()->json('data');
        $semester = $this->postJson('/api/v1/academic/semesters', ['academic_year_id' => $year['id'], 'number' => 1, 'name' => 'Semester 1', 'start_date' => '2026-07-01', 'end_date' => '2026-12-19', 'reason' => 'Semester uji realisasi'], $operatorHeaders)->assertCreated()->json('data');
        $class = $this->postJson('/api/v1/academic/classes', ['code' => '7A', 'name' => 'Kelas 7A', 'grade' => 7, 'reason' => 'Rombel uji realisasi'], $operatorHeaders)->assertCreated()->json('data');
        $period = $this->postJson('/api/v1/academic/class-periods', ['class_id' => $class['id'], 'academic_year_id' => $year['id'], 'display_name' => 'VII-A 2026/2027', 'reason' => 'Periode rombel uji realisasi'], $operatorHeaders)->assertCreated()->json('data');
        $subjectId = DB::table('subjects')->insertGetId(['code' => 'TRV'.uniqid(), 'name' => 'Mapel uji', 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $assignment = $this->postJson('/api/v1/academic/teaching-assignments', ['employee_id' => $employeeId, 'subject_id' => $subjectId, 'class_period_id' => $period['id'], 'semester_id' => $semester['id'], 'effective_from' => '2026-07-13', 'reason' => 'Penugasan uji realisasi'], $operatorHeaders)->assertCreated()->json('data');
        $timetable = $this->postJson('/api/v1/academic/timetables', ['semester_id' => $semester['id'], 'name' => 'Jadwal utama', 'reason' => 'Jadwal uji realisasi'], $operatorHeaders)->assertCreated()->json('data');
        $version = $this->postJson('/api/v1/academic/timetables/'.$timetable['id'].'/versions', ['effective_from' => '2026-07-13', 'reason' => 'Draf uji realisasi'], $operatorHeaders)->assertCreated()->json('data');
        $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/entries', ['teaching_assignment_id' => $assignment['id'], 'day_of_week' => 1, 'start_time' => '07:30', 'end_time' => '08:50', 'planned_jp' => 2, 'reason' => 'Entri Senin uji realisasi'], $operatorHeaders)->assertCreated();
        $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/publish', ['reason' => 'Terbitkan jadwal uji'], $operatorHeaders)->assertOk();
        $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/generate-meetings', ['date_from' => '2026-07-13', 'date_to' => '2026-07-13', 'reason' => 'Satu pertemuan uji realisasi'], $operatorHeaders)->assertOk();

        return [$semester, $assignment, $period];
    }

    public function test_access_follows_roles_and_meeting_duty(): void
    {
        [$plain, $plainHeaders] = [User::factory()->create(), null];
        $plainHeaders = ['Authorization' => 'Bearer '.$this->loginUser($plain)['access_token']];
        $this->getJson('/api/v1/teaching/meetings', $plainHeaders)->assertForbidden();

        [, $operatorHeaders] = $this->operator();
        $this->getJson('/api/v1/teaching/meetings', $operatorHeaders)->assertForbidden();
        $this->getJson('/api/v1/teaching/verifications', $operatorHeaders)->assertForbidden();

        [$operatorUser] = $this->operator();
        $this->grantVerifyMandate($operatorUser, $operatorUser->id);
        $freshOperatorHeaders = ['Authorization' => 'Bearer '.$this->loginUser($operatorUser)['access_token']];
        $this->getJson('/api/v1/teaching/verifications', $freshOperatorHeaders)->assertOk();

        [$teacherUser, $teacherHeaders, $employeeId] = $this->teacher();
        [$semester] = $this->seedOneMeeting($operatorHeaders, $employeeId);
        $meetings = $this->getJson('/api/v1/teaching/meetings?date_from=2026-07-13&date_to=2026-07-13', $teacherHeaders)->assertOk()->json('data');
        $this->assertCount(1, $meetings);

        [$stranger, $strangerHeaders] = $this->teacher();
        $this->getJson('/api/v1/teaching/meetings/'.$meetings[0]['id'], $strangerHeaders)->assertNotFound();
        $this->postJson('/api/v1/teaching/meetings/'.$meetings[0]['id'].'/realizations', [
            'execution_status' => 'held', 'material' => 'Materi', 'actual_jp' => 2, 'reason' => 'Bukan penugasannya',
        ], $strangerHeaders)->assertNotFound();
        $this->getJson('/api/v1/teaching/verifications', $teacherHeaders)->assertForbidden();
    }

    public function test_realization_lifecycle_with_revisions_and_decisions(): void
    {
        [, $operatorHeaders] = $this->operator();
        [$teacherUser, $teacherHeaders, $employeeId] = $this->teacher();
        [$semester] = $this->seedOneMeeting($operatorHeaders, $employeeId);
        $meeting = $this->getJson('/api/v1/teaching/meetings?date_from=2026-07-13&date_to=2026-07-13', $teacherHeaders)->assertOk()->json('data')[0];

        // Tidak terlaksatakan tanpa alasan ditolak; JP dipaksa nol (FR-06-03).
        $this->postJson('/api/v1/teaching/meetings/'.$meeting['id'].'/realizations', [
            'execution_status' => 'not_held', 'actual_jp' => 2, 'reason' => 'Uji validasi alasan wajib',
        ], $teacherHeaders)->assertStatus(422);

        $draft = $this->postJson('/api/v1/teaching/meetings/'.$meeting['id'].'/realizations', [
            'execution_status' => 'held', 'material' => 'Aljabar dasar', 'actual_jp' => 2,
            'actual_started_at' => '2026-07-13 07:30:00', 'actual_ended_at' => '2026-07-13 08:50:00',
            'reason' => 'Pencatatan realisasi pertama',
        ], $teacherHeaders)->assertCreated()->json('data');
        $this->assertSame('not_submitted', $draft['verification_status']);
        $this->assertSame(1, $draft['revision_number']);

        // Realisasi ganda ditolak (AC-07): satu catatan aktif per pertemuan.
        $this->postJson('/api/v1/teaching/meetings/'.$meeting['id'].'/realizations', [
            'execution_status' => 'held', 'material' => 'Ganda', 'actual_jp' => 2, 'reason' => 'Percobaan catatan ganda',
        ], $teacherHeaders)->assertStatus(422);

        // Verifikator = pengaju ditolak; tanpa mandat ditolak.
        $this->postJson('/api/v1/teaching/revisions/'.$draft['id'].'/decision', ['action' => 'approve', 'reason' => 'Verifikasi diri sendiri'], $teacherHeaders)->assertForbidden();
        [, $operatorHeaders2] = $this->operator();
        $this->postJson('/api/v1/teaching/revisions/'.$draft['id'].'/decision', ['action' => 'approve', 'reason' => 'Tanpa mandat verifikator'], $operatorHeaders2)->assertForbidden();

        // Ajukan lalu putuskan: tolak → revisi baru → ajukan → setujui.
        $this->postJson('/api/v1/teaching/revisions/'.$draft['id'].'/submit', ['reason' => 'Pengajuan verifikasi realisasi'], $teacherHeaders)->assertOk()->assertJsonPath('data.verification_status', 'pending');
        $this->postJson('/api/v1/teaching/meetings/'.$meeting['id'].'/realizations', [
            'execution_status' => 'held', 'material' => 'Edit saat menunggu', 'actual_jp' => 1, 'reason' => 'Revisi saat menunggu',
        ], $teacherHeaders)->assertStatus(422);

        [$verifier] = $this->operator();
        $this->grantVerifyMandate($verifier, $verifier->id);
        $verifierHeaders = ['Authorization' => 'Bearer '.$this->loginUser($verifier)['access_token']];
        $queue = $this->getJson('/api/v1/teaching/verifications', $verifierHeaders)->assertOk()->json('data');
        $this->assertCount(1, $queue);
        $this->assertSame($teacherUser->id, $queue[0]['submitted_by']);

        $this->postJson('/api/v1/teaching/revisions/'.$draft['id'].'/decision', ['action' => 'reject', 'reason' => 'Materi belum sesuai silabus minggu itu'], $verifierHeaders)->assertOk()->assertJsonPath('data.verification_status', 'rejected');
        $this->postJson('/api/v1/teaching/revisions/'.$draft['id'].'/decision', ['action' => 'approve', 'reason' => 'Keputusan kedua ditolak'], $verifierHeaders)->assertStatus(422);

        $revised = $this->postJson('/api/v1/teaching/meetings/'.$meeting['id'].'/realizations', [
            'execution_status' => 'held', 'material' => 'Aljabar dasar dan latihan', 'actual_jp' => 2,
            'revision_reason' => 'Perbaikan materi sesuai catatan verifikator',
        ], $teacherHeaders)->assertCreated()->json('data');
        $this->assertSame(2, $revised['revision_number']);
        $this->postJson('/api/v1/teaching/revisions/'.$revised['id'].'/submit', ['reason' => 'Pengajuan ulang setelah perbaikan'], $teacherHeaders)->assertOk();
        $this->postJson('/api/v1/teaching/revisions/'.$revised['id'].'/decision', ['action' => 'approve', 'reason' => 'Perbaikan sesuai; disetujui'], $verifierHeaders)->assertOk()->assertJsonPath('data.verification_status', 'approved');

        $history = $this->getJson('/api/v1/teaching/realizations', $teacherHeaders)->assertOk()->json('data');
        $this->assertSame(2.0, (float) $history['totals']['approved_jp']);
        $this->assertSame(1, $history['totals']['approved_meetings']);
        $this->assertSame('approved', $history['rows']['data'][0]['verification_status']);
    }

    public function test_substitute_records_realization_as_performer(): void
    {
        [, $operatorHeaders] = $this->operator();
        [$teacherUser, , $employeeId] = $this->teacher();
        [$semester, , $period] = $this->seedOneMeeting($operatorHeaders, $employeeId);
        $meeting = $this->getJson('/api/v1/academic/meetings?date_from=2026-07-13&date_to=2026-07-13', $operatorHeaders)->assertOk()->json('data')[0];

        [$substituteUser, $substituteHeaders, $substituteEmployeeId] = $this->teacher();
        $assignment = $this->postJson('/api/v1/academic/meetings/'.$meeting['id'].'/substitute', [
            'substitute_employee_id' => $substituteEmployeeId, 'reason' => 'Guru terjadwal berhalangan dinas',
        ], $operatorHeaders)->assertCreated()->json('data');

        // Pengganti melihat pertemuan sebagai tugasnya dan mencatat sebagai pelaksana.
        $own = $this->getJson('/api/v1/teaching/meetings?date_from=2026-07-13&date_to=2026-07-13', $substituteHeaders)->assertOk()->json('data');
        $this->assertCount(1, $own);
        $revision = $this->postJson('/api/v1/teaching/meetings/'.$meeting['id'].'/realizations', [
            'execution_status' => 'held', 'material' => 'Penggantian materi', 'actual_jp' => 2, 'reason' => 'Realisasi oleh guru pengganti',
        ], $substituteHeaders)->assertCreated()->json('data');
        $this->assertSame($substituteEmployeeId, $revision['actual_employee_id']);
        $this->assertNotNull($revision['substituteAssignment']);

        // Guru terjadwal tidak dapat menimpa catatan pelaksana lain (FR-06-04).
        $teacherHeaders = ['Authorization' => 'Bearer '.$this->loginUser($teacherUser)['access_token']];
        $this->postJson('/api/v1/teaching/meetings/'.$meeting['id'].'/realizations', [
            'execution_status' => 'held', 'material' => 'Menimpa catatan pengganti', 'actual_jp' => 1, 'reason' => 'Percobaan menimpa',
        ], $teacherHeaders)->assertStatus(422);
    }
}
