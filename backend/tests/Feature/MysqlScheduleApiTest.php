<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\TimetableVersion;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\DB;
use Tests\MysqlAuthTestCase;

/**
 * Uji API jadwal pelajaran & guru pengganti (SRS v1.3 FR-04, AC-04/05/09):
 * versi jadwal, benturan guru/kelas, generator pertemuan dengan kalender,
 * pembatalan pertemuan, dan penunjukan/pencabutan guru pengganti.
 */
class MysqlScheduleApiTest extends MysqlAuthTestCase
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

    private function employee(?int $personId = null): int
    {
        return DB::table('employees')->insertGetId(['person_id' => $personId ?? Person::factory()->create()->id, 'employee_number' => 'EMP-'.uniqid(), 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
    }

    private function subject(string $code): int
    {
        return DB::table('subjects')->insertGetId(['code' => $code.uniqid(), 'name' => 'Mapel '.$code, 'is_active' => true, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
    }

    /**
     * Siapkan tahun/semester/kelas/penugasan + jadwal MAIN dengan satu versi
     * terbit berisi satu entri Senin 07:30.
     *
     * @return array<string, int|array<string, mixed>>
     */
    private function seedPublishedSchedule(array $headers, string $mondayAt = '07:30', ?int $employeeId = null): array
    {
        $year = $this->postJson('/api/v1/academic/years', ['code' => '2026/2027', 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'reason' => 'Tahun ajaran uji jadwal'], $headers)->assertCreated()->json('data');
        $semester = $this->postJson('/api/v1/academic/semesters', ['academic_year_id' => $year['id'], 'number' => 1, 'name' => 'Semester 1', 'start_date' => '2026-07-01', 'end_date' => '2026-12-19', 'reason' => 'Semester ganjil uji jadwal'], $headers)->assertCreated()->json('data');
        $class = $this->postJson('/api/v1/academic/classes', ['code' => '7A', 'name' => 'Kelas 7A', 'grade' => 7, 'reason' => 'Rombel uji jadwal'], $headers)->assertCreated()->json('data');
        $period = $this->postJson('/api/v1/academic/class-periods', ['class_id' => $class['id'], 'academic_year_id' => $year['id'], 'display_name' => 'VII-A 2026/2027', 'reason' => 'Periode rombel uji jadwal'], $headers)->assertCreated()->json('data');
        $employeeId ??= $this->employee();
        $assignment = $this->postJson('/api/v1/academic/teaching-assignments', ['employee_id' => $employeeId, 'subject_id' => $this->subject('MTK'), 'class_period_id' => $period['id'], 'semester_id' => $semester['id'], 'effective_from' => '2026-07-13', 'reason' => 'Penugasan mengajar uji jadwal'], $headers)->assertCreated()->json('data');
        $timetable = $this->postJson('/api/v1/academic/timetables', ['semester_id' => $semester['id'], 'name' => 'Jadwal utama', 'reason' => 'Pembuatan jadwal utama semester 1'], $headers)->assertCreated()->json('data');
        $version = $this->postJson('/api/v1/academic/timetables/'.$timetable['id'].'/versions', ['effective_from' => '2026-07-13', 'reason' => 'Draf awal jadwal'], $headers)->assertCreated()->json('data');
        $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/entries', ['teaching_assignment_id' => $assignment['id'], 'day_of_week' => 1, 'start_time' => $mondayAt, 'end_time' => '08:50', 'planned_jp' => 2, 'reason' => 'Entri Senin pagi'], $headers)->assertCreated();
        $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/publish', ['reason' => 'Penerbitan jadwal awal'], $headers)->assertOk()->assertJsonPath('data.status', 'published');

        return ['year' => $year, 'semester' => $semester, 'period' => $period, 'employee_id' => $employeeId, 'assignment' => $assignment, 'timetable' => $timetable, 'version' => $version];
    }

    public function test_access_follows_roles_for_schedules_and_substitutes(): void
    {
        $plain = User::factory()->create();
        $plainHeaders = ['Authorization' => 'Bearer '.$this->loginUser($plain)['access_token']];
        foreach (['timetables', 'meetings', 'lesson-slots?semester_id=1', 'substitute-assignments'] as $path) {
            $this->getJson('/api/v1/academic/'.$path, $plainHeaders)->assertForbidden();
        }

        [$operator, $headers] = $this->operator();
        $me = $this->getJson('/api/v1/auth/me', $headers)->assertOk()->json('data');
        $this->assertContains('academic.schedules.manage', $me['permissions']);
        $this->assertContains('academic.substitutes.manage', $me['permissions']);

        $guru = User::factory()->create();
        $this->grant($guru, 'guru');
        $guruHeaders = ['Authorization' => 'Bearer '.$this->loginUser($guru)['access_token']];
        $guruMe = $this->getJson('/api/v1/auth/me', $guruHeaders)->assertOk()->json('data');
        $this->assertContains('academic.schedules.view', $guruMe['permissions']);
        $this->assertNotContains('academic.schedules.manage', $guruMe['permissions']);
        $this->assertContains('academic.substitutes.view', $guruMe['permissions']);
        $this->getJson('/api/v1/academic/timetables', $guruHeaders)->assertOk();
        $this->postJson('/api/v1/academic/timetables', ['semester_id' => 1, 'name' => 'X', 'reason' => 'Guru tidak boleh membuat jadwal'], $guruHeaders)->assertForbidden();
        $this->postJson('/api/v1/academic/meetings/1/substitute', ['substitute_employee_id' => 1, 'reason' => 'Guru tidak boleh menunjuk pengganti'], $guruHeaders)->assertForbidden();
    }

    public function test_timetable_versions_publish_supersede_and_conflicts(): void
    {
        [, $headers] = $this->operator();
        $seed = $this->seedPublishedSchedule($headers);
        $semester = $seed['semester'];
        $timetable = $seed['timetable'];

        $this->postJson('/api/v1/academic/timetables', ['semester_id' => $semester['id'], 'name' => 'Duplikat', 'reason' => 'Kode MAIN sudah ada pada semester'], $headers)->assertStatus(409);
        $this->putJson('/api/v1/academic/timetables/'.$timetable['id'], ['name' => 'Jadwal utama MTs', 'expected_updated_at' => $timetable['updated_at'], 'reason' => 'Perbaikan nama jadwal'], $headers)->assertOk()->assertJsonPath('data.name', 'Jadwal utama MTs');

        // Entri pada versi terbit tidak dapat diubah atau dihapus.
        $entries = $this->getJson('/api/v1/academic/timetable-versions/'.$seed['version']['id'].'/entries', $headers)->assertOk()->json('data');
        $this->assertCount(1, $entries);
        $this->assertArrayHasKey('teachingAssignment', $entries[0]);
        $this->putJson('/api/v1/academic/schedule-entry-versions/'.$entries[0]['id'], ['room' => 'R-1', 'expected_updated_at' => $entries[0]['updated_at'], 'reason' => 'Perubahan pada versi terbit ditolak'], $headers)->assertStatus(422);
        $this->deleteJson('/api/v1/academic/schedule-entry-versions/'.$entries[0]['id'], ['reason' => 'Penghapusan pada versi terbit ditolak'], $headers)->assertStatus(422);

        // Versi kedua disalin dari versi terbit; entri benturan dalam draf ditolak.
        $draft = $this->postJson('/api/v1/academic/timetables/'.$timetable['id'].'/versions', ['effective_from' => '2026-09-14', 'copy_from_version_id' => $seed['version']['id'], 'reason' => 'Penyesuaian jadwal setelah libur semester'], $headers)->assertCreated()->json('data');
        $copied = $this->getJson('/api/v1/academic/timetable-versions/'.$draft['id'].'/entries', $headers)->assertOk()->json('data');
        $this->assertCount(1, $copied);
        $this->postJson('/api/v1/academic/timetable-versions/'.$draft['id'].'/entries', ['teaching_assignment_id' => $seed['assignment']['id'], 'day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '09:00', 'planned_jp' => 2, 'reason' => 'Benturan dalam draf ditolak'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/academic/timetable-versions/'.$draft['id'].'/publish', ['reason' => 'Penerbitan versi penyesuaian'], $headers)->assertOk();
        $first = TimetableVersion::query()->find($seed['version']['id']);
        $this->assertSame('superseded', $first->status);
        $this->assertSame('2026-09-13', $first->effective_to->toDateString());

        // Benturan lintas versi terbit ditolak saat penerbitan (FR-04-02).
        $conflict = $this->postJson('/api/v1/academic/timetables/'.$timetable['id'].'/versions', ['effective_from' => '2026-10-05', 'reason' => 'Draf uji benturan'], $headers)->assertCreated()->json('data');
        $this->postJson('/api/v1/academic/timetable-versions/'.$conflict['id'].'/entries', ['teaching_assignment_id' => $seed['assignment']['id'], 'day_of_week' => 1, 'start_time' => '07:00', 'end_time' => '08:00', 'planned_jp' => 1, 'reason' => 'Entri menabrak versi terbit'], $headers)->assertCreated();
        $this->postJson('/api/v1/academic/timetable-versions/'.$conflict['id'].'/publish', ['reason' => 'Benturan lintas versi ditolak'], $headers)->assertStatus(422);

        $this->deleteJson('/api/v1/academic/schedule-entry-versions/'.$this->entryIdOf($conflict['id']), ['reason' => 'Hapus entri benturan pada draf'], $headers)->assertOk();
        $this->postJson('/api/v1/academic/timetable-versions/'.$conflict['id'].'/entries', ['teaching_assignment_id' => $seed['assignment']['id'], 'day_of_week' => 2, 'start_time' => '07:00', 'end_time' => '08:00', 'planned_jp' => 1, 'reason' => 'Entri Selasa tanpa benturan'], $headers)->assertCreated();
        $this->postJson('/api/v1/academic/timetable-versions/'.$conflict['id'].'/publish', ['reason' => 'Terbitkan setelah benturan dihapus'], $headers)->assertOk();
    }

    public function test_lesson_slots_and_slot_derived_entries(): void
    {
        [, $headers] = $this->operator();
        $seed = $this->seedPublishedSchedule($headers);
        $semester = $seed['semester'];

        $first = $this->postJson('/api/v1/academic/lesson-slots', ['semester_id' => $semester['id'], 'day_of_week' => 1, 'label' => 'Jam 1', 'start_time' => '07:00', 'end_time' => '07:40', 'jp_units' => 1, 'reason' => 'Penambahan jam pelajaran pertama'], $headers)->assertCreated()->json('data');
        $second = $this->postJson('/api/v1/academic/lesson-slots', ['semester_id' => $semester['id'], 'day_of_week' => 1, 'label' => 'Jam 2', 'start_time' => '07:40', 'end_time' => '08:20', 'jp_units' => 1, 'reason' => 'Penambahan jam pelajaran kedua'], $headers)->assertCreated()->json('data');
        $this->assertSame(2, $second['sequence']);
        $this->getJson('/api/v1/academic/lesson-slots?semester_id='.$semester['id'].'&day_of_week=1', $headers)->assertOk()->assertJsonCount(2, 'data');

        $draft = $this->postJson('/api/v1/academic/timetables/'.$seed['timetable']['id'].'/versions', ['effective_from' => '2026-11-02', 'reason' => 'Draf entri berbasis slot'], $headers)->assertCreated()->json('data');
        $entry = $this->postJson('/api/v1/academic/timetable-versions/'.$draft['id'].'/entries', ['teaching_assignment_id' => $seed['assignment']['id'], 'lesson_slot_ids' => [$first['id'], $second['id']], 'reason' => 'Entri dari dua jam pelajaran'], $headers)->assertCreated()->json('data');
        $this->assertSame('07:00:00', substr($entry['start_time_snapshot'], 0, 8));
        $this->assertSame('08:20:00', substr($entry['end_time_snapshot'], 0, 8));
        $this->assertEquals(2, $entry['planned_jp']);
        $this->assertSame(80, $entry['planned_minutes']);
    }

    public function test_meeting_generation_respects_calendar_and_teacher_scope(): void
    {
        [, $headers] = $this->operator();
        $person = Person::factory()->create();
        $teacher = User::factory()->for($person)->create();
        $this->grant($teacher, 'guru');
        $seed = $this->seedPublishedSchedule($headers, '07:30', $this->employee($person->id));
        $version = $seed['version'];

        $this->postJson('/api/v1/academic/calendar', ['academic_year_id' => $seed['year']['id'], 'semester_id' => $seed['semester']['id'], 'title' => 'Pekan penyegaran', 'event_type' => 'holiday', 'start_date' => '2026-07-20', 'end_date' => '2026-07-24', 'blocks_teaching' => true, 'reason' => 'Libur penyegaran semester ganjil'], $headers)->assertCreated();

        $result = $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/generate-meetings', ['date_from' => '2026-07-13', 'date_to' => '2026-07-31', 'reason' => 'Pembuatan pertemuan Juli'], $headers)->assertOk()->json('data');
        $this->assertSame(2, $result['created']); // 13 & 27 Juli; 20 Juli diblokir kalender.
        $this->assertSame(1, $result['blocked']);
        $again = $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/generate-meetings', ['date_from' => '2026-07-13', 'date_to' => '2026-07-31', 'reason' => 'Generator idempoten'], $headers)->assertOk()->json('data');
        $this->assertSame(0, $again['created']);
        $this->assertSame(2, $again['skipped_existing']);

        $meetings = $this->getJson('/api/v1/academic/meetings?semester_id='.$seed['semester']['id'].'&date_from=2026-07-13&date_to=2026-07-31', $headers)->assertOk()->json('data');
        $this->assertSame(['2026-07-13', '2026-07-27'], array_column($meetings, 'meeting_date'));
        $this->assertSame('07:30:00', substr($meetings[0]['start_time_snapshot'], 0, 8));
        $this->assertNotNull($meetings[0]['classPeriod']);

        // Guru terjadwal melihat pertemuannya; guru lain tidak (Bab II).
        $teacherHeaders = ['Authorization' => 'Bearer '.$this->loginUser($teacher)['access_token']];
        $scoped = $this->getJson('/api/v1/academic/meetings?date_from=2026-07-13&date_to=2026-07-31', $teacherHeaders)->assertOk()->json('data');
        $this->assertCount(2, $scoped);

        $stranger = User::factory()->create();
        $this->grant($stranger, 'guru');
        $strangerHeaders = ['Authorization' => 'Bearer '.$this->loginUser($stranger)['access_token']];
        $this->getJson('/api/v1/academic/meetings?date_from=2026-07-13&date_to=2026-07-31', $strangerHeaders)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_substitute_assignment_revoke_and_meeting_cancellation(): void
    {
        [, $headers] = $this->operator();
        $seed = $this->seedPublishedSchedule($headers);
        $version = $seed['version'];
        $this->postJson('/api/v1/academic/timetable-versions/'.$version['id'].'/generate-meetings', ['date_from' => '2026-07-13', 'date_to' => '2026-07-13', 'reason' => 'Satu pertemuan untuk uji pengganti'], $headers)->assertOk();
        $meeting = $this->getJson('/api/v1/academic/meetings?date_from=2026-07-13&date_to=2026-07-13', $headers)->assertOk()->json('data')[0];

        $substituteId = $this->employee();
        $this->postJson('/api/v1/academic/meetings/'.$meeting['id'].'/substitute', ['substitute_employee_id' => $seed['employee_id'], 'reason' => 'Pengganti sama dengan guru terjadwal ditolak'], $headers)->assertStatus(422);
        $assigned = $this->postJson('/api/v1/academic/meetings/'.$meeting['id'].'/substitute', ['substitute_employee_id' => $substituteId, 'reason' => 'Guru berhalangan dinas luar kota'], $headers)->assertCreated()->json('data');

        // Penunjukan baru menutup pengganti aktif lama (riwayat tetap utuh).
        $replacement = $this->postJson('/api/v1/academic/meetings/'.$meeting['id'].'/substitute', ['substitute_employee_id' => $this->employee(), 'reason' => 'Pengganti pertama berhalangan lanjut'], $headers)->assertCreated()->json('data');
        $this->assertNotNull(DB::table('substitute_assignments')->where('id', $assigned['id'])->value('revoked_at'));

        // Benturan pengganti: guru terjadwal kelas lain pada jam yang sama.
        $otherClass = $this->postJson('/api/v1/academic/classes', ['code' => '7B', 'name' => 'Kelas 7B', 'grade' => 7, 'reason' => 'Rombel pembanding pengganti'], $headers)->assertCreated()->json('data');
        $otherPeriod = $this->postJson('/api/v1/academic/class-periods', ['class_id' => $otherClass['id'], 'academic_year_id' => $seed['year']['id'], 'display_name' => 'VII-B 2026/2027', 'reason' => 'Periode rombel pembanding'], $headers)->assertCreated()->json('data');
        $otherAssignment = $this->postJson('/api/v1/academic/teaching-assignments', ['employee_id' => $substituteId, 'subject_id' => $this->subject('BIO'), 'class_period_id' => $otherPeriod['id'], 'semester_id' => $seed['semester']['id'], 'effective_from' => '2026-07-13', 'reason' => 'Penugasan pembanding jam sama'], $headers)->assertCreated()->json('data');
        $otherTimetable = $this->postJson('/api/v1/academic/timetables', ['semester_id' => $seed['semester']['id'], 'code' => '7B', 'name' => 'Jadwal kelas 7B', 'reason' => 'Jadwal pembanding'], $headers)->assertCreated()->json('data');
        $otherVersion = $this->postJson('/api/v1/academic/timetables/'.$otherTimetable['id'].'/versions', ['effective_from' => '2026-07-13', 'reason' => 'Draf jadwal 7B'], $headers)->assertCreated()->json('data');
        $this->postJson('/api/v1/academic/timetable-versions/'.$otherVersion['id'].'/entries', ['teaching_assignment_id' => $otherAssignment['id'], 'day_of_week' => 1, 'start_time' => '07:30', 'end_time' => '08:50', 'planned_jp' => 2, 'reason' => 'Entri 7B jam sama'], $headers)->assertCreated();
        $this->postJson('/api/v1/academic/timetable-versions/'.$otherVersion['id'].'/publish', ['reason' => 'Terbitkan jadwal 7B'], $headers)->assertOk();
        $this->postJson('/api/v1/academic/timetable-versions/'.$otherVersion['id'].'/generate-meetings', ['date_from' => '2026-07-13', 'date_to' => '2026-07-13', 'reason' => 'Pertemuan 7B'], $headers)->assertOk();
        $otherMeeting = $this->getJson('/api/v1/academic/meetings?class_period_id='.$otherPeriod['id'], $headers)->assertOk()->json('data')[0];
        $conflictingTeacher = $this->employee();
        $this->postJson('/api/v1/academic/meetings/'.$otherMeeting['id'].'/substitute', ['substitute_employee_id' => $seed['employee_id'], 'reason' => 'Pengganti bentrok dengan jadwal sendiri'], $headers)->assertStatus(422);

        $substitutes = $this->getJson('/api/v1/academic/substitute-assignments?active_only=1', $headers)->assertOk()->json('data');
        $this->assertCount(1, $substitutes);
        $this->assertSame($replacement['id'], $substitutes[0]['id']);
        $this->assertNotNull($substitutes[0]['meeting']);
        $this->assertNotNull($substitutes[0]['substituteEmployee']);

        $this->postJson('/api/v1/academic/substitute-assignments/'.$replacement['id'].'/revoke', ['reason' => 'Guru pengganti membatalkan kesediaan'], $headers)->assertOk();
        $this->postJson('/api/v1/academic/substitute-assignments/'.$replacement['id'].'/revoke', ['reason' => 'Pencabutan ganda ditolak'], $headers)->assertStatus(422);

        $final = $this->postJson('/api/v1/academic/meetings/'.$meeting['id'].'/substitute', ['substitute_employee_id' => $conflictingTeacher, 'reason' => 'Penunjukan pengganti berikutnya'], $headers)->assertCreated()->json('data');
        $this->postJson('/api/v1/academic/meetings/'.$meeting['id'].'/cancel', ['reason' => 'Kegiatan madrasah meniadakan pertemuan', 'expected_updated_at' => $meeting['updated_at']], $headers)->assertOk()->assertJsonPath('data.is_cancelled', true);
        $this->assertNotNull(DB::table('substitute_assignments')->where('id', $final['id'])->value('revoked_at'));
        $this->postJson('/api/v1/academic/meetings/'.$meeting['id'].'/substitute', ['substitute_employee_id' => $this->employee(), 'reason' => 'Pertemuan batal tidak boleh digantikan'], $headers)->assertStatus(422);
    }

    private function entryIdOf(int $versionId): int
    {
        return DB::table('schedule_entry_versions')->where('timetable_version_id', $versionId)->value('id');
    }
}
