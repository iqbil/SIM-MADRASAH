<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use App\Models\UserRole;
use Carbon\CarbonImmutable;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\MysqlAuthTestCase;

/**
 * Uji API absensi pegawai (SRS v1.3 FR-05): aksi mandiri foto+GPS dengan
 * idempotensi dan validasi kebaruan (FR-05-04), kompresi WebP (FR-05-05),
 * pencatatan izin petugas (FR-05-01/07), koreksi dengan persetujuan orang
 * berbeda (FR-05-02/03), pola kerja, generator hari kerja, dan rekap.
 */
class MysqlAttendanceApiTest extends MysqlAuthTestCase
{
    /** JPEG 1x1 px valid untuk uji pipeline foto. */
    private const PHOTO_BASE64 = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD/2wBDAQMDAwQDBAgEBAgQCwkLEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBD/wAARCAAGAAgDAREAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAH/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AUBFDT//Z';

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

    /** @return array{0: User, 1: array<string, string>, 2: int} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $this->grant($user, 'operator');

        return [$user, ['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']], $user->id];
    }

    /** Pegawai aktif + akun terhubung; pola kerja 7 hari agar uji bebas kalender. @return array{0: array<string, string>, 1: int, 2: User} */
    private function employeeUser(array $operatorHeaders): array
    {
        $person = Person::factory()->create();
        $user = User::factory()->for($person)->create();
        $employeeId = DB::table('employees')->insertGetId(['person_id' => $person->id, 'employee_number' => 'EMP-ATT-'.uniqid(), 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $ownHeaders = ['Authorization' => 'Bearer '.$this->loginUser($user)['access_token']];
        // Pemanggilan pertama mem-bootstrap baseline DEC-16/policy/pola bawaan.
        $this->getJson('/api/v1/attendance/status', $ownHeaders)->assertOk();

        $pattern = $this->post('/api/v1/attendance/work-patterns', [
            'code' => 'FULLWEEK', 'name' => 'Pola uji tujuh hari', 'effective_from' => today('Asia/Jakarta')->subDays(30)->toDateString(),
            'days' => array_map(fn (int $day): array => ['day_of_week' => $day, 'start_time' => '07:00', 'end_time' => '14:00', 'late_tolerance_minutes' => 10], range(1, 7)),
            'reason' => 'Pola kerja tujuh hari untuk pengujian absensi',
        ], $operatorHeaders)->assertCreated()->json('data');
        $this->post('/api/v1/attendance/work-patterns/'.$pattern['id'].'/assignments', [
            'employee_id' => $employeeId, 'effective_from' => today('Asia/Jakarta')->subDays(30)->toDateString(),
            'reason' => 'Penetapan pola kerja pegawai uji',
        ], $operatorHeaders)->assertCreated();

        return [$ownHeaders, $employeeId, $user];
    }

    /** @param array<string, mixed> $overrides */
    private function clockPayload(array $overrides = []): array
    {
        return array_merge([
            'action' => 'check_in', 'operation_key' => (string) Str::uuid(),
            'latitude' => -6.7, 'longitude' => 108.5, 'accuracy_meters' => 12.5,
            'photo_taken_at' => now('UTC')->toIso8601String(), 'location_captured_at' => now('UTC')->toIso8601String(),
            'photo' => UploadedFile::fake()->createWithContent('photo.jpg', base64_decode(self::PHOTO_BASE64)),
        ], $overrides);
    }

    public function test_access_follows_roles_and_employee_link(): void
    {
        [$plainUser, $plainHeaders] = [User::factory()->create(), null];
        $plainHeaders = ['Authorization' => 'Bearer '.$this->loginUser($plainUser)['access_token']];
        $this->getJson('/api/v1/attendance/status', $plainHeaders)->assertForbidden();
        $this->getJson('/api/v1/attendance/records', $plainHeaders)->assertForbidden();
        $this->getJson('/api/v1/attendance/recap', $plainHeaders)->assertForbidden();

        [, $operatorHeaders] = $this->operator();
        $this->getJson('/api/v1/attendance/records', $operatorHeaders)->assertOk();
        // Operator tanpa linkage pegawai tetap boleh membaca kebijakan (halaman kelola).
        $this->getJson('/api/v1/attendance/policy', $operatorHeaders)->assertOk();
        $this->getJson('/api/v1/attendance/recap', $operatorHeaders)->assertOk();
        $this->postJson('/api/v1/attendance/corrections/1/decision', ['decision' => 'approve', 'reason' => 'Operator tanpa mandat tidak boleh menyetujui'], $operatorHeaders)->assertForbidden();

        $kepala = User::factory()->create();
        $this->grant($kepala, 'kepala_madrasah');
        $kepalaHeaders = ['Authorization' => 'Bearer '.$this->loginUser($kepala)['access_token']];
        $this->getJson('/api/v1/attendance/recap', $kepalaHeaders)->assertOk();
        $this->getJson('/api/v1/attendance/records', $kepalaHeaders)->assertForbidden();

        [$employeeHeaders] = $this->employeeUser($operatorHeaders);
        $this->getJson('/api/v1/attendance/status', $employeeHeaders)->assertOk()->assertJsonStructure(['data' => ['next_action', 'policy']]);
    }

    public function test_clock_flow_validates_freshness_accuracy_and_idempotency(): void
    {
        [, $operatorHeaders] = $this->operator();
        [$employeeHeaders, $employeeId] = $this->employeeUser($operatorHeaders);
        $status = $this->getJson('/api/v1/attendance/status', $employeeHeaders)->assertOk()->json('data');
        $this->assertNotNull($status['work_day']);
        $this->assertSame('check_in', $status['next_action']);

        $this->post('/api/v1/attendance/clock', $this->clockPayload(['photo_taken_at' => now('UTC')->subSeconds(120)->toIso8601String()]), $employeeHeaders)->assertStatus(422);
        $this->post('/api/v1/attendance/clock', $this->clockPayload(['accuracy_meters' => 500]), $employeeHeaders)->assertStatus(422);
        $this->post('/api/v1/attendance/clock', $this->clockPayload(['latitude' => 200]), $employeeHeaders)->assertStatus(422);

        $this->post('/api/v1/attendance/clock', $this->clockPayload(['photo_taken_at' => now('UTC')->addSeconds(30)->toIso8601String()]), $employeeHeaders)->assertStatus(422);
        $first = $this->clockPayload();
        $firstKey = $first['operation_key'];
        $checkedIn = $this->post('/api/v1/attendance/clock', $first, $employeeHeaders)->assertOk()->json('data');
        $this->assertNotNull($checkedIn['attendance']['check_in_at']);
        $this->assertSame('check_out', $checkedIn['next_action']);

        // Kunci idempotensi: kirim ulang operasi yang sama tidak mencatat ganda.
        $replay = $this->post('/api/v1/attendance/clock', $this->clockPayload(['operation_key' => $firstKey]), $employeeHeaders)->assertOk()->json('data');
        $this->assertTrue($replay['replayed']);
        $this->post('/api/v1/attendance/clock', $this->clockPayload(), $employeeHeaders)->assertStatus(422);

        $capture = DB::table('employee_attendance_captures')->where('event_type', 'check_in')->first();
        $this->assertNotNull($capture);
        $this->assertSame(8, (int) $capture->photo_width);
        $file = DB::table('files')->where('id', $capture->photo_file_id)->first();
        $this->assertSame('image/webp', $file->mime_type);
        $this->assertSame('internal', $file->classification);
        $this->assertLessThanOrEqual(307200, $file->size_bytes);
        $this->assertTrue(Storage::disk('local')->exists($file->storage_path));

        // Bukti hanya untuk pemilik/petugas (FR-05-06).
        $this->getJson('/api/v1/attendance/captures/'.$capture->id, $employeeHeaders)->assertOk();
        $this->getJson('/api/v1/attendance/captures/'.$capture->id, $operatorHeaders)->assertOk();
        $this->get('/api/v1/attendance/captures/'.$capture->id.'/photo', $employeeHeaders)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->getJson('/api/v1/attendance/captures/'.$capture->id, $this->guestHeaders())->assertUnauthorized();

        $checkedOut = $this->post('/api/v1/attendance/clock', $this->clockPayload(['action' => 'check_out']), $employeeHeaders)->assertOk()->json('data');
        $this->assertNotNull($checkedOut['attendance']['check_out_at']);
        $this->assertNull($checkedOut['next_action']);
        $this->post('/api/v1/attendance/clock', $this->clockPayload(['action' => 'check_out']), $employeeHeaders)->assertStatus(422);
    }

    public function test_admin_records_and_correction_require_different_approver(): void
    {
        [, $operatorHeaders, $requesterId] = $this->operator();
        [$employeeHeaders, $employeeId] = $this->employeeUser($operatorHeaders);
        $today = today('Asia/Jakarta')->toDateString();

        $record = $this->postJson('/api/v1/attendance/records', [
            'employee_id' => $employeeId, 'work_date' => $today, 'status_code' => 'izin',
            'notes' => 'Acara keluarga', 'reason' => 'Pencatatan izin pegawai melalui petugas',
        ], $operatorHeaders)->assertCreated()->json('data');
        $this->assertSame('officer', $record['entry_mode']);
        $this->postJson('/api/v1/attendance/records', [
            'employee_id' => $employeeId, 'work_date' => $today, 'status_code' => 'sakit', 'reason' => 'Duplikat pencatatan harus ditolak',
        ], $operatorHeaders)->assertStatus(409);

        $correction = $this->postJson('/api/v1/attendance/corrections', [
            'employee_attendance_id' => $record['id'],
            'after_values' => ['status_code' => 'sakit', 'notes' => 'Ternyata sakit, bukan izin'],
            'reason' => 'Koreksi status setelah surat dokter diterima',
            'expected_lock_version' => 0,
        ], $operatorHeaders)->assertCreated()->json('data');
        $this->assertSame('pending', $correction['status']);

        // Keputusan koreksi memerlukan mandat petugas; operator default tidak memilikinya.
        $this->postJson('/api/v1/attendance/corrections/'.$correction['id'].'/decision', [
            'decision' => 'approve', 'reason' => 'Tanpa mandat petugas harus ditolak',
        ], $operatorHeaders)->assertForbidden();

        $mandatePermission = DB::table('permissions')->where('code', 'attendance.correct')->value('id');
        $this->assertNotNull($mandatePermission);
        DB::table('authorization_mandates')->insert([
            'user_id' => $requesterId, 'permission_id' => $mandatePermission, 'scope_type' => 'institution',
            'effective_from' => now('UTC')->subSecond(), 'granted_by' => $requesterId,
            'reason' => 'Mandat uji persetujuan koreksi', 'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);

        // Pemohon tidak boleh menyetujui koreksinya sendiri (FR-05-02).
        $this->postJson('/api/v1/attendance/corrections/'.$correction['id'].'/decision', [
            'decision' => 'approve', 'reason' => 'Penyetuju sama dengan pemohon ditolak',
        ], $operatorHeaders)->assertStatus(422);

        [$secondOperator, $secondHeaders] = $this->operator();
        DB::table('authorization_mandates')->insert([
            'user_id' => $secondOperator->id, 'permission_id' => $mandatePermission, 'scope_type' => 'institution',
            'effective_from' => now('UTC')->subSecond(), 'granted_by' => $requesterId,
            'reason' => 'Mandat penyetuju kedua untuk uji koreksi', 'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);
        $this->postJson('/api/v1/attendance/corrections/'.$correction['id'].'/decision', [
            'decision' => 'approve', 'reason' => 'Disetujui petugas lain setelah memeriksa surat',
        ], $secondHeaders)->assertOk()->assertJsonPath('data.status', 'applied');
        $this->assertSame('sakit', DB::table('employee_attendances')
            ->join('attendance_statuses', 'attendance_statuses.id', '=', 'employee_attendances.attendance_status_id')
            ->where('employee_attendances.id', $record['id'])->value('attendance_statuses.code'));

        // Lewat batas koreksi 7 hari ditolak (FR-05-03).
        $oldDate = today('Asia/Jakarta')->subDays(10)->toDateString();
        $oldRecord = $this->postJson('/api/v1/attendance/records', [
            'employee_id' => $employeeId, 'work_date' => $oldDate, 'status_code' => 'dinas_luar',
            'reason' => 'Pencatatan dinas luar mundur untuk uji tenggat',
        ], $operatorHeaders)->assertCreated()->json('data');
        $this->postJson('/api/v1/attendance/corrections', [
            'employee_attendance_id' => $oldRecord['id'],
            'after_values' => ['notes' => 'Perubahan terlambat'],
            'reason' => 'Koreksi melewati tenggat tujuh hari',
            'expected_lock_version' => 0,
        ], $operatorHeaders)->assertStatus(422);
    }

    public function test_work_pattern_versions_assignment_and_recap(): void
    {
        [, $operatorHeaders] = $this->operator();
        [$employeeHeaders, $employeeId] = $this->employeeUser($operatorHeaders);
        $today = today('Asia/Jakarta')->toDateString();

        $generated = $this->postJson('/api/v1/attendance/work-days/generate', [
            'date_from' => $today, 'date_to' => $today, 'reason' => 'Penyiapan hari kerja hari ini untuk rekap',
        ], $operatorHeaders)->assertOk()->json('data');
        $this->assertGreaterThanOrEqual(1, $generated['work_days']);

        $this->post('/api/v1/attendance/clock', $this->clockPayload(), $employeeHeaders)->assertOk();
        $recap = $this->getJson('/api/v1/attendance/recap?employee_id='.$employeeId, $operatorHeaders)->assertOk()->json('data');
        $this->assertSame(1, $recap['summary'][0]['hadir']);
        $this->assertNotNull($recap['daily']);

        $history = $this->getJson('/api/v1/attendance/me', $employeeHeaders)->assertOk()->json('data');
        $this->assertNotEmpty($history['rows']['data']);
        $todayRow = collect($history['rows']['data'])->first(fn (array $row): bool => substr($row['work_date'], 0, 10) === today('Asia/Jakarta')->toDateString());
        $this->assertSame('hadir', $todayRow['attendance']['attendance_status']['code'] ?? null);

        // Versi baru menutup versi lama; penetapan ulang menutup penetapan lama.
        $patterns = $this->getJson('/api/v1/attendance/work-patterns?per_page=100', $operatorHeaders)->assertOk()->json('data');
        $fullweek = collect($patterns)->first(fn (array $row): bool => $row['code'] === 'FULLWEEK');
        $this->postJson('/api/v1/attendance/work-patterns/'.$fullweek['id'].'/versions', [
            'effective_from' => today('Asia/Jakarta')->addDays(30)->toDateString(),
            'days' => [['day_of_week' => 1, 'start_time' => '07:30', 'end_time' => '14:00', 'late_tolerance_minutes' => 5]],
            'reason' => 'Perubahan jam kerja efektif bulan depan',
        ], $operatorHeaders)->assertOk();
        $this->postJson('/api/v1/attendance/work-patterns', [
            'code' => 'SHIFT2', 'name' => 'Pola kedua', 'effective_from' => today('Asia/Jakarta')->addDays(40)->toDateString(),
            'days' => [['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '15:00', 'late_tolerance_minutes' => 10]],
            'reason' => 'Pola pembanding untuk uji pergantian penetapan',
        ], $operatorHeaders)->assertCreated();
        $shift2 = collect($this->getJson('/api/v1/attendance/work-patterns?per_page=100', $operatorHeaders)->json('data'))->firstWhere('code', 'SHIFT2');
        $this->postJson('/api/v1/attendance/work-patterns/'.$shift2['id'].'/assignments', [
            'employee_id' => $employeeId, 'effective_from' => today('Asia/Jakarta')->addDays(40)->toDateString(),
            'reason' => 'Perpindahan pegawai ke pola kedua',
        ], $operatorHeaders)->assertCreated();
        $assignments = $this->getJson('/api/v1/attendance/work-assignments?per_page=100', $operatorHeaders)->assertOk()->json('data');
        $fullweekAssignment = collect($assignments)->first(fn (array $row): bool => $row['work_pattern_id'] === $fullweek['id']);
        $this->assertSame(today('Asia/Jakarta')->addDays(39)->toDateString(), substr($fullweekAssignment['effective_to'], 0, 10));
    }

    /** @return array<string, string> */
    private function guestHeaders(): array
    {
        return [];
    }

    public function test_optional_join_date_navigation_lateness_and_correction_contract(): void
    {
        [, $op] = $this->operator();
        [$headers, $employeeId, $user] = $this->employeeUser($op);
        $this->getJson('/api/v1/auth/me', $headers)->assertOk()->assertJsonFragment(['attendance.own']);
        $record = $this->postJson('/api/v1/attendance/records', [
            'employee_id' => $employeeId, 'work_date' => today('Asia/Jakarta')->toDateString(), 'status_code' => 'hadir',
            'check_in_at' => today('Asia/Jakarta')->setTime(10, 0)->utc()->format('Y-m-d H:i:s'),
            'reason' => 'Fallback hadir administratif pukul sepuluh pagi',
        ], $op)->assertCreated()->assertJsonPath('data.late_minutes', 170)->json('data');
        $this->getJson('/api/v1/attendance/records?employee_id='.$employeeId, $op)->assertOk()
            ->assertJsonPath('data.0.employee.person.full_name', $user->person->full_name)
            ->assertJsonPath('data.0.attendance.id', $record['id'])
            ->assertJsonPath('data.0.attendance.attendanceStatus.code', 'hadir');
        $this->postJson('/api/v1/attendance/corrections', ['employee_attendance_id' => $record['id'], 'after_values' => ['notes' => 'Catatan koreksi'], 'expected_lock_version' => 0, 'reason' => 'Pengajuan koreksi untuk uji kontrak respons'], $op)->assertCreated();
        $this->getJson('/api/v1/attendance/corrections?employee_id='.$employeeId, $op)->assertOk()
            ->assertJsonPath('data.0.employeeAttendance.workDay.employee.person.full_name', $user->person->full_name)
            ->assertJsonPath('data.0.employeeAttendance.workDay.work_date', CarbonImmutable::parse(today('Asia/Jakarta')->toDateString(), 'UTC')->toJSON());
        $this->getJson('/api/v1/attendance/recap?employee_id='.$employeeId, $op)->assertOk()->assertJsonPath('data.summary.0.terlambat', 1);
    }
}
