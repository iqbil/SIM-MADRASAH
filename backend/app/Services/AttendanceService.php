<?php

namespace App\Services;

use App\Models\AcademicCalendarEvent;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceStatus;
use App\Models\BusinessDecision;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceCapture;
use App\Models\EmployeeAttendancePolicy;
use App\Models\EmployeeWorkAssignment;
use App\Models\EmployeeWorkDay;
use App\Models\File;
use App\Models\User;
use App\Models\WorkPattern;
use App\Models\WorkPatternVersion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Absensi pegawai (SRS v1.3 FR-05). Aksi masuk/pulang normal wajib foto kamera
 * baru + GPS perangkat dengan kunci idempotensi (FR-05-04); petugas mencatat
 * izin/sakit/cuti/dinas luar tanpa bukti (FR-05-01) dan SOP fallback
 * administratif (FR-05-07). Koreksi menyimpan sebelum/sesudah dengan approval
 * petugas berbeda (FR-05-02/03). Waktu yang dicatat adalah waktu server;
 * shift dinilai pada zona Asia/Jakarta (DEC-16).
 */
class AttendanceService
{
    public function __construct(private AccountManagementService $audit, private AttendancePhotoPipeline $photos) {}

    // ------------------------------------------------------------------
    // Baseline & hari kerja
    // ------------------------------------------------------------------

    /** Idempoten: pastikan keputusan DEC-16, pola kerja bawaan, dan policy v1 ada. */
    public function ensureBaseline(User $actor): void
    {
        $decision = BusinessDecision::query()->where('code', 'DEC-16')->orderByDesc('version')->first();
        if ($decision === null) {
            $decision = new BusinessDecision([
                'code' => 'DEC-16', 'version' => 1, 'title' => 'Pola kerja dan absensi pegawai',
                'status' => 'baseline',
                'decision_text' => 'Satu shift Senin–Sabtu 07.00–14.00 Asia/Jakarta; Minggu/libur tidak wajib kerja. Toleransi terlambat 10 menit. Petugas mencatat izin/sakit/cuti/dinas luar; koreksi 7 hari dengan persetujuan petugas berbeda. Biometrik nonaktif.',
                'source_reference' => 'SRS v1.3 FR-05 / DEC-16 baseline',
                'effective_from' => today('Asia/Jakarta')->toDateString(),
            ]);
            $decision->forceFill(['recorded_at' => now('UTC')])->save();
        }

        $pattern = WorkPattern::query()->where('code', 'DEFAULT')->first();
        if ($pattern === null) {
            $pattern = new WorkPattern(['code' => 'DEFAULT', 'name' => 'Pola kerja bawaan madrasah']);
            $pattern->save();
            foreach (range(1, 6) as $day) {
                $version = new WorkPatternVersion([
                    'work_pattern_id' => $pattern->id, 'version_number' => 1, 'day_of_week' => $day,
                    'start_time' => '07:00', 'end_time' => '14:00', 'crosses_midnight' => false,
                    'late_tolerance_minutes' => 10, 'effective_from' => today('Asia/Jakarta')->toDateString(),
                    'decision_id' => $decision->id,
                ]);
                $version->forceFill(['created_by' => $actor->id])->save();
            }
        }

        if (EmployeeAttendancePolicy::query()->count() === 0) {
            $policy = new EmployeeAttendancePolicy([
                'code' => 'EMP-ATTENDANCE', 'version_number' => 1, 'status' => 'baseline',
                'correction_deadline_days' => 7, 'requires_correction_approval' => true,
                'allow_late_correction_with_mandate' => false,
                'effective_from' => today('Asia/Jakarta')->toDateString(), 'decision_id' => $decision->id,
                'requires_photo' => true, 'requires_gps' => true,
            ]);
            $policy->save();
        }
    }

