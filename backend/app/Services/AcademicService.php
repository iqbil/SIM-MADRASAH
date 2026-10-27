<?php

namespace App\Services;

use App\Models\AcademicCalendarEvent;
use App\Models\AcademicContext;
use App\Models\AcademicYear;
use App\Models\ClassPeriod;
use App\Models\HomeroomAssignment;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentStatus;
use App\Models\StudentTransition;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Layanan modul Akademik & Kelas: tahun ajaran/semester, periode aktif,
 * rombongan belajar, keanggotaan/riwayat siswa, mutasi, kalender akademik,
 * penugasan mengajar dan wali kelas. Seluruh mutasi berjalan dalam transaksi
 * dengan penguncian parent sesuai database.MD §15.2 dan audit akun.
 */
class AcademicService
{
    public function __construct(private AccountManagementService $audit) {}

    // ------------------------------------------------------------------
    // Periode aktif (DEC-19: satu pointer singleton)
    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function context(): array
    {
        $context = AcademicContext::query()->with(['activeAcademicYear', 'activeSemester'])->first();

        return [
            'active_academic_year_id' => $context?->active_academic_year_id,
            'active_semester_id' => $context?->active_semester_id,
            'lock_version' => $context?->lock_version ?? 0,
            'year' => $context?->activeAcademicYear?->only(['id', 'code', 'name', 'start_date', 'end_date', 'status']),
            'semester' => $context?->activeSemester?->only(['id', 'academic_year_id', 'number', 'name', 'start_date', 'end_date', 'status']),
            'changed_by' => $context?->changed_by,
            'updated_at' => $context?->updated_at,
        ];
    }

    /** @param array<string, mixed> $data */
    public function setContext(User $actor, array $data, Request $request): array
    {
        return $this->mutate(function () use ($actor, $data, $request): array {
            AcademicDataAccess::authorize($actor, 'years', true);
            $values = Validator::make($data, [
                'active_academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
                'active_semester_id' => ['required', 'integer', 'exists:semesters,id'],
                'expected_lock_version' => ['required', 'integer'],
                'reason' => self::reasonRule(),
            ])->validate();
            $context = AcademicContext::query()->lockForUpdate()->first() ?? (function () use ($actor): AcademicContext {
                $created = new AcademicContext;
                $created->forceFill(['singleton_key' => 1, 'changed_by' => $actor->id, 'lock_version' => 0])->save();

                return $created;
            })();
            abort_unless((int) $values['expected_lock_version'] === $context->lock_version, 409, 'Periode aktif telah berubah. Muat ulang sebelum menyimpan.');
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($values['active_academic_year_id']);
            $semester = Semester::query()->lockForUpdate()->findOrFail($values['active_semester_id']);
            abort_unless($semester->academic_year_id === $year->id, 422, 'Semester harus milik tahun ajaran yang dipilih.');
            abort_unless($year->status === 'open' && $semester->status === 'open', 422, 'Tahun ajaran dan semester harus berstatus terbuka sebelum diaktifkan.');
            $before = ['year_id' => $context->active_academic_year_id, 'semester_id' => $context->active_semester_id];
            $context->fill(['active_academic_year_id' => $year->id, 'active_semester_id' => $semester->id]);
            $context->forceFill(['changed_by' => $actor->id, 'lock_version' => $context->lock_version + 1])->save();
            $this->audit->audit($actor, 'academic.context.updated', 'academic_context', $context->id, $values['reason'], $before,
                ['year_id' => $year->id, 'semester_id' => $semester->id], $request);

            return $this->context();
        });
    }

