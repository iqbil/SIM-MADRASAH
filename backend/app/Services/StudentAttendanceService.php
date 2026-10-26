<?php

namespace App\Services;

use App\Models\AttendanceCorrection;
use App\Models\AttendancePolicy;
use App\Models\AttendanceStatus;
use App\Models\BusinessDecision;
use App\Models\ClassPeriod;
use App\Models\Semester;
use App\Models\StudentDailyAttendance;
use App\Models\StudentEnrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Absensi siswa harian (SRS v1.3 FR-07, DEC-07, BR-08/09, AC-10). Baseline
 * hanya mode harian: guru/wali kelas tercantum sebagai pencatat untuk kelas
 * penugasannya sebelum 23.59 waktu lokal; tidak ada baris berarti belum
 * dicatat, bukan alpa; koreksi disetujui petugas akademik berbeda, lewat
 * 7 hari hanya petugas bermandat khusus.
 */
class StudentAttendanceService
{
    public function __construct(private AccountManagementService $audit) {}

    /** Pencatat sah: token students.attendance + kelas dalam cakupan penugasan. */
    public function authorizeRecorder(User $user): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(in_array('students.attendance', NavigationAccess::forUser($user), true), 403,
            'Akses absensi siswa tidak diberikan untuk peran Anda.');
    }

    /** Kelas yang boleh dicatat pengguna: cakupan penugasan mengajar/wali kelas. @return array<int, int>|null */
    private function dutyScope(User $user): array
    {
        return AcademicDataAccess::classPeriodScope($user);
    }

    private function authorizeClass(User $user, ClassPeriod $period, ?string $date = null): AttendancePolicy
    {
        $this->authorizeRecorder($user);
        $scope = $this->dutyScope($user);
        abort_unless($scope === null || in_array($period->id, $scope, true), 404);

        return $this->effectivePolicy($user, $period, $date);
    }

    /** Policy harian semester kelas pada tanggal; baseline dibuat idempoten bila belum ada. */
    public function effectivePolicy(User $actor, ClassPeriod $period, ?string $date = null): AttendancePolicy
    {
        $date ??= today('Asia/Jakarta')->toDateString();
        $semester = Semester::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('start_date', '<=', $date)->where('end_date', '>=', $date)
            ->orderByDesc('start_date')->first();
        abort_unless($semester !== null, 422, 'Tanggal di luar semester kelas tersebut.');

        $policy = AttendancePolicy::query()->where('semester_id', $semester->id)
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('effective_from')->first();
        if ($policy !== null) {
            return $policy;
        }
        $decision = BusinessDecision::query()->where('code', 'DEC-07')->orderByDesc('version')->first();
        if ($decision === null) {
            $decision = new BusinessDecision([
                'code' => 'DEC-07', 'version' => 1, 'title' => 'Kebijakan absensi siswa harian',
                'status' => 'baseline',
                'decision_text' => 'Absensi siswa harian; guru/wali kelas yang ditugaskan mengisi sebelum 23.59 waktu lokal. Tidak ada baris berarti belum dicatat, bukan alpa. Koreksi sampai 7 hari kalender disetujui petugas akademik berbeda; sesudahnya hanya petugas berizin khusus dengan alasan. Mode pertemuan/both nonaktif.',
                'source_reference' => 'SRS v1.3 FR-07 / DEC-07 baseline', 'effective_from' => today('Asia/Jakarta')->toDateString(),
            ]);
            $decision->forceFill(['recorded_at' => now('UTC')])->save();
        }
        $policy = new AttendancePolicy([
            'semester_id' => $semester->id, 'version_number' => 1, 'status' => 'baseline', 'mode' => 'daily',
            'input_deadline_time' => '23:59:00', 'correction_deadline_days' => 7,
            'requires_correction_approval' => true, 'allow_late_correction_with_mandate' => true,
            'effective_from' => $semester->start_date->toDateString(), 'decision_id' => $decision->id,
        ]);
        $policy->save();

        return $policy;
    }

    /** Daftar kelas boleh-catat + ringkas status pengisian hari ini. @return array<int, array<string, mixed>> */
    public function classes(User $actor): array
    {
        $this->authorizeRecorder($actor);
        $scope = $this->dutyScope($actor);
        $today = today('Asia/Jakarta')->toDateString();
        $query = ClassPeriod::query()->with('schoolClass');
        if ($scope !== null) {
            $query->whereKey($scope === [] ? [-1] : $scope);
        }
        $rows = [];
        foreach ($query->orderBy('display_name')->get() as $period) {
            $roster = $this->rosterQuery($period->id, $today)->get();
            $rows[] = [...$period->only(['id', 'class_id', 'display_name', 'status']),
                'class_name' => $period->schoolClass?->name,
                'roster' => $roster->count(),
                'recorded' => $roster->where('attendance', '!==', null)->count()];
        }

        return $rows;
    }

    /** Query roster: keanggotaan aktif pada tanggal + baris absensi (bila ada). */
    private function rosterQuery(int $periodId, string $date): Builder
    {
        return StudentEnrollment::query()
            ->where('student_enrollments.class_period_id', $periodId)
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $date))
            ->with(['student.person'])
            ->leftJoin('student_daily_attendances', fn ($join) => $join
                ->on('student_daily_attendances.enrollment_id', '=', 'student_enrollments.id')
                ->where('student_daily_attendances.attendance_date', '=', $date)
                ->whereColumn('student_daily_attendances.student_id', '=', 'student_enrollments.student_id'))
            ->select('student_enrollments.*')
            ->addSelect('student_daily_attendances.id as attendance_id', 'student_daily_attendances.attendance_status_id',
                'student_daily_attendances.lock_version as attendance_lock_version', 'student_daily_attendances.notes as attendance_notes')
            ->orderBy('student_enrollments.effective_from')
            ->orderBy('student_enrollments.student_id');
    }

    /** Roster satu kelas pada tanggal untuk form pengisian. @param array<string, mixed> $params */
    public function roster(User $actor, array $params): array
    {
        $data = Validator::make($params, [
            'class_period_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d'],
        ])->validate();
        $period = ClassPeriod::query()->findOrFail($data['class_period_id']);
        $policy = $this->authorizeClass($actor, $period, $data['date']);
        $statuses = AttendanceStatus::query()->where('domain', 'student')->where('is_active', true)
            ->orderBy('id')->get(['id', 'code', 'name']);
        $statusById = $statuses->keyBy('id');
        $rows = $this->rosterQuery($period->id, $data['date'])->get()->map(function (StudentEnrollment $enrollment) use ($statusById) {
            return [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'student' => ['id' => $enrollment->student_id, 'person' => $enrollment->student?->person?->only(['id', 'full_name'])],
                'attendance' => $enrollment->attendance_id === null ? null : [
                    'id' => $enrollment->attendance_id,
                    'status' => $statusById[$enrollment->attendance_status_id]?->only(['code', 'name']),
                    'lock_version' => $enrollment->attendance_lock_version,
                    'notes' => $enrollment->attendance_notes,
                ],
            ];
        })->values();

        return ['class_period' => $period->only(['id', 'display_name']), 'date' => $data['date'],
            'policy' => $policy->only(['mode', 'input_deadline_time', 'correction_deadline_days']),
            'statuses' => $statuses->toArray(), 'rows' => $rows->all()];
    }

    /** Simpan pengisian harian satu kelas-tanggal (FR-07-01/02, AC-10). @param array<string, mixed> $data */
    public function record(User $actor, array $data, Request $request): array
    {
        try {
            return $this->doRecord($actor, $data, $request);
        } catch (UniqueConstraintViolationException) {
            abort(409, 'Sebagian siswa sudah tercatat pada tanggal ini (unik siswa-tanggal); muat ulang daftar lalu lanjutkan siswa yang belum tercatat.');
        }
    }

    /** @param array<string, mixed> $data */
    private function doRecord(User $actor, array $data, Request $request): array
    {
        return DB::transaction(function () use ($actor, $data, $request): array {
            $values = Validator::make($data, [
                'class_period_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d'],
                'entries' => ['required', 'array', 'min:1'],
                'entries.*.student_id' => ['required', 'integer', 'distinct'],
                'entries.*.status_code' => ['required', 'string'],
                'entries.*.notes' => ['nullable', 'string', 'max:1000'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $period = ClassPeriod::query()->lockForUpdate()->findOrFail($values['class_period_id']);
            $policy = $this->authorizeClass($actor, $period, $values['date']);
            abort_unless($policy->mode === 'daily', 422, 'Mode absensi per pertemuan/both nonaktif pada kebijakan ini.');
            $today = today('Asia/Jakarta')->toDateString();
            $officer = AttendanceDataAccess::allows($actor, 'attendance.manage');
            if (! $officer) {
                // Guru/wali mengisi hari itu sebelum tenggat 23.59 lokal (DEC-07);
                // hari yang lampau harus melalui koreksi oleh petugas.
                abort_unless($values['date'] === $today, 422, 'Pengisian hanya untuk hari ini sebelum 23.59; hari lampau melalui koreksi oleh petugas.');
            }
            $deadline = CarbonImmutable::parse($values['date'].' '.$policy->input_deadline_time, 'Asia/Jakarta');
            abort_unless(now('Asia/Jakarta') <= $deadline || $officer, 422,
                'Tenggat pengisian '.$policy->input_deadline_time.' WIB telah lewat; gunakan koreksi melalui petugas.');

            $statusByCode = AttendanceStatus::query()->where('domain', 'student')->where('is_active', true)
                ->get()->keyBy('code');
            foreach ($values['entries'] as $entry) {
                abort_unless(isset($statusByCode[$entry['status_code']]), 422, 'Status absensi tidak dikenal: '.$entry['status_code'].'.');
            }
            $roster = $this->rosterQuery($period->id, $values['date'])->lockForUpdate()->get()->keyBy('student_id');
            $unknown = array_diff(array_column($values['entries'], 'student_id'), $roster->keys()->all());
            abort_unless($unknown === [], 422, 'Siswa bukan anggota kelas pada tanggal tersebut: #'.implode(', #', $unknown).'.');
            $duplicates = [];
            foreach ($values['entries'] as $entry) {
                if ($roster[$entry['student_id']]->attendance_id !== null) {
                    $duplicates[] = $roster[$entry['student_id']]->student?->person?->full_name ?? '#'.$entry['student_id'];
                }
            }
            abort_unless($duplicates === [], 422, 'Sudah tercatat pada tanggal ini (gunakan koreksi): '.implode(', ', $duplicates).'.');

            $created = 0;
            foreach ($values['entries'] as $entry) {
                $enrollment = $roster[$entry['student_id']];
                $row = new StudentDailyAttendance([
                    'student_id' => $enrollment->student_id, 'attendance_date' => $values['date'],
                    'class_period_id' => $period->id, 'enrollment_id' => $enrollment->id,
                    'attendance_status_id' => $statusByCode[$entry['status_code']]->id, 'policy_id' => $policy->id,
                    'source_type' => 'manual', 'notes' => $entry['notes'] ?? null,
                ]);
                $row->forceFill(['recorded_by' => $actor->id, 'recorded_at' => now('UTC')])->save();
                $created++;
            }
            $this->audit->audit($actor, 'students.attendance.recorded', 'student_daily_attendance', 0, $values['reason'], null,
                ['class_period_id' => $period->id, 'date' => $values['date'], 'created' => $created], $request);

            return ['created' => $created, 'date' => $values['date']];
        });
    }

    /** Rekap per tanggal & per siswa (FR-07-03); wali kelas hanya kelas penugasannya. @param array<string, mixed> $params */
    public function recap(User $actor, array $params): array
    {
        $this->authorizeRecorder($actor);
        $data = Validator::make($params, [
            'class_period_id' => ['required', 'integer'],
            'date_from' => ['required', 'date_format:Y-m-d'], 'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ])->validate();
        $period = ClassPeriod::query()->with('schoolClass')->findOrFail($data['class_period_id']);
        $this->authorizeClass($actor, $period, $data['date_from']);

        $byDate = [];
        $byStudent = [];
        $cursor = CarbonImmutable::parse($data['date_from'], 'Asia/Jakarta');
        $end = CarbonImmutable::parse($data['date_to'], 'Asia/Jakarta');
        for ($date = $cursor; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $iso = $date->toDateString();
            $roster = $this->rosterQuery($period->id, $iso)->get();
            $counts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'belum_dicatat' => 0];
            foreach ($roster as $enrollment) {
                $studentKey = $enrollment->student_id;
                $byStudent[$studentKey] ??= ['student_id' => $studentKey,
                    'student' => $enrollment->student?->person?->only(['id', 'full_name']),
                    'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'belum_dicatat' => 0, 'hari_aktif' => 0];
                $byStudent[$studentKey]['hari_aktif']++;
                if ($enrollment->attendance_id === null) {
                    $counts['belum_dicatat']++;
                    $byStudent[$studentKey]['belum_dicatat']++;

                    continue;
                }
                $code = $this->statusCache($enrollment->attendance_status_id);
                if (! array_key_exists($code, $counts)) {
                    continue;
                }
                $counts[$code]++;
                $byStudent[$studentKey][$code]++;
            }
            $byDate[] = ['date' => $iso, ...$counts, 'roster' => $roster->count()];
        }

        return ['class_period' => $period->only(['id', 'display_name']),
            'range' => ['from' => $data['date_from'], 'to' => $data['date_to']],
            'by_date' => $byDate, 'by_student' => array_values($byStudent)];
    }

    /** @var array<int, string> */
    private array $statusCodes = [];

    private function statusCache(int $statusId): string
    {
        if (! isset($this->statusCodes[$statusId])) {
            $this->statusCodes[$statusId] = AttendanceStatus::query()->whereKey($statusId)->value('code') ?? '';
        }

        return $this->statusCodes[$statusId];
    }

    // ------------------------------------------------------------------
    // Koreksi (DEC-07): pemohon ≠ penyetuju; >7 hari hanya pemegang mandat.
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function requestCorrection(User $actor, array $data, Request $request): AttendanceCorrection
    {
        return DB::transaction(function () use ($actor, $data, $request): AttendanceCorrection {
            $this->authorizeRecorder($actor);
            $values = Validator::make($data, [
                'student_daily_attendance_id' => ['required', 'integer'],
                'after_values' => ['required', 'array', 'min:1'],
                'after_values.status_code' => ['required', 'string'],
                'after_values.notes' => ['nullable', 'string', 'max:1000'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
                'expected_lock_version' => ['required', 'integer'],
            ])->validate();
            $row = StudentDailyAttendance::query()->lockForUpdate()->findOrFail($values['student_daily_attendance_id']);
            abort_unless($row->lock_version === $values['expected_lock_version'], 409, 'Data absensi telah berubah; muat ulang lalu ajukan ulang.');
            $this->authorizeClass($actor, $row->classPeriod, $row->attendance_date->toDateString());
            $statusByCode = AttendanceStatus::query()->where('domain', 'student')->where('is_active', true)->get()->keyBy('code');
            abort_unless(isset($statusByCode[$values['after_values']['status_code']]), 422, 'Status absensi tidak dikenal.');

            $policy = $this->effectivePolicy($actor, $row->classPeriod, $row->attendance_date->toDateString());
            $deadline = CarbonImmutable::parse($row->attendance_date->toDateString(), 'Asia/Jakarta')
                ->addDays($policy->correction_deadline_days)->endOfDay();
            $mandated = AttendanceDataAccess::correctMandate($actor) !== null;
            abort_unless(now('Asia/Jakarta') <= $deadline || $mandated, 422,
                'Batas koreksi '.$policy->correction_deadline_days.' hari kalender telah terlewat; hanya petugas bermandat khusus yang dapat mengajukan.');

            $before = ['status_code' => $this->statusCache($row->attendance_status_id), 'notes' => $row->notes];
            $correction = new AttendanceCorrection([
                'student_daily_attendance_id' => $row->id,
                'before_values' => $before, 'after_values' => $values['after_values'],
                'reason' => $values['reason'], 'requested_at' => now('UTC'), 'status' => 'pending',
                'expected_lock_version' => $row->lock_version, 'operation_key' => 'student-attendance-correction:'.Str::uuid(),
            ]);
            $correction->forceFill(['requested_by' => $actor->id])->save();
            $this->audit->audit($actor, 'students.attendance.correction_requested', 'attendance_correction', $correction->id,
                $values['reason'], null, ['student_daily_attendance_id' => $row->id], $request);

            return $correction;
        });
    }

    /** @param array<string, mixed> $data */
    public function decideCorrection(User $actor, int $correctionId, array $data, Request $request): AttendanceCorrection
    {
        return DB::transaction(function () use ($actor, $correctionId, $data, $request): AttendanceCorrection {
            AttendanceDataAccess::authorizeCorrect($actor);
            $values = Validator::make($data, [
                'decision' => ['required', 'in:approve,reject'], 'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $correction = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correctionId);
            abort_unless($correction->student_daily_attendance_id !== null, 422, 'Ini bukan koreksi absensi siswa.');
            abort_unless($correction->status === 'pending', 422, 'Koreksi ini sudah diputuskan.');
            abort_if($correction->requested_by === $actor->id, 422, 'Pemohon dan penyetuju tidak boleh orang sama.');

            if ($values['decision'] === 'reject') {
                $correction->forceFill(['status' => 'rejected', 'rejection_reason' => $values['reason'],
                    'approved_at' => now('UTC'), 'approved_by' => $actor->id])->save();
                $this->audit->audit($actor, 'students.attendance.correction_rejected', 'attendance_correction', $correction->id,
                    $values['reason'], ['status' => 'pending'], ['status' => 'rejected'], $request);

                return $correction;
            }
            $row = StudentDailyAttendance::query()->lockForUpdate()->findOrFail($correction->student_daily_attendance_id);
            abort_unless($row->lock_version === $correction->expected_lock_version, 409,
                'Data absensi berubah setelah koreksi diajukan; minta pengajuan ulang.');
            $status = AttendanceStatus::query()->where('domain', 'student')
                ->where('code', $correction->after_values['status_code'] ?? '')->firstOrFail();
            $row->attendance_status_id = $status->id;
            if (array_key_exists('notes', $correction->after_values)) {
                $row->notes = $correction->after_values['notes'];
            }
            $row->lock_version += 1;
            $row->save();
            $correction->forceFill(['status' => 'applied', 'approved_at' => now('UTC'), 'applied_at' => now('UTC'), 'approved_by' => $actor->id])->save();
            $this->audit->audit($actor, 'students.attendance.correction_applied', 'attendance_correction', $correction->id,
                $values['reason'], ['status' => 'pending'],
                ['status' => 'applied', 'student_daily_attendance_lock_version' => $row->lock_version], $request);

            return $correction;
        });
    }

    /** Daftar koreksi siswa: pemegang mandat melihat semua; guru/wali kelas penugasannya. */
    public function corrections(User $actor, array $params): array
    {
        $data = Validator::make($params, [
            'status' => ['nullable', 'in:pending,applied,rejected'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();
        $mandated = AttendanceDataAccess::correctMandate($actor) !== null;
        abort_unless($mandated || in_array('students.attendance', NavigationAccess::forUser($actor), true), 403);
        $query = AttendanceCorrection::query()
            ->whereNotNull('student_daily_attendance_id')
            ->with(['studentDailyAttendance.student.person', 'studentDailyAttendance.classPeriod', 'requester.person', 'approver.person'])
            ->orderByDesc('requested_at');
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! $mandated) {
            $scope = $this->dutyScope($actor) ?? [];
            $query->whereHas('studentDailyAttendance.classPeriod', fn (Builder $q): Builder => $q->whereKey($scope === [] ? [-1] : $scope));
        }
        $page = $query->paginate($data['per_page'] ?? 25)->withQueryString();

        $rows = $page->toArray();
        $rows['meta'] = [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];

        return ['rows' => $rows];
    }
}
