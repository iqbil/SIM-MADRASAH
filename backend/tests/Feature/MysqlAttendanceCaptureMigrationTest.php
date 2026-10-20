<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MysqlAttendanceCaptureMigrationTest extends TestCase
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

    public function test_legacy_attendance_remains_valid_without_retroactive_photo_or_gps(): void
    {
        [$user, $attendance, $policy] = $this->attendanceContext();

        $this->assertDatabaseHas('employee_attendance_policies', ['id' => $policy, 'requires_photo' => 0, 'requires_gps' => 0]);
        $this->assertDatabaseHas('employee_attendances', ['id' => $attendance, 'recorded_by' => $user]);
        $this->assertDatabaseMissing('employee_attendance_captures', ['employee_attendance_id' => $attendance]);
        DB::table('employee_attendance_policies')->where('id', $policy)->update(['requires_photo' => 1, 'requires_gps' => 1]);
        $this->assertDatabaseHas('employee_attendance_policies', ['id' => $policy, 'requires_photo' => 1, 'requires_gps' => 1, 'photo_max_size_bytes' => 307200, 'photo_max_dimension' => 1280, 'photo_quality' => 80, 'gps_max_accuracy_meters' => '100.00', 'gps_max_age_seconds' => 60]);
    }

    public function test_check_in_and_check_out_store_distinct_photos_and_locations(): void
    {
        [$user, $attendance] = $this->attendanceContext();
        $this->insert('employee_attendance_captures', $this->captureValues($user, $attendance));
        $this->insert('employee_attendance_captures', $this->captureValues($user, $attendance, 'check_out'));

        $this->assertSame(2, DB::table('employee_attendance_captures')->where('employee_attendance_id', $attendance)->count());
    }

    #[DataProvider('invalidCaptureValues')]
    public function test_invalid_or_incomplete_capture_is_rejected(array $changes, string $constraint): void
    {
        [$user, $attendance] = $this->attendanceContext();
        $values = array_replace($this->captureValues($user, $attendance), $changes);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage($constraint);
        $this->insert('employee_attendance_captures', $values);
    }

    public static function invalidCaptureValues(): array
    {
        return [
            'latitude too high' => [['latitude' => '90.0000001'], 'ck_eac_coordinates'],
            'latitude too low' => [['latitude' => '-90.0000001'], 'ck_eac_coordinates'],
            'longitude too high' => [['longitude' => '180.0000001'], 'ck_eac_coordinates'],
            'negative accuracy' => [['accuracy_meters' => '-0.01'], 'ck_eac_coordinates'],
            'missing latitude' => [['latitude' => null], 'latitude'],
            'missing photo' => [['photo_file_id' => null], 'photo_file_id'],
            'invalid event' => [['event_type' => 'daily'], 'ck_eac_event'],
            'invalid compression quality' => [['compression_quality' => 101], 'ck_eac_photo_metadata'],
            'empty original' => [['original_size_bytes' => 0], 'ck_eac_photo_metadata'],
            'future location' => [['location_captured_at' => '2026-10-20 00:01:01'], 'ck_eac_capture_times'],
            'future photo' => [['photo_taken_at' => '2026-10-20 00:01:01'], 'ck_eac_capture_times'],
        ];
    }

    public function test_duplicate_event_is_rejected_even_with_a_different_photo(): void
    {
        [$user, $attendance] = $this->attendanceContext();
        $this->insert('employee_attendance_captures', $this->captureValues($user, $attendance));

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_eac_attendance_event');
        $this->insert('employee_attendance_captures', $this->captureValues($user, $attendance));
    }

    public function test_photo_cannot_be_reused_for_check_out(): void
    {
        [$user, $attendance] = $this->attendanceContext();
        $values = $this->captureValues($user, $attendance);
        $this->insert('employee_attendance_captures', $values);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('uq_eac_photo_file');
        $this->insert('employee_attendance_captures', array_replace($values, ['event_type' => 'check_out', 'operation_key' => (string) Str::uuid()]));
    }

    public function test_photo_reference_cannot_be_deleted(): void
    {
        [$user, $attendance] = $this->attendanceContext();
        $values = $this->captureValues($user, $attendance);
        $this->insert('employee_attendance_captures', $values);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('fk_eac_photo');
        DB::table('files')->where('id', $values['photo_file_id'])->delete();
    }

    #[DataProvider('invalidPolicyValues')]
    public function test_invalid_capture_policy_is_rejected(array $changes, string $constraint): void
    {
        [, , $policy] = $this->attendanceContext();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage($constraint);
        DB::table('employee_attendance_policies')->where('id', $policy)->update($changes);
    }

    public static function invalidPolicyValues(): array
    {
        return [
            'invalid boolean' => [['requires_photo' => 2], 'ck_eap_capture_flags'],
            'incomplete capture requirement' => [['requires_photo' => 1, 'requires_gps' => 0], 'ck_eap_capture_flags'],
            'zero output limit' => [['photo_max_size_bytes' => 0], 'ck_eap_photo_limits'],
            'excessive output limit' => [['photo_max_size_bytes' => 10485761], 'ck_eap_photo_limits'],
            'invalid quality' => [['photo_quality' => 101], 'ck_eap_photo_limits'],
            'zero accuracy limit' => [['gps_max_accuracy_meters' => 0], 'ck_eap_gps_limits'],
            'zero location age' => [['gps_max_age_seconds' => 0], 'ck_eap_gps_limits'],
        ];
    }

    private function insert(string $table, array $values): int
    {
        return DB::table($table)->insertGetId($values + ['created_at' => '2026-10-20 00:01:00.000000', 'updated_at' => '2026-10-20 00:01:00.000000']);
    }

    private function attendanceContext(): array
    {
        $person = $this->insert('persons', ['full_name' => 'Pegawai uji foto GPS']);
        $user = $this->insert('users', ['person_id' => $person, 'name' => 'Pegawai uji', 'username' => 'capture_test', 'password' => 'synthetic-hash', 'status' => 'active']);
        $employee = $this->insert('employees', ['person_id' => $person, 'employee_number' => 'CAPTURE-TEST']);
        $status = $this->insert('attendance_statuses', ['domain' => 'employee', 'code' => 'hadir', 'name' => 'Hadir']);
        $decision = $this->insert('business_decisions', ['code' => 'DEC-16', 'title' => 'Absensi foto GPS', 'status' => 'baseline', 'decision_text' => 'Synthetic test policy', 'source_reference' => 'Test', 'recorded_at' => '2026-10-20 00:00:00', 'version' => 2, 'effective_from' => '2026-10-20']);
        $pattern = $this->insert('work_patterns', ['code' => 'CAPTURE', 'name' => 'Pola kerja uji']);
        $version = $this->insert('work_pattern_versions', ['work_pattern_id' => $pattern, 'version_number' => 1, 'day_of_week' => 2, 'start_time' => '07:00:00', 'end_time' => '14:00:00', 'late_tolerance_minutes' => 10, 'effective_from' => '2026-10-20', 'decision_id' => $decision, 'created_by' => $user]);
        $policy = $this->insert('employee_attendance_policies', ['code' => 'CAPTURE', 'version_number' => 2, 'status' => 'baseline', 'correction_deadline_days' => 7, 'requires_correction_approval' => 1, 'allow_late_correction_with_mandate' => 1, 'effective_from' => '2026-10-20', 'decision_id' => $decision]);
        $assignment = $this->insert('employee_work_assignments', ['employee_id' => $employee, 'work_pattern_id' => $pattern, 'effective_from' => '2026-10-20', 'created_by' => $user]);
        $day = $this->insert('employee_work_days', ['employee_id' => $employee, 'work_assignment_id' => $assignment, 'work_pattern_version_id' => $version, 'policy_id' => $policy, 'work_date' => '2026-10-20', 'shift_number' => 1, 'scheduled_start_at' => '2026-10-20 00:00:00', 'scheduled_end_at' => '2026-10-20 07:00:00', 'tolerance_minutes_snapshot' => 10, 'is_work_required' => 1]);
        $attendance = $this->insert('employee_attendances', ['work_day_id' => $day, 'attendance_status_id' => $status, 'check_in_at' => '2026-10-20 00:01:00', 'entry_mode' => 'self', 'recorded_by' => $user, 'recorded_at' => '2026-10-20 00:01:00']);

        return [$user, $attendance, $policy];
    }

    private function captureValues(int $user, int $attendance, string $event = 'check_in'): array
    {
        $file = $this->insert('files', ['storage_disk' => 'private', 'storage_path' => 'attendance/'.Str::uuid().'.webp', 'original_name' => 'capture.webp', 'mime_type' => 'image/webp', 'extension' => 'webp', 'size_bytes' => 102400, 'sha256' => str_repeat('a', 64), 'classification' => 'internal', 'scan_status' => 'clean', 'uploaded_by' => $user]);

        return ['employee_attendance_id' => $attendance, 'event_type' => $event, 'photo_file_id' => $file, 'latitude' => '-6.2000000', 'longitude' => '106.8166667', 'accuracy_meters' => '15.00', 'photo_taken_at' => '2026-10-20 00:00:50', 'location_captured_at' => '2026-10-20 00:00:45', 'recorded_at' => '2026-10-20 00:01:00', 'original_size_bytes' => 2097152, 'photo_width' => 960, 'photo_height' => 1280, 'compression_quality' => 80, 'policy_snapshot' => '{"requires_photo":true,"requires_gps":true,"photo_max_size_bytes":307200,"photo_max_dimension":1280,"photo_quality":80,"gps_max_accuracy_meters":100,"gps_max_age_seconds":60}', 'recorded_by' => $user, 'operation_key' => (string) Str::uuid()];
    }
}