    // ------------------------------------------------------------------
    // Tahun ajaran & semester
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function saveYear(User $actor, ?int $id, array $data, Request $request): AcademicYear
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): AcademicYear {
            AcademicDataAccess::authorize($actor, 'years', true);
            $year = $id ? AcademicYear::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'code' => [$year ? 'prohibited' : 'required', 'string', 'max:20'],
                'name' => [$year ? 'sometimes' : 'required', 'string', 'max:50'],
                'start_date' => [$year ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'end_date' => [$year ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'status' => ['sometimes', 'in:planned,open,closed'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$year ? 'required' : 'prohibited', 'date'],
            ])->validate();
            self::assertRange($values, $year?->start_date->toDateString(), 'end_date');
            if ($year) {
                MasterDataService::checkVersion($year, $values['expected_updated_at']);
                if (($values['status'] ?? $year->status) === 'closed' && $year->status !== 'closed') {
                    $this->assertNotActive('year', $year->id);
                }
                unset($values['code'], $values['expected_updated_at'], $values['reason']);
                $before = $year->only(['status', 'start_date', 'end_date']);
                $year->fill($values)->save();
                $this->audit->audit($actor, 'academic.years.updated', 'academic_year', $year->id, $data['reason'], $before, $year->only(['status', 'start_date', 'end_date']), $request);

                return $year->fresh();
            }
            $created = new AcademicYear($values);
            $created->forceFill(['status' => $values['status'] ?? 'planned', 'created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'academic.years.created', 'academic_year', $created->id, $data['reason'], null, $created->only(['code', 'status']), $request);

            return $created;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveSemester(User $actor, ?int $id, array $data, Request $request): Semester
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): Semester {
            AcademicDataAccess::authorize($actor, 'years', true);
            $semester = $id ? Semester::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'academic_year_id' => [$semester ? 'prohibited' : 'required', 'integer', 'exists:academic_years,id'],
                'number' => [$semester ? 'prohibited' : 'required', 'in:1,2'],
                'name' => [$semester ? 'sometimes' : 'required', 'string', 'max:50'],
                'start_date' => [$semester ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'end_date' => [$semester ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'status' => ['sometimes', 'in:planned,open,closed'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$semester ? 'required' : 'prohibited', 'date'],
            ])->validate();
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($semester?->academic_year_id ?? $values['academic_year_id']);
            $semester ??= new Semester(['academic_year_id' => $year->id]);
            $from = $values['start_date'] ?? $semester->start_date->toDateString();
            $to = $values['end_date'] ?? $semester->end_date?->toDateString();
            self::assertRange(['start_date' => $from, 'end_date' => $to], null, 'end_date');
            abort_unless($from >= $year->start_date->toDateString() && $to <= $year->end_date->toDateString(), 422, 'Rentang semester harus berada dalam tahun ajaran.');
            $overlap = $year->semesters()->whereKeyNot($semester->id)
                ->where('start_date', '<', $to)->where('end_date', '>', $from)->exists();
            abort_unless(! $overlap, 422, 'Rentang semester bertumpang tindih dengan semester lain pada tahun ajaran ini.');
            if ($semester->exists) {
                MasterDataService::checkVersion($semester, $values['expected_updated_at']);
                if (($values['status'] ?? $semester->status) === 'closed' && $semester->status !== 'closed') {
                    $this->assertNotActive('semester', $semester->id);
                }
                unset($values['academic_year_id'], $values['number'], $values['expected_updated_at'], $values['reason']);
                $before = $semester->only(['status', 'start_date', 'end_date']);
                $semester->fill($values)->save();
                $this->audit->audit($actor, 'academic.semesters.updated', 'semester', $semester->id, $data['reason'], $before, $semester->only(['status', 'start_date', 'end_date']), $request);

                return $semester->fresh();
            }
            $created = new Semester($values);
            $created->forceFill(['status' => $values['status'] ?? 'planned', 'created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'academic.semesters.created', 'semester', $created->id, $data['reason'], null, $created->only(['number', 'status']), $request);

            return $created;
        });
    }

    private function assertNotActive(string $kind, int $id): void
    {
        $context = AcademicContext::query()->first();
        $active = $kind === 'year' ? $context?->active_academic_year_id : $context?->active_semester_id;
        abort_if($active === $id, 422, 'Periode yang sedang aktif tidak dapat ditutup. Alihkan periode aktif terlebih dahulu.');
    }

    // ------------------------------------------------------------------
    // Rombongan belajar (identitas kelas + instansi per tahun)
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function saveClass(User $actor, ?int $id, array $data, Request $request): SchoolClass
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): SchoolClass {
            AcademicDataAccess::authorize($actor, 'classes', true);
            $class = $id ? SchoolClass::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'code' => [$class ? 'prohibited' : 'required', 'string', 'max:30'],
                'name' => [$class ? 'sometimes' : 'required', 'string', 'max:100'],
                'grade' => [$class ? 'sometimes' : 'required', 'in:7,8,9'],
                'is_active' => ['sometimes', 'boolean'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$class ? 'required' : 'prohibited', 'date'],
            ])->validate();
            if ($class) {
                MasterDataService::checkVersion($class, $values['expected_updated_at']);
                unset($values['code'], $values['expected_updated_at'], $values['reason']);
                $before = $class->only(['name', 'grade', 'is_active']);
                $class->fill($values)->save();
                $this->audit->audit($actor, 'academic.classes.updated', 'school_class', $class->id, $data['reason'], $before, $class->only(['name', 'grade', 'is_active']), $request);

                return $class->fresh();
            }
            $created = new SchoolClass($values);
            $created->save();
            $this->audit->audit($actor, 'academic.classes.created', 'school_class', $created->id, $data['reason'], null, $created->only(['code', 'grade']), $request);

            return $created;
        });
    }

    /** @param array<string, mixed> $data */
    public function saveClassPeriod(User $actor, ?int $id, array $data, Request $request): ClassPeriod
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): ClassPeriod {
            AcademicDataAccess::authorize($actor, 'classes', true);
            $period = $id ? ClassPeriod::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'class_id' => [$period ? 'prohibited' : 'required', 'integer', 'exists:classes,id'],
                'academic_year_id' => [$period ? 'prohibited' : 'required', 'integer', 'exists:academic_years,id'],
                'display_name' => [$period ? 'sometimes' : 'required', 'string', 'max:100'],
                'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
                'status' => ['sometimes', 'in:planned,open,closed'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$period ? 'required' : 'prohibited', 'date'],
            ])->validate();
            if ($period) {
                MasterDataService::checkVersion($period, $values['expected_updated_at']);
                unset($values['class_id'], $values['academic_year_id'], $values['expected_updated_at'], $values['reason']);
                $before = $period->only(['display_name', 'capacity', 'status']);
                $period->fill($values)->save();
                $this->audit->audit($actor, 'academic.class_periods.updated', 'class_period', $period->id, $data['reason'], $before, $period->only(['display_name', 'capacity', 'status']), $request);

                return $period->fresh(['schoolClass']);
            }
            $class = SchoolClass::query()->lockForUpdate()->findOrFail($values['class_id']);
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($values['academic_year_id']);
            $created = new ClassPeriod($values);
            $created->forceFill(['status' => $values['status'] ?? 'planned', 'grade_snapshot' => $class->grade])->save();
            $this->audit->audit($actor, 'academic.class_periods.created', 'class_period', $created->id, $data['reason'], null,
                ['class_id' => $class->id, 'academic_year_id' => $year->id, 'grade_snapshot' => $created->grade_snapshot], $request);

            return $created->fresh(['schoolClass']);
        });
    }

    // ------------------------------------------------------------------
    // Keanggotaan kelas & mutasi (FR-03-02/03, BR-01/02)
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function saveEnrollment(User $actor, ?int $id, array $data, Request $request): StudentEnrollment
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): StudentEnrollment {
            AcademicDataAccess::authorize($actor, 'enrollments', true);
            $enrollment = $id ? StudentEnrollment::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'student_id' => [$enrollment ? 'prohibited' : 'required', 'integer', 'exists:students,id'],
                'class_period_id' => [$enrollment ? 'prohibited' : 'required', 'integer', 'exists:class_periods,id'],
                'effective_from' => [$enrollment ? 'prohibited' : 'required', 'date_format:Y-m-d'],
                'entry_reason' => [$enrollment ? 'prohibited' : 'required', 'in:admission,transfer,correction'],
                'effective_to' => [$enrollment ? 'required_with:exit_reason' : 'prohibited', 'nullable', 'date_format:Y-m-d'],
                'exit_reason' => [$enrollment ? 'required_with:effective_to' : 'prohibited', 'nullable', 'in:promotion,transfer,graduation,withdrawal,correction'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$enrollment ? 'required' : 'prohibited', 'date'],
            ])->validate();
            if ($enrollment) {
                MasterDataService::checkVersion($enrollment, $values['expected_updated_at']);
                Student::query()->lockForUpdate()->findOrFail($enrollment->student_id);
                $from = $enrollment->effective_from->toDateString();
                $to = $values['effective_to'] ?? null;
                if ($to !== null && $to <= $from) {
                    throw ValidationException::withMessages(['effective_to' => 'Tanggal berakhir harus setelah tanggal mulai keanggotaan.']);
                }
                $this->assertNoEnrollmentOverlap($enrollment->student_id, $from, $to, $enrollment->id);
                unset($values['reason'], $values['expected_updated_at']);
                $before = $enrollment->only(['effective_to', 'exit_reason']);
                $enrollment->fill($values)->save();
                $this->audit->audit($actor, 'academic.enrollments.updated', 'student_enrollment', $enrollment->id, $data['reason'], $before, $enrollment->only(['effective_to', 'exit_reason']), $request);

                return $enrollment->fresh(['student.person', 'classPeriod.schoolClass']);
            }
            $student = Student::query()->lockForUpdate()->findOrFail($values['student_id']);
            $period = ClassPeriod::query()->lockForUpdate()->findOrFail($values['class_period_id']);
            abort_unless($period->status !== 'closed', 422, 'Rombongan belajar sudah ditutup untuk keanggotaan baru.');
            $this->assertWithinPeriodYear($period, $values['effective_from'], 'Tanggal mulai keanggotaan');
            $this->assertNoEnrollmentOverlap($student->id, $values['effective_from'], null, null);
            $created = new StudentEnrollment($values);
            $created->forceFill(['created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'academic.enrollments.created', 'student_enrollment', $created->id, $data['reason'], null,
                ['student_id' => $student->id, 'class_period_id' => $period->id, 'effective_from' => $created->effective_from->toDateString()], $request);

            return $created->fresh(['student.person', 'classPeriod.schoolClass']);
        });
    }

    private function assertNoEnrollmentOverlap(int $studentId, string $from, ?string $to, ?int $except): void
    {
        $exists = StudentEnrollment::query()->where('student_id', $studentId)->whereKeyNot($except ?? 0)
            ->where('effective_from', '<', $to ?? '9999-12-31')
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $from))
            ->exists();
        abort_if($exists, 422, 'Siswa masih memiliki keanggotaan aktif pada rentang tanggal ini. Gunakan perpindahan kelas atau mutasi agar riwayat tetap terjaga.');
    }

    /**
     * Perpindahan kelas: tutup keanggotaan lama pada tanggal efektif (akhir
     * eksklusif) dan buat keanggotaan baru dalam satu transaksi (FR-03-02).
     *
     * @param  array<string, mixed>  $data
     */
    public function moveEnrollment(User $actor, array $data, Request $request): StudentEnrollment
    {
        return $this->mutate(function () use ($actor, $data, $request): StudentEnrollment {
            AcademicDataAccess::authorize($actor, 'enrollments', true);
            $values = Validator::make($data, [
                'student_id' => ['required', 'integer', 'exists:students,id'],
                'to_class_period_id' => ['required', 'integer', 'exists:class_periods,id'],
                'effective_date' => ['required', 'date_format:Y-m-d'],
                'entry_reason' => ['required', 'in:promotion,transfer,correction'],
                'reason' => self::reasonRule(),
            ])->validate();
            $student = Student::query()->lockForUpdate()->findOrFail($values['student_id']);
            $target = ClassPeriod::query()->lockForUpdate()->findOrFail($values['to_class_period_id']);
            abort_unless($target->status !== 'closed', 422, 'Rombongan belajar tujuan sudah ditutup.');
            $date = $values['effective_date'];
            $this->assertWithinPeriodYear($target, $date, 'Tanggal perpindahan');
            $current = $this->activeEnrollment($student->id, $date, true);
            abort_unless($current, 422, 'Siswa tidak memiliki keanggotaan aktif pada tanggal efektif.');
            abort_if($current->class_period_id === $target->id, 422, 'Siswa sudah terdaftar pada rombongan belajar tujuan.');
            abort_unless($date > $current->effective_from->toDateString(), 422, 'Tanggal efektif harus setelah keanggotaan aktif dimulai.');
            $this->assertNoEnrollmentOverlap($student->id, $date, null, $current->id);
            $exitReason = match ($values['entry_reason']) {
                'promotion' => 'promotion',
                'correction' => 'correction',
                default => 'transfer',
            };
            $current->forceFill(['effective_to' => $date])->save();
            $created = new StudentEnrollment([
                'student_id' => $student->id, 'class_period_id' => $target->id, 'effective_from' => $date,
                'entry_reason' => $values['entry_reason'], 'exit_reason' => null,
            ]);
            $created->forceFill(['created_by' => $actor->id])->save();
            StudentTransition::query()->forceCreate([
                'student_id' => $student->id, 'from_enrollment_id' => $current->id, 'to_enrollment_id' => $created->id,
                'from_status_id' => $student->status_id, 'to_status_id' => $student->status_id, 'type' => 'class_transfer',
                'effective_date' => $date, 'reason' => $values['reason'], 'batch_key' => 'move:'.$created->id,
                'operation_key' => 'enrollment-move:'.Str::uuid(), 'confirmed_by' => $actor->id, 'confirmed_at' => now('UTC'),
            ]);
            $this->audit->audit($actor, 'academic.enrollments.moved', 'student_enrollment', $created->id, $values['reason'],
                ['from_class_period_id' => $current->class_period_id, 'effective_to' => $current->effective_to?->toDateString()],
                ['to_class_period_id' => $target->id, 'effective_from' => $date], $request);

            return $created->fresh(['student.person', 'classPeriod.schoolClass']);
        });
    }

    /** @return StudentEnrollment|null */
    private function activeEnrollment(int $studentId, string $date, bool $lock = false)
    {
        return StudentEnrollment::query()->where('student_id', $studentId)
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $date))
            ->when($lock, fn (Builder $q): Builder => $q->lockForUpdate())
            ->first();
    }

    /**
     * Pratinjau mutasi siswa (kenaikan/lulus/pindah sekolah/keluar) tanpa
     * menulis apa pun (FR-03-03: pratinjau lalu konfirmasi petugas).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transitionPreview(User $actor, array $data): array
    {
        AcademicDataAccess::authorize($actor, 'enrollments', true);
        [$toStatus, $activeStatus, $target] = $this->transitionSetup($data);
        $values = Validator::make($data, [
            'student_ids' => ['required', 'array', 'min:1', 'max:200'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'type' => ['required', 'in:promotion,class_transfer,graduation,school_transfer,withdrawal'],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'to_class_period_id' => ['required_if:type,promotion,class_transfer', 'nullable', 'integer', 'exists:class_periods,id'],
        ])->validate();
        $date = $values['effective_date'];
        $plans = [];
        foreach (Student::query()->with(['status', 'person'])->whereIn('id', $values['student_ids'])->orderBy('id')->get() as $student) {
            $current = $this->activeEnrollment($student->id, $date)?->load('classPeriod.schoolClass');
            $plans[] = [
                'student_id' => $student->id,
                'student_name' => $student->person?->full_name,
                'current_status' => $student->status?->code,
                'to_status' => $toStatus->code,
                'current_class_period_id' => $current?->class_period_id,
                'current_class_name' => $current?->classPeriod?->display_name,
                'to_class_period_id' => $target?->id,
                'to_class_name' => $target?->display_name,
                'effective_date' => $date,
                'problem' => match (true) {
                    $student->status_id !== $activeStatus->id => 'Status siswa bukan aktif.',
                    $current === null => 'Tidak ada keanggotaan aktif pada tanggal efektif.',
                    $current->class_period_id === $target?->id => 'Sudah terdaftar pada rombongan belajar tujuan.',
                    $date <= $current->effective_from->toDateString() => 'Tanggal efektif harus setelah keanggotaan aktif dimulai.',
                    default => null,
                },
            ];
        }

        return ['type' => $values['type'], 'effective_date' => $date, 'to_class_period_id' => $target?->id, 'to_status' => $toStatus->code,
            'students' => $plans, 'ready' => $plans !== [] && collect($plans)->every(fn (array $plan): bool => $plan['problem'] === null)];
    }

    /**
     * Konfirmasi mutasi siswa setelah pratinjau (FR-03-03): menutup
     * keanggotaan, membuka keanggotaan baru bila perlu, memutasi status
     * siswa, dan mencatat student_transitions + audit dalam satu transaksi.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transitionConfirm(User $actor, array $data, Request $request): array
    {
        return $this->mutate(function () use ($actor, $data, $request): array {
            AcademicDataAccess::authorize($actor, 'enrollments', true);
            [$toStatus, $activeStatus, $target] = $this->transitionSetup($data);
            $values = Validator::make($data, [
                'student_ids' => ['required', 'array', 'min:1', 'max:200'],
                'student_ids.*' => ['integer', 'exists:students,id'],
                'type' => ['required', 'in:promotion,class_transfer,graduation,school_transfer,withdrawal'],
                'effective_date' => ['required', 'date_format:Y-m-d'],
                'to_class_period_id' => ['required_if:type,promotion,class_transfer', 'nullable', 'integer', 'exists:class_periods,id'],
                'reason' => self::reasonRule(),
            ])->validate();
            $date = $values['effective_date'];
            $batchKey = 'batch:'.Str::uuid();
            $results = [];
            foreach (Student::query()->whereIn('id', $values['student_ids'])->orderBy('id')->lockForUpdate()->get() as $student) {
                abort_unless($student->status_id === $activeStatus->id, 422, 'Siswa '.$student->id.' berstatus '.$student->status?->code.'; mutasi hanya berlaku bagi siswa aktif.');
                $current = $this->activeEnrollment($student->id, $date, true);
                abort_unless($current, 422, 'Siswa '.$student->id.' tidak memiliki keanggotaan aktif pada tanggal efektif.');
                abort_if($target && $current->class_period_id === $target->id, 422, 'Siswa '.$student->id.' sudah berada pada rombongan belajar tujuan.');
                abort_unless($date > $current->effective_from->toDateString(), 422, 'Tanggal efektif harus setelah keanggotaan aktif siswa '.$student->id.' dimulai.');
                $exitReason = match ($values['type']) {
                    'promotion', 'graduation' => $values['type'],
                    'withdrawal' => 'withdrawal',
                    default => 'transfer',
                };
                $this->assertWithinPeriodYear($target ?? $current->classPeriod, $date, 'Tanggal efektif mutasi');
                $toEnrollmentId = null;
                if ($target !== null) {
                    $toEnrollment = new StudentEnrollment([
                        'student_id' => $student->id, 'class_period_id' => $target->id, 'effective_from' => $date,
                        'entry_reason' => $values['type'] === 'promotion' ? 'promotion' : 'transfer',
                    ]);
                    $toEnrollment->forceFill(['created_by' => $actor->id])->save();
                    $toEnrollmentId = $toEnrollment->id;
                }
                $current->forceFill(['effective_to' => $date, 'exit_reason' => $exitReason])->save();
                $terminal = $toStatus->is_terminal;
                $student->forceFill(['status_id' => $toStatus->id, 'archived_at' => $terminal ? now('UTC') : null])->save();
                StudentTransition::query()->forceCreate([
                    'student_id' => $student->id, 'from_enrollment_id' => $current->id, 'to_enrollment_id' => $toEnrollmentId,
                    'from_status_id' => $activeStatus->id, 'to_status_id' => $toStatus->id, 'type' => $values['type'],
                    'effective_date' => $date, 'reason' => $values['reason'], 'batch_key' => $batchKey,
                    'operation_key' => 'transition:'.Str::uuid(), 'confirmed_by' => $actor->id, 'confirmed_at' => now('UTC'),
                ]);
                $results[] = ['student_id' => $student->id, 'to_status' => $toStatus->code, 'to_enrollment_id' => $toEnrollmentId];
                $this->audit->audit($actor, 'academic.transitions.confirmed', 'student', $student->id, $values['reason'],
                    ['status_id' => $activeStatus->id, 'enrollment_id' => $current->id],
                    ['status_id' => $toStatus->id, 'enrollment_id' => $toEnrollmentId, 'type' => $values['type']], $request);
            }

            return ['batch_key' => $batchKey, 'type' => $values['type'], 'effective_date' => $date, 'to_status' => $toStatus->code, 'students' => $results];
        });
    }

    /**
     * Validasi bersama pratinjau/konfirmasi mutasi.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: StudentStatus, 1: StudentStatus, 2: ?ClassPeriod}
     */
    private function transitionSetup(array $data): array
    {
        $type = $data['type'] ?? '';
        abort_unless(in_array($type, ['promotion', 'class_transfer', 'graduation', 'school_transfer', 'withdrawal'], true), 422);
        $toCode = match ($type) {
            'graduation' => 'graduated',
            'school_transfer' => 'transferred',
            'withdrawal' => 'withdrawn',
            default => 'active',
        };
        $statuses = StudentStatus::query()->get()->keyBy('code');
        $active = $statuses['active'] ?? null;
        $to = $statuses[$toCode] ?? null;
        abort_unless($active && $active->is_active && ! $active->is_terminal, 422, 'Referensi status siswa belum siap. Jalankan seeder referensi terlebih dahulu.');
        abort_unless($to, 422, 'Referensi status tujuan ('.$toCode.') belum tersedia.');
        $target = null;
        if (in_array($type, ['promotion', 'class_transfer'], true)) {
            $target = ClassPeriod::query()->lockForUpdate()->findOrFail($data['to_class_period_id'] ?? 0);
            abort_unless($target->status !== 'closed', 422, 'Rombongan belajar tujuan sudah ditutup.');
        }

        return [$to, $active, $target];
    }

    // ------------------------------------------------------------------
    // Kalender akademik
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function saveCalendarEvent(User $actor, ?int $id, array $data, Request $request): AcademicCalendarEvent
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): AcademicCalendarEvent {
            AcademicDataAccess::authorize($actor, 'calendar', true);
            $event = $id ? AcademicCalendarEvent::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
                'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
                'class_period_id' => ['nullable', 'integer', 'exists:class_periods,id'],
                'title' => [$event ? 'sometimes' : 'required', 'string', 'max:200'],
                'event_type' => [$event ? 'sometimes' : 'required', 'in:holiday,activity,exam,other'],
                'start_date' => [$event ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'end_date' => [$event ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'blocks_teaching' => ['sometimes', 'boolean'],
                'blocks_employee_work' => ['sometimes', 'boolean'],
                'description' => ['nullable', 'string', 'max:5000'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$event ? 'required' : 'prohibited', 'date'],
            ])->validate();
            self::assertRange($values, $event?->start_date?->toDateString(), 'end_date');
            $year = AcademicYear::query()->lockForUpdate()->findOrFail($values['academic_year_id']);
            $semester = isset($values['semester_id']) ? Semester::query()->findOrFail($values['semester_id']) : null;
            abort_unless(! $semester || $semester->academic_year_id === $year->id, 422, 'Semester harus milik tahun ajaran yang sama.');
            $period = isset($values['class_period_id']) ? ClassPeriod::query()->findOrFail($values['class_period_id']) : null;
            abort_unless(! $period || $period->academic_year_id === $year->id, 422, 'Rombongan belajar harus milik tahun ajaran yang sama.');
            $eventFrom = $values['start_date'] ?? $event?->start_date?->toDateString();
            $eventTo = $values['end_date'] ?? $event?->end_date?->toDateString();
            abort_unless($eventFrom >= $year->start_date->toDateString() && $eventTo <= $year->end_date->toDateString(), 422, 'Tanggal kejadian harus berada dalam tahun ajaran.');
            if ($event) {
                MasterDataService::checkVersion($event, $values['expected_updated_at']);
                unset($values['reason'], $values['expected_updated_at']);
                $before = $event->only(['title', 'event_type', 'start_date', 'end_date', 'blocks_teaching', 'blocks_employee_work']);
                $event->fill($values)->save();
                $this->audit->audit($actor, 'academic.calendar.updated', 'academic_calendar_event', $event->id, $data['reason'], $before, $event->only(['title', 'event_type', 'start_date', 'end_date']), $request);

                return $event->fresh();
            }
            $created = new AcademicCalendarEvent($values);
            $created->forceFill(['created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'academic.calendar.created', 'academic_calendar_event', $created->id, $data['reason'], null, $created->only(['title', 'event_type', 'start_date', 'end_date']), $request);

            return $created;
        });
    }

    // ------------------------------------------------------------------
    // Penugasan mengajar & wali kelas
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    public function saveTeachingAssignment(User $actor, ?int $id, array $data, Request $request): TeachingAssignment
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): TeachingAssignment {
            AcademicDataAccess::authorize($actor, 'assignments', true);
            $assignment = $id ? TeachingAssignment::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'employee_id' => [$assignment ? 'prohibited' : 'required', 'integer', 'exists:employees,id'],
                'subject_id' => [$assignment ? 'prohibited' : 'required', 'integer', 'exists:subjects,id'],
                'class_period_id' => [$assignment ? 'prohibited' : 'required', 'integer', 'exists:class_periods,id'],
                'semester_id' => [$assignment ? 'prohibited' : 'required', 'integer', 'exists:semesters,id'],
                'planned_weekly_jp' => ['nullable', 'numeric', 'min:0', 'max:99'],
                'effective_from' => [$assignment ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'effective_to' => ['nullable', 'date_format:Y-m-d'],
                'is_active' => ['sometimes', 'boolean'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$assignment ? 'required' : 'prohibited', 'date'],
            ])->validate();
            $semester = Semester::query()->lockForUpdate()->findOrFail($assignment?->semester_id ?? $values['semester_id']);
            $period = ClassPeriod::query()->lockForUpdate()->findOrFail($assignment?->class_period_id ?? $values['class_period_id']);
            $from = $values['effective_from'] ?? $assignment?->effective_from->toDateString();
            $to = $values['effective_to'] ?? $assignment?->effective_to?->toDateString() ?? null;
            self::assertRange(['effective_from' => $from, 'effective_to' => $to], null, 'effective_to');
            abort_unless($semester->academic_year_id === $period->academic_year_id, 422, 'Semester dan rombongan belajar harus berada pada tahun ajaran yang sama.');
            abort_unless($from >= $semester->start_date->toDateString()
                && $semester->end_date->toDateString() >= ($to ?? $from), 422, 'Masa penugasan harus berada dalam rentang semester.');
            $dup = TeachingAssignment::query()
                ->where('employee_id', $assignment?->employee_id ?? $values['employee_id'])
                ->where('subject_id', $assignment?->subject_id ?? $values['subject_id'])
                ->where('class_period_id', $period->id)->where('semester_id', $semester->id)
                ->whereKeyNot($assignment?->id ?? 0)
                ->where('effective_from', '<', $to ?? '9999-12-31')
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $from))
                ->lockForUpdate()->exists();
            abort_if($dup, 422, 'Penugasan untuk guru, mapel, kelas, dan semester yang sama masih tumpang tindih pada masa berlaku.');
            if ($assignment) {
                MasterDataService::checkVersion($assignment, $values['expected_updated_at']);
                unset($values['employee_id'], $values['subject_id'], $values['class_period_id'], $values['semester_id'], $values['reason'], $values['expected_updated_at']);
                $before = $assignment->only(['planned_weekly_jp', 'effective_from', 'effective_to', 'is_active']);
                $assignment->fill($values)->save();
                $this->audit->audit($actor, 'academic.assignments.updated', 'teaching_assignment', $assignment->id, $data['reason'], $before, $assignment->only(['planned_weekly_jp', 'is_active']), $request);

                return $assignment->fresh(['employee.person', 'subject', 'classPeriod.schoolClass', 'semester']);
            }
            $created = new TeachingAssignment($values);
            $created->forceFill(['created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'academic.assignments.created', 'teaching_assignment', $created->id, $data['reason'], null,
                ['employee_id' => $created->employee_id, 'subject_id' => $created->subject_id, 'class_period_id' => $period->id, 'semester_id' => $semester->id], $request);

            return $created->fresh(['employee.person', 'subject', 'classPeriod.schoolClass', 'semester']);
        });
    }

    /** @param array<string, mixed> $data */
    public function saveHomeroomAssignment(User $actor, ?int $id, array $data, Request $request): HomeroomAssignment
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): HomeroomAssignment {
            AcademicDataAccess::authorize($actor, 'assignments', true);
            $assignment = $id ? HomeroomAssignment::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'employee_id' => [$assignment ? 'prohibited' : 'required', 'integer', 'exists:employees,id'],
                'class_period_id' => [$assignment ? 'prohibited' : 'required', 'integer', 'exists:class_periods,id'],
                'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
                'effective_from' => [$assignment ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'effective_to' => [$assignment ? 'nullable' : 'prohibited', 'nullable', 'date_format:Y-m-d'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$assignment ? 'required' : 'prohibited', 'date'],
            ])->validate();
            $period = ClassPeriod::query()->lockForUpdate()->findOrFail($assignment?->class_period_id ?? $values['class_period_id']);
            if (isset($values['semester_id'])) {
                $semester = Semester::query()->findOrFail($values['semester_id']);
                abort_unless($semester->academic_year_id === $period->academic_year_id, 422, 'Semester dan rombongan belajar harus pada tahun ajaran yang sama.');
            }
            if ($assignment) {
                $from = $values['effective_from'] ?? $assignment->effective_from->toDateString();
                $to = $values['effective_to'] ?? $assignment->effective_to?->toDateString() ?? null;
                self::assertRange(['effective_from' => $from, 'effective_to' => $to], null, 'effective_to');
            }
            if ($assignment) {
                MasterDataService::checkVersion($assignment, $values['expected_updated_at']);
                unset($values['employee_id'], $values['class_period_id'], $values['reason'], $values['expected_updated_at']);
                $before = $assignment->only(['effective_to']);
                $assignment->fill($values)->save();
                $this->audit->audit($actor, 'academic.homeroom.updated', 'homeroom_assignment', $assignment->id, $data['reason'], $before, $assignment->only(['effective_to']), $request);

                return $assignment->fresh(['employee.person', 'classPeriod.schoolClass']);
            }
            $existing = HomeroomAssignment::query()->where('class_period_id', $period->id)
                ->where('effective_from', '<', $values['effective_from'])
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $values['effective_from']))
                ->lockForUpdate()->get();
            foreach ($existing as $previous) {
                $previous->forceFill(['effective_to' => $values['effective_from']])->save();
            }
            $created = new HomeroomAssignment($values);
            $created->forceFill(['created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'academic.homeroom.created', 'homeroom_assignment', $created->id, $data['reason'],
                $existing->isEmpty() ? null : ['closed_ids' => $existing->pluck('id')->all()],
                ['employee_id' => $created->employee_id, 'class_period_id' => $period->id, 'effective_from' => $values['effective_from']], $request);

            return $created->fresh(['employee.person', 'classPeriod.schoolClass']);
        });
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /** @return array<int, string> */
    private static function reasonRule(): array
    {
        return ['required', 'string', 'min:10', 'max:1000'];
    }

    /** Tanggal efektif harus berada dalam rentang tahun ajaran rombongan belajar. */
    private function assertWithinPeriodYear(ClassPeriod $period, string $date, string $label): void
    {
        $year = $period->academicYear()->lockForUpdate()->firstOrFail();
        abort_unless($date >= $year->start_date->toDateString() && $date <= $year->end_date->toDateString(), 422,
            $label.' harus berada dalam tahun ajaran rombongan belajar ('.$year->start_date->toDateString().' s.d. '.$year->end_date->toDateString().').');
    }

    /** Validasi manual akhir > awal karena field dapat parsial saat update. @param array<string, mixed> $values */
    private static function assertRange(array $values, ?string $fallbackFrom, string $toField): void
    {
        $from = $values['start_date'] ?? $values['effective_from'] ?? $fallbackFrom;
        $to = $values[$toField] ?? null;
        if ($from !== null && $to !== null && $to <= $from) {
            throw ValidationException::withMessages([$toField => 'Tanggal berakhir harus setelah tanggal mulai.']);
        }
    }

    /** @template T */
    private function mutate(callable $callback): mixed
    {
        try {
            return DB::transaction($callback, 3);
        } catch (UniqueConstraintViolationException) {
            abort(409, 'Data dengan identitas atau masa berlaku yang sama sudah ada. Muat ulang dan periksa kembali.');
        }
    }
}