    public function effectivePolicy(?User $actor = null): EmployeeAttendancePolicy
    {
        $today = today('Asia/Jakarta')->toDateString();
        $policy = EmployeeAttendancePolicy::query()
            ->where('effective_from', '<=', $today)
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today))
            ->orderByDesc('effective_from')->first();
        if ($policy === null && $actor !== null) {
            $this->ensureBaseline($actor);
            $policy = EmployeeAttendancePolicy::query()->orderByDesc('effective_from')->firstOrFail();
        }
        abort_unless($policy !== null, 500, 'Kebijakan absensi belum dikonfigurasi; minta petugas membuka modul absensi sekali.');

        return $policy;
    }

    /**
     * Pastikan hari kerja pegawai pada tanggal (Asia/Jakarta) ada sesuai pola
     * kerja; null bila bukan hari kerja. Libur kalender tetap dibuat dengan
     * is_work_required=false agar tidak ditafsirkan alpa.
     */
    public function ensureWorkDay(int $employeeId, string $date, ?User $actor = null): ?EmployeeWorkDay
    {
        $existing = EmployeeWorkDay::query()->where('employee_id', $employeeId)->where('work_date', $date)->where('shift_number', 1)->first();
        if ($existing !== null) {
            return $existing;
        }
        $assignment = EmployeeWorkAssignment::query()->where('employee_id', $employeeId)
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('effective_from')->first();
        if ($assignment === null) {
            return null;
        }
        $dayOfWeek = (int) CarbonImmutable::parse($date, 'Asia/Jakarta')->dayOfWeekIso;
        $version = WorkPatternVersion::query()->where('work_pattern_id', $assignment->work_pattern_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('version_number')->first();
        if ($version === null) {
            return null;
        }
        $event = AcademicCalendarEvent::query()->where('blocks_employee_work', true)
            ->where('start_date', '<=', $date)->where('end_date', '>=', $date)->first(['id']);

        $day = new EmployeeWorkDay([
            'employee_id' => $employeeId, 'work_assignment_id' => $assignment->id,
            'work_pattern_version_id' => $version->id, 'policy_id' => $this->effectivePolicy($actor)->id,
            'work_date' => $date, 'shift_number' => 1,
            'scheduled_start_at' => CarbonImmutable::parse($date.' '.$version->start_time, 'Asia/Jakarta')->utc(),
            'scheduled_end_at' => CarbonImmutable::parse($date.' '.$version->end_time, 'Asia/Jakarta')->utc(),
            'tolerance_minutes_snapshot' => $version->late_tolerance_minutes,
            'calendar_event_id' => $event?->id, 'is_work_required' => $event === null,
        ]);
        $day->save();

        return $day->fresh();
    }

    // ------------------------------------------------------------------
    // Aksi mandiri (FR-05-01/04/05)
    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function status(User $actor): array
    {
        $employee = AttendanceDataAccess::authorizeOwn($actor);
        $today = today('Asia/Jakarta')->toDateString();
        $workDay = $this->ensureWorkDay($employee->id, $today, $actor);
        $attendance = $workDay ? EmployeeAttendance::query()->where('work_day_id', $workDay->id)->with(['attendanceStatus', 'captures.photoFile'])->first() : null;
        $next = null;
        if ($workDay !== null && $workDay->is_work_required) {
            $next = $attendance === null ? 'check_in' : ($attendance->check_out_at === null ? 'check_out' : null);
        }
        $policy = $this->effectivePolicy($actor);

        return [
            'today' => $today,
            'employee' => ['id' => $employee->id, 'employee_number' => $employee->employee_number, 'person' => $employee->person?->only(['id', 'full_name'])],
            'work_day' => $workDay?->only(['id', 'work_date', 'scheduled_start_at', 'scheduled_end_at', 'tolerance_minutes_snapshot', 'is_work_required', 'calendar_event_id']),
            'attendance' => $attendance?->only(['id', 'check_in_at', 'check_out_at', 'late_minutes', 'entry_mode', 'notes', 'lock_version', 'updated_at']),
            'attendance_status' => $attendance?->attendanceStatus?->only(['code', 'name']),
            'next_action' => $next,
            'policy' => $policy->only(['id', 'requires_photo', 'requires_gps', 'photo_max_dimension', 'photo_quality', 'photo_max_size_bytes', 'gps_max_accuracy_meters', 'gps_max_age_seconds', 'correction_deadline_days']),
        ];
    }

    /** @param array<string, mixed> $data */
    public function clock(User $actor, array $data, Request $request): array
    {
        $employee = AttendanceDataAccess::authorizeOwn($actor);
        $policy = $this->effectivePolicy($actor);
        $values = Validator::make($data, [
            'action' => ['required', 'in:check_in,check_out'],
            'operation_key' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['required', 'numeric', 'min:0'],
            'photo_taken_at' => ['required', 'date'],
            'location_captured_at' => ['required', 'date'],
        ])->validate();
        abort_unless($policy->requires_photo && $policy->requires_gps, 422, 'Kebijakan foto/GPS tidak aktif; hubungi petugas absensi.');

        $replay = EmployeeAttendanceCapture::query()->where('operation_key', $values['operation_key'])->first();
        if ($replay !== null) {
            return [...$this->status($actor), 'replayed' => true];
        }

        $now = now('UTC');
        $photoTakenAt = CarbonImmutable::parse($values['photo_taken_at']);
        $locationAt = CarbonImmutable::parse($values['location_captured_at']);
        abort_unless($now->diffInSeconds($photoTakenAt, true) <= $policy->gps_max_age_seconds, 422,
            'Foto lebih tua dari '.$policy->gps_max_age_seconds.' detik; ambil ulang foto saat akan mencatat.');
        abort_unless($now->diffInSeconds($locationAt, true) <= $policy->gps_max_age_seconds, 422,
            'Data lokasi lebih tua dari '.$policy->gps_max_age_seconds.' detik; muat ulang lokasi lalu ulangi.');
        abort_unless((float) $values['accuracy_meters'] <= (float) $policy->gps_max_accuracy_meters, 422,
            'Akurasi GPS '.round((float) $values['accuracy_meters']).' meter melebihi batas '.round((float) $policy->gps_max_accuracy_meters).' meter; keluar ke ruang terbuka lalu ulangi.');

        $upload = $request->file('photo');
        abort_unless($upload !== null && $upload->isValid(), 422, 'Foto wajib diambil dari kamera saat mencatat.');
        $photo = $this->photos->process($upload, (int) $policy->photo_max_dimension, (int) $policy->photo_quality, (int) $policy->photo_max_size_bytes);

        $today = today('Asia/Jakarta')->toDateString();
        $storedPath = null;
        try {
            $result = DB::transaction(function () use ($actor, $employee, $policy, $values, $now, $photoTakenAt, $locationAt, $photo, $today, $request, &$storedPath): array {
                $workDay = $this->ensureWorkDay($employee->id, $today, $actor);
                abort_unless($workDay !== null, 422, 'Hari ini bukan hari kerja menurut pola kerja Anda.');
                abort_unless($workDay->is_work_required, 422, 'Hari ini libur/kegiatan madrasah; kehadiran tidak dicatat.');

                $path = 'attendance-captures/'.$now->format('Y/m').'/'.Str::uuid()->toString().'.webp';
                abort_unless(Storage::disk('local')->put($path, $photo['bytes']), 503, 'Penyimpanan berkas tidak tersedia.');
                $storedPath = $path;
                $file = new File([
                    'storage_disk' => 'local', 'storage_path' => $path,
                    'original_name' => 'absen-'.($values['action'] === 'check_in' ? 'masuk' : 'pulang').'-'.$today.'.webp',
                    'mime_type' => 'image/webp', 'extension' => 'webp', 'size_bytes' => strlen($photo['bytes']),
                    'sha256' => hash('sha256', $photo['bytes']), 'classification' => 'internal', 'scan_status' => 'clean',
                ]);
                $file->forceFill(['uploaded_by' => $actor->id])->save();

                $attendance = EmployeeAttendance::query()->where('work_day_id', $workDay->id)->lockForUpdate()->first();
                $hadir = AttendanceStatus::query()->where('domain', 'employee')->where('code', 'hadir')->firstOrFail();
                if ($values['action'] === 'check_in') {
                    abort_unless($attendance === null, 422, 'Absen masuk hari ini sudah tercatat.');
                    $deadline = CarbonImmutable::parse($workDay->scheduled_start_at)->addMinutes($workDay->tolerance_minutes_snapshot);
                    $late = $now->greaterThan($deadline) ? (int) ceil($now->diffInSeconds($deadline, true) / 60) : 0;
                    $attendance = new EmployeeAttendance([
                        'work_day_id' => $workDay->id, 'attendance_status_id' => $hadir->id,
                        'check_in_at' => $now, 'late_minutes' => $late, 'entry_mode' => 'self',
                        'evidence_file_id' => $file->id,
                    ]);
                    $attendance->forceFill(['recorded_by' => $actor->id, 'recorded_at' => $now])->save();
                } else {
                    abort_unless($attendance !== null, 422, 'Belum ada absen masuk hari ini.');
                    abort_unless($attendance->check_out_at === null, 422, 'Absen pulang hari ini sudah tercatat.');
                    $attendance->check_out_at = $now;
                    $attendance->save();
                }

                $capture = new EmployeeAttendanceCapture([
                    'employee_attendance_id' => $attendance->id, 'event_type' => $values['action'],
                    'photo_file_id' => $file->id,
                    'latitude' => $values['latitude'], 'longitude' => $values['longitude'],
                    'accuracy_meters' => $values['accuracy_meters'],
                    'photo_taken_at' => $photoTakenAt, 'location_captured_at' => $locationAt,
                    'original_size_bytes' => $request->file('photo')->getSize(),
                    'photo_width' => $photo['width'], 'photo_height' => $photo['height'],
                    'compression_quality' => $photo['quality'],
                    'policy_snapshot' => $policy->only(['id', 'requires_photo', 'requires_gps', 'photo_max_dimension', 'photo_quality', 'photo_max_size_bytes', 'gps_max_accuracy_meters', 'gps_max_age_seconds']),
                    'operation_key' => $values['operation_key'],
                ]);
                $capture->forceFill(['recorded_by' => $actor->id, 'recorded_at' => $now])->save();

                $this->audit->audit($actor, 'attendance.'.$values['action'], 'employee_attendance', $attendance->id,
                    'Absen '.($values['action'] === 'check_in' ? 'masuk' : 'pulang').' mandiri dengan foto dan GPS.', null,
                    ['employee_id' => $employee->id, 'work_day_id' => $workDay->id, 'capture_id' => $capture->id], $request);

                return $this->status($actor);
            }, 3);

            return $result;
        } catch (UniqueConstraintViolationException) {
            if ($storedPath !== null && File::query()->where('storage_path', $storedPath)->doesntExist()) {
                Storage::disk('local')->delete($storedPath);
            }
            abort(409, 'Aksi absensi sudah tercatat. Muat ulang status kehadiran Anda.');
        } catch (\Throwable $failure) {
            // Transaksi gagal setelah berkas ditulis: hapus berkas yatim;
            // baris files sudah ikut ter-rollback bersama transaksi.
            if ($storedPath !== null && File::query()->where('storage_path', $storedPath)->doesntExist()) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $failure;
        }
    }

    // ------------------------------------------------------------------
    // Riwayat mandiri & pencatatan petugas (FR-05-01/07)
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $params */
    public function ownHistory(User $actor, array $params): array
    {
        $employee = AttendanceDataAccess::authorizeOwn($actor);
        $data = Validator::make($params, [
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ])->validate();
        $from = $data['date_from'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $data['date_to'] ?? now('Asia/Jakarta')->endOfMonth()->toDateString();
        $query = EmployeeWorkDay::query()->where('employee_id', $employee->id)
            ->whereBetween('work_date', [$from, $to])->with(['attendance.attendanceStatus', 'attendance.captures.photoFile', 'calendarEvent'])
            ->orderByDesc('work_date');

        $page = $query->paginate($data['per_page'] ?? 31)->withQueryString();
        $rows = $page->toArray();
        $rows['meta'] = [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];

        return ['rows' => $rows, 'range' => ['from' => $from, 'to' => $to]];
    }

    /** @param array<string, mixed> $data */
    public function storeRecord(User $actor, array $data, Request $request): EmployeeAttendance
    {
        return DB::transaction(function () use ($actor, $data, $request): EmployeeAttendance {
            AttendanceDataAccess::authorizeManage($actor);
            $values = Validator::make($data, [
                'employee_id' => ['required', 'integer', 'exists:employees,id'],
                'work_date' => ['required', 'date_format:Y-m-d'],
                'status_code' => ['required', 'string', 'max:30'],
                'check_in_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
                'check_out_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $status = AttendanceStatus::query()->where('domain', 'employee')->where('code', $values['status_code'])->where('is_active', true)->firstOrFail();
            $workDay = $this->ensureWorkDay($values['employee_id'], $values['work_date'], $actor);
            abort_unless($workDay !== null, 422, 'Tanggal tersebut bukan hari kerja pegawai menurut pola kerjanya.');
            abort_unless($workDay->is_work_required, 422, 'Tanggal tersebut libur/kegiatan; kehadiran tidak dicatat.');
            abort_unless(! EmployeeAttendance::query()->where('work_day_id', $workDay->id)->exists(), 409,
                'Kehadiran tanggal tersebut sudah tercatat; gunakan koreksi untuk mengubah.');

            $record = new EmployeeAttendance([
                'work_day_id' => $workDay->id, 'attendance_status_id' => $status->id,
                'check_in_at' => $values['check_in_at'] ?? null, 'check_out_at' => $values['check_out_at'] ?? null,
                'entry_mode' => 'officer', 'notes' => $values['notes'] ?? null,
            ]);
            $record->forceFill(['recorded_by' => $actor->id, 'recorded_at' => now('UTC')])->save();
            $this->audit->audit($actor, 'attendance.record_created', 'employee_attendance', $record->id, $values['reason'], null,
                ['employee_id' => $values['employee_id'], 'status' => $status->code, 'work_date' => $values['work_date']], $request);

            return $record->fresh(['workDay.calendarEvent', 'attendanceStatus', 'workDay.employee.person']);
        });
    }

    // ------------------------------------------------------------------
    // Koreksi (FR-05-02/03/06)
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function requestCorrection(User $actor, array $data, Request $request): AttendanceCorrection
    {
        return DB::transaction(function () use ($actor, $data, $request): AttendanceCorrection {
            AttendanceDataAccess::authorizeManage($actor);
            $values = Validator::make($data, [
                'employee_attendance_id' => ['required', 'integer'],
                'after_values' => ['required', 'array', 'min:1'],
                'after_values.check_in_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
                'after_values.check_out_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
                'after_values.status_code' => ['nullable', 'string', 'max:30'],
                'after_values.notes' => ['nullable', 'string', 'max:1000'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
                'expected_lock_version' => ['required', 'integer'],
            ])->validate();
            $attendance = EmployeeAttendance::query()->lockForUpdate()->findOrFail($values['employee_attendance_id']);
            abort_unless($attendance->lock_version === $values['expected_lock_version'], 409, 'Data kehadiran telah berubah; muat ulang lalu ajukan ulang.');
            $policy = $this->effectivePolicy();
            $deadline = CarbonImmutable::parse($attendance->workDay->work_date->toDateString(), 'Asia/Jakarta')->addDays($policy->correction_deadline_days)->endOfDay();
            abort_unless(now('Asia/Jakarta') <= $deadline || $policy->allow_late_correction_with_mandate, 422,
                'Batas koreksi '.$policy->correction_deadline_days.' hari kalender telah terlewat.');

            $before = $attendance->only(['check_in_at', 'check_out_at', 'late_minutes', 'notes', 'attendance_status_id']);
            $before['status_code'] = $attendance->attendanceStatus?->code;
            $correction = new AttendanceCorrection([
                'employee_attendance_id' => $attendance->id,
                'before_values' => $before, 'after_values' => $values['after_values'],
                'reason' => $values['reason'], 'requested_at' => now('UTC'), 'status' => 'pending',
                'expected_lock_version' => $attendance->lock_version, 'operation_key' => 'attendance-correction:'.Str::uuid(),
            ]);
            $correction->forceFill(['requested_by' => $actor->id])->save();
            $this->audit->audit($actor, 'attendance.correction_requested', 'attendance_correction', $correction->id, $values['reason'],
                null, ['employee_attendance_id' => $attendance->id], $request);

            return $correction;
        });
    }

    /** @param array<string, mixed> $data */
    public function decideCorrection(User $actor, int $correctionId, array $data, Request $request): AttendanceCorrection
    {
        return DB::transaction(function () use ($actor, $correctionId, $data, $request): AttendanceCorrection {
            AttendanceDataAccess::authorizeCorrect($actor);
            $values = Validator::make($data, [
                'decision' => ['required', 'in:approve,reject'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $correction = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correctionId);
            abort_unless($correction->status === 'pending', 422, 'Koreksi ini sudah diputuskan.');
            abort_if($correction->requested_by === $actor->id, 422, 'Pemohon dan penyetuju tidak boleh orang sama.');

            if ($values['decision'] === 'reject') {
                $correction->forceFill(['status' => 'rejected', 'rejection_reason' => $values['reason'],
                    'approved_at' => now('UTC'), 'approved_by' => $actor->id])->save();
                $this->audit->audit($actor, 'attendance.correction_rejected', 'attendance_correction', $correction->id, $values['reason'],
                    ['status' => 'pending'], ['status' => 'rejected'], $request);

                return $correction->fresh(self::CORRECTION_LOAD);
            }

            $attendance = EmployeeAttendance::query()->lockForUpdate()->findOrFail($correction->employee_attendance_id);
            abort_unless($attendance->lock_version === $correction->expected_lock_version, 409,
                'Data kehadiran berubah setelah koreksi diajukan; minta pengajuan ulang.');
            $after = $correction->after_values;
            if (isset($after['status_code'])) {
                $status = AttendanceStatus::query()->where('domain', 'employee')->where('code', $after['status_code'])->firstOrFail();
                $attendance->attendance_status_id = $status->id;
            }
            foreach (['check_in_at', 'check_out_at', 'notes'] as $field) {
                if (array_key_exists($field, $after)) {
                    $attendance->{$field} = $after[$field];
                }
            }
            if ($attendance->check_in_at !== null && $attendance->workDay->scheduled_start_at !== null) {
                $deadline = CarbonImmutable::parse($attendance->workDay->scheduled_start_at)->addMinutes($attendance->workDay->tolerance_minutes_snapshot);
                $attendance->late_minutes = CarbonImmutable::parse($attendance->check_in_at)->greaterThan($deadline)
                    ? (int) ceil(CarbonImmutable::parse($attendance->check_in_at)->diffInSeconds($deadline, true) / 60) : 0;
            }
            $attendance->lock_version += 1;
            $attendance->save();
            $correction->forceFill(['status' => 'applied', 'approved_at' => now('UTC'), 'applied_at' => now('UTC'), 'approved_by' => $actor->id])->save();
            $this->audit->audit($actor, 'attendance.correction_applied', 'attendance_correction', $correction->id, $values['reason'],
                ['status' => 'pending'], ['status' => 'applied', 'attendance_lock_version' => $attendance->lock_version], $request);

            return $correction->fresh(self::CORRECTION_LOAD);
        });
    }

    private const CORRECTION_LOAD = ['employeeAttendance.workDay.employee.person', 'employeeAttendance.attendanceStatus', 'requester.person', 'approver.person'];

    // ------------------------------------------------------------------
    // Pola kerja, penetapan, generator hari kerja, policy
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function saveWorkPattern(User $actor, ?int $id, array $data, Request $request): WorkPattern
    {
        return DB::transaction(function () use ($actor, $id, $data, $request): WorkPattern {
            AttendanceDataAccess::authorizeManage($actor);
            $pattern = $id ? WorkPattern::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'code' => [$pattern ? 'prohibited' : 'required', 'string', 'max:50'],
                'name' => [$pattern ? 'sometimes' : 'required', 'string', 'max:150'],
                'is_active' => [$pattern ? 'sometimes' : 'prohibited', 'boolean'],
                'effective_from' => [$pattern ? 'prohibited' : 'required', 'date_format:Y-m-d'],
                'days' => [$pattern ? 'prohibited' : 'required', 'array', 'min:1'],
                'days.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
                'days.*.start_time' => ['required', 'date_format:H:i'],
                'days.*.end_time' => ['required', 'date_format:H:i', 'after:days.*.start_time'],
                'days.*.late_tolerance_minutes' => ['required', 'integer', 'between:0,240'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $decision = BusinessDecision::query()->where('code', 'DEC-16')->orderByDesc('version')->firstOrFail();

            if ($pattern !== null) {
                $before = $pattern->only(['name', 'is_active']);
                unset($values['reason']);
                $pattern->fill($values)->save();
                $this->audit->audit($actor, 'attendance.work_pattern_updated', 'work_pattern', $pattern->id, $data['reason'], $before, $pattern->only(['name', 'is_active']), $request);

                return $pattern->fresh(self::PATTERN_LOAD);
            }

            $created = new WorkPattern(['code' => $values['code'], 'name' => $values['name']]);
            $created->save();
            $this->savePatternVersions($created, 1, $values['days'], $values['effective_from'], $decision->id, $actor->id);
            $this->audit->audit($actor, 'attendance.work_pattern_created', 'work_pattern', $created->id, $data['reason'], null,
                ['code' => $values['code']], $request);

            return $created->fresh(self::PATTERN_LOAD);
        });
    }

    /** @param array<string, mixed> $data */
    public function newPatternVersion(User $actor, int $patternId, array $data, Request $request): WorkPattern
    {
        return DB::transaction(function () use ($actor, $patternId, $data, $request): WorkPattern {
            AttendanceDataAccess::authorizeManage($actor);
            $values = Validator::make($data, [
                'effective_from' => ['required', 'date_format:Y-m-d'],
                'days' => ['required', 'array', 'min:1'],
                'days.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
                'days.*.start_time' => ['required', 'date_format:H:i'],
                'days.*.end_time' => ['required', 'date_format:H:i', 'after:days.*.start_time'],
                'days.*.late_tolerance_minutes' => ['required', 'integer', 'between:0,240'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $pattern = WorkPattern::query()->lockForUpdate()->findOrFail($patternId);
            $decision = BusinessDecision::query()->where('code', 'DEC-16')->orderByDesc('version')->firstOrFail();
            $open = WorkPatternVersion::query()->where('work_pattern_id', $pattern->id)->whereNull('effective_to')
                ->where('effective_from', '<', $values['effective_from'])->lockForUpdate()->get();
            foreach ($open as $version) {
                $version->forceFill(['effective_to' => CarbonImmutable::parse($values['effective_from'])->subDay()->toDateString()])->save();
            }
            $number = (int) WorkPatternVersion::query()->where('work_pattern_id', $pattern->id)->max('version_number') + 1;
            $this->savePatternVersions($pattern, $number, $values['days'], $values['effective_from'], $decision->id, $actor->id);
            $this->audit->audit($actor, 'attendance.work_pattern_versioned', 'work_pattern', $pattern->id, $values['reason'],
                $open->isEmpty() ? null : ['closed_version_rows' => $open->count()], ['version_number' => $number], $request);

            return $pattern->fresh(self::PATTERN_LOAD);
        });
    }

    /** @param array<int, array<string, mixed>> $days */
    private function savePatternVersions(WorkPattern $pattern, int $number, array $days, string $effectiveFrom, int $decisionId, int $userId): void
    {
        foreach ($days as $day) {
            $version = new WorkPatternVersion([
                'work_pattern_id' => $pattern->id, 'version_number' => $number,
                'day_of_week' => $day['day_of_week'], 'start_time' => $day['start_time'], 'end_time' => $day['end_time'],
                'crosses_midnight' => false, 'late_tolerance_minutes' => $day['late_tolerance_minutes'],
                'effective_from' => $effectiveFrom, 'decision_id' => $decisionId,
            ]);
            $version->forceFill(['created_by' => $userId])->save();
        }
    }

    /** @param array<string, mixed> $data */
    public function assignPattern(User $actor, int $patternId, array $data, Request $request): EmployeeWorkAssignment
    {
        return DB::transaction(function () use ($actor, $patternId, $data, $request): EmployeeWorkAssignment {
            AttendanceDataAccess::authorizeManage($actor);
            $values = Validator::make($data, [
                'employee_id' => ['required', 'integer', 'exists:employees,id'],
                'effective_from' => ['required', 'date_format:Y-m-d'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $pattern = WorkPattern::query()->findOrFail($patternId);
            abort_unless($pattern->is_active, 422, 'Pola kerja nonaktif tidak dapat diterapkan.');
            $previous = EmployeeWorkAssignment::query()->where('employee_id', $values['employee_id'])
                ->where('effective_from', '<', $values['effective_from'])
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $values['effective_from']))
                ->lockForUpdate()->get();
            foreach ($previous as $old) {
                $old->forceFill(['effective_to' => CarbonImmutable::parse($values['effective_from'])->subDay()->toDateString()])->save();
            }
            $created = new EmployeeWorkAssignment(['employee_id' => $values['employee_id'], 'work_pattern_id' => $pattern->id, 'effective_from' => $values['effective_from']]);
            $created->forceFill(['created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'attendance.work_pattern_assigned', 'employee_work_assignment', $created->id, $values['reason'],
                $previous->isEmpty() ? null : ['closed_ids' => $previous->pluck('id')->all()],
                ['employee_id' => $values['employee_id'], 'work_pattern_id' => $pattern->id], $request);

            return $created->fresh(['employee.person', 'workPattern']);
        });
    }

    /** @param array<string, mixed> $data */
    public function generateWorkDays(User $actor, array $data, Request $request): array
    {
        return DB::transaction(function () use ($actor, $data, $request): array {
            AttendanceDataAccess::authorizeManage($actor);
            $values = Validator::make($data, [
                'date_from' => ['required', 'date_format:Y-m-d'],
                'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
                'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            $from = CarbonImmutable::parse($values['date_from'], 'Asia/Jakarta');
            $to = CarbonImmutable::parse($values['date_to'], 'Asia/Jakarta');
            abort_if($from->diffInDays($to) > 92, 422, 'Rentang maksimal 92 hari per permintaan.');
            $employees = Employee::query()->where('is_active', true)
                ->when($values['employee_id'] ?? null, fn (Builder $q, int $id): Builder => $q->whereKey($id))
                ->whereHas('workAssignments')->pluck('id');
            $created = 0;
            foreach ($employees as $employeeId) {
                for ($date = $from; $date->lessThanOrEqualTo($to); $date = $date->addDay()) {
                    if ($this->ensureWorkDay($employeeId, $date->toDateString(), $actor) !== null) {
                        $created++;
                    }
                }
            }
            $this->audit->audit($actor, 'attendance.work_days_generated', 'employee_work_day', 0, $values['reason'], null,
                ['date_from' => $values['date_from'], 'date_to' => $values['date_to'], 'employees' => $employees->count(), 'work_days' => $created], $request);

            return ['employees' => $employees->count(), 'work_days' => $created, 'date_from' => $values['date_from'], 'date_to' => $values['date_to']];
        });
    }

    /** @param array<string, mixed> $data */
    public function newPolicyVersion(User $actor, array $data, Request $request): EmployeeAttendancePolicy
    {
        return DB::transaction(function () use ($actor, $data, $request): EmployeeAttendancePolicy {
            AttendanceDataAccess::authorizeManage($actor);
            $current = $this->effectivePolicy();
            $values = Validator::make($data, [
                'effective_from' => ['required', 'date_format:Y-m-d', 'after:'.$current->effective_from->toDateString()],
                'requires_photo' => ['required', 'boolean'],
                'correction_deadline_days' => ['required', 'integer', 'between:1,60'],
                'photo_max_size_bytes' => ['required', 'integer', 'between:1,10485760'],
                'photo_max_dimension' => ['required', 'integer', 'between:1,8192'],
                'photo_quality' => ['required', 'integer', 'between:1,100'],
                'gps_max_accuracy_meters' => ['required', 'numeric', 'min:1'],
                'gps_max_age_seconds' => ['required', 'integer', 'min:1'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
            ])->validate();
            abort_unless($values['effective_from'] > today('Asia/Jakarta')->toDateString(), 422, 'Versi kebijakan baru harus berlaku mulai besok atau setelahnya.');
            $decision = BusinessDecision::query()->where('code', 'DEC-16')->orderByDesc('version')->firstOrFail();
            $current->forceFill(['effective_to' => CarbonImmutable::parse($values['effective_from'])->subDay()->toDateString()])->save();
            $created = new EmployeeAttendancePolicy([
                'code' => $current->code, 'version_number' => $current->version_number + 1, 'status' => 'baseline',
                'correction_deadline_days' => $values['correction_deadline_days'],
                'requires_correction_approval' => $current->requires_correction_approval,
                'allow_late_correction_with_mandate' => $current->allow_late_correction_with_mandate,
                'effective_from' => $values['effective_from'], 'decision_id' => $decision->id,
                'requires_photo' => $values['requires_photo'], 'requires_gps' => $values['requires_photo'],
                'photo_max_size_bytes' => $values['photo_max_size_bytes'], 'photo_max_dimension' => $values['photo_max_dimension'],
                'photo_quality' => $values['photo_quality'], 'gps_max_accuracy_meters' => $values['gps_max_accuracy_meters'],
                'gps_max_age_seconds' => $values['gps_max_age_seconds'],
            ]);
            $created->save();
            $this->audit->audit($actor, 'attendance.policy_versioned', 'employee_attendance_policy', $created->id, $values['reason'],
                $current->only(['effective_from', 'effective_to', 'requires_photo', 'gps_max_accuracy_meters']),
                $created->only(['version_number', 'effective_from', 'requires_photo', 'gps_max_accuracy_meters']), $request);

            return $created;
        });
    }

    // ------------------------------------------------------------------
    // Bukti foto/GPS (akses terbatas, FR-05-06)
    // ------------------------------------------------------------------

    public function capture(User $actor, int $captureId): EmployeeAttendanceCapture
    {
        $capture = EmployeeAttendanceCapture::query()->with(['photoFile', 'attendance.workDay.employee.person'])->findOrFail($captureId);
        $this->authorizeCapture($actor, $capture);

        return $capture;
    }

    public function capturePhoto(User $actor, int $captureId): File
    {
        $capture = EmployeeAttendanceCapture::query()->with(['photoFile', 'attendance.workDay'])->findOrFail($captureId);
        $this->authorizeCapture($actor, $capture);
        abort_unless($capture->photoFile !== null, 404);

        return $capture->photoFile;
    }

    private function authorizeCapture(User $actor, EmployeeAttendanceCapture $capture): void
    {
        if (AttendanceDataAccess::allows($actor, 'attendance.manage')) {
            return;
        }
        $employee = AttendanceDataAccess::employee($actor);
        abort_unless($employee !== null && $capture->attendance?->workDay?->employee_id === $employee->id, 404);
    }

    private const PATTERN_LOAD = ['versions', 'employeeWorkAssignments.employee.person'];
}
