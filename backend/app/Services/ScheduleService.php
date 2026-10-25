<?php

namespace App\Services;

use App\Models\AcademicCalendarEvent;
use App\Models\Employee;
use App\Models\LessonSlot;
use App\Models\Meeting;
use App\Models\ScheduleEntry;
use App\Models\ScheduleEntryVersion;
use App\Models\Semester;
use App\Models\SubstituteAssignment;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Models\TimetableVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Jadwal pelajaran & guru pengganti (SRS v1.3 FR-04). Jadwal berversi:
 * entri diubah pada draf, penerbitan menutup versi terbit sebelumnya
 * (BR-03), dan pertemuan dibuat dari versi terbit dengan memperhitungkan
 * kalender akademik. Guru pengganti tercatat terpisah dari guru terjadwal
 * (FR-04-04) dan berlaku satu pengganti aktif per pertemuan (DEC-03).
 */
class ScheduleService
{
    private const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function __construct(private AccountManagementService $audit) {}

    /** @param array<string, mixed> $data */
    public function saveTimetable(User $actor, ?int $id, array $data, Request $request): Timetable
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): Timetable {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            $timetable = $id ? Timetable::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'semester_id' => [$timetable ? 'prohibited' : 'required', 'integer', 'exists:semesters,id'],
                'code' => [$timetable ? 'prohibited' : 'nullable', 'string', 'max:50'],
                'name' => [$timetable ? 'sometimes' : 'required', 'string', 'max:150'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$timetable ? 'required' : 'prohibited', 'date'],
            ])->validate();
            if ($timetable) {
                MasterDataService::checkVersion($timetable, $values['expected_updated_at']);
                unset($values['semester_id'], $values['code'], $values['reason'], $values['expected_updated_at']);
                $before = $timetable->only(['name']);
                $timetable->fill($values)->save();
                $this->audit->audit($actor, 'academic.schedules.updated', 'timetable', $timetable->id, $data['reason'], $before, $timetable->only(['name']), $request);

                return $timetable->fresh(['semester.academicYear']);
            }
            $semester = Semester::query()->lockForUpdate()->findOrFail($values['semester_id']);
            $created = new Timetable([
                'semester_id' => $semester->id,
                'code' => $values['code'] ?? 'MAIN',
                'name' => $values['name'],
            ]);
            $created->forceFill(['created_by' => $actor->id])->save();
            $this->audit->audit($actor, 'academic.schedules.created', 'timetable', $created->id, $data['reason'], null,
                ['semester_id' => $semester->id, 'code' => $created->code], $request);

            return $created->fresh(['semester.academicYear']);
        });
    }

    /** @param array<string, mixed> $data */
    public function saveVersion(User $actor, int $timetableId, ?int $versionId, array $data, Request $request): TimetableVersion
    {
        return $this->mutate(function () use ($actor, $timetableId, $versionId, $data, $request): TimetableVersion {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            $timetable = Timetable::query()->lockForUpdate()->findOrFail($timetableId);
            $version = $versionId ? TimetableVersion::query()->where('timetable_id', $timetable->id)->lockForUpdate()->findOrFail($versionId) : null;
            $values = Validator::make($data, [
                'effective_from' => [$version ? 'sometimes' : 'required', 'date_format:Y-m-d'],
                'effective_to' => [$version ? 'sometimes' : 'prohibited', 'nullable', 'date_format:Y-m-d'],
                'copy_from_version_id' => [$version ? 'prohibited' : 'nullable', 'integer'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$version ? 'required' : 'prohibited', 'date'],
            ])->validate();
            $semester = $timetable->semester()->lockForUpdate()->firstOrFail();
            $from = $values['effective_from'] ?? $version?->effective_from?->toDateString();
            $to = array_key_exists('effective_to', $values) ? $values['effective_to'] : $version?->effective_to?->toDateString();
            if ($to !== null && $to <= $from) {
                throw ValidationException::withMessages(['effective_to' => 'Tanggal berakhir harus setelah tanggal mulai.']);
            }
            abort_unless($from >= $semester->start_date->toDateString() && $semester->end_date->toDateString() >= ($to ?? $from), 422,
                'Masa berlaku versi harus berada dalam rentang semester ('.$semester->start_date->toDateString().' s.d. '.$semester->end_date->toDateString().').');
            if ($version) {
                abort_unless($version->status === 'draft', 422, 'Hanya versi draf yang dapat diubah. Terbitkan versi baru untuk perubahan berikutnya.');
                MasterDataService::checkVersion($version, $values['expected_updated_at']);
                unset($values['reason'], $values['copy_from_version_id'], $values['expected_updated_at']);
                $before = $version->only(['effective_from', 'effective_to']);
                $version->fill($values)->save();
                $this->audit->audit($actor, 'academic.schedules.version_updated', 'timetable_version', $version->id, $data['reason'], $before, $version->only(['effective_from', 'effective_to']), $request);

                return $version;
            }
            $previous = TimetableVersion::query()->where('timetable_id', $timetable->id)->where('status', 'published')
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from))
                ->lockForUpdate()->first();
            if ($previous && $previous->effective_from->toDateString() >= $from) {
                abort(422, 'Versi baru harus berlaku setelah versi terbit saat ini ('.$previous->effective_from->toDateString().').');
            }
            $number = (int) TimetableVersion::query()->where('timetable_id', $timetable->id)->lockForUpdate()->max('version_number') + 1;
            $created = new TimetableVersion(['timetable_id' => $timetable->id, 'version_number' => $number, 'effective_from' => $from]);
            $created->forceFill(['created_by' => $actor->id])->save();
            if (! empty($values['copy_from_version_id'])) {
                $source = TimetableVersion::query()->where('timetable_id', $timetable->id)->findOrFail($values['copy_from_version_id']);
                $copied = 0;
                foreach ($source->entries()->with('teachingAssignment')->get() as $entryVersion) {
                    $clone = $entryVersion->replicate();
                    $clone->fill(['schedule_entry_id' => $entryVersion->schedule_entry_id, 'timetable_version_id' => $created->id,
                        'teaching_assignment_id' => $entryVersion->teaching_assignment_id]);
                    $clone->save();
                    $copied++;
                }
            }
            $this->audit->audit($actor, 'academic.schedules.version_created', 'timetable_version', $created->id, $data['reason'], null,
                ['timetable_id' => $timetable->id, 'version_number' => $number, 'effective_from' => $from, 'copied_entries' => $copied ?? 0], $request);

            return $created;
        });
    }

    /** @param array<string, mixed> $data */
    public function publishVersion(User $actor, int $versionId, array $data, Request $request): TimetableVersion
    {
        return $this->mutate(function () use ($actor, $versionId, $data, $request): TimetableVersion {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            Validator::make($data, ['reason' => self::reasonRule()])->validate();
            $version = TimetableVersion::query()->lockForUpdate()->findOrFail($versionId);
            abort_unless($version->status === 'draft', 422, 'Hanya versi draf yang dapat diterbitkan.');
            $timetable = Timetable::query()->lockForUpdate()->findOrFail($version->timetable_id);
            $semester = $timetable->semester()->lockForUpdate()->firstOrFail();
            $from = $version->effective_from->toDateString();
            abort_unless($from >= $semester->start_date->toDateString() && $semester->end_date->toDateString() >= $from, 422,
                'Tanggal mulai versi harus berada dalam rentang semester.');
            $entries = $version->entries()->with('teachingAssignment')->get();
            abort_if($entries->isEmpty(), 422, 'Versi tidak memiliki entri jadwal. Tambahkan entri sebelum menerbitkan.');
            $previous = TimetableVersion::query()->where('timetable_id', $timetable->id)->where('id', '!=', $version->id)
                ->where('status', 'published')
                ->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $from))
                ->lockForUpdate()->get();
            foreach ($previous as $old) {
                abort_if($old->effective_from->toDateString() >= $from, 422, 'Versi terbit saat ini berlaku mulai '.$old->effective_from->toDateString().'; versi baru harus setelahnya.');
            }
            $this->assertNoConflicts($semester->id, $version, $entries, null);
            foreach ($previous as $old) {
                $old->forceFill(['effective_to' => CarbonImmutable::parse($from)->subDay()->toDateString(), 'status' => 'superseded'])->save();
            }
            $version->forceFill(['status' => 'published', 'effective_to' => null, 'published_by' => $actor->id, 'published_at' => now('UTC')])->save();
            $this->audit->audit($actor, 'academic.schedules.version_published', 'timetable_version', $version->id, $data['reason'],
                $previous->isEmpty() ? null : ['superseded_ids' => $previous->pluck('id')->all()],
                ['timetable_id' => $timetable->id, 'effective_from' => $from, 'entries' => $entries->count()], $request);

            return $version->fresh(['timetable.semester.academicYear']);
        });
    }

    /** @param array<string, mixed> $data */
    public function saveEntry(User $actor, int $versionId, ?int $entryId, array $data, Request $request): ScheduleEntryVersion
    {
        return $this->mutate(function () use ($actor, $versionId, $entryId, $data, $request): ScheduleEntryVersion {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            $version = TimetableVersion::query()->lockForUpdate()->findOrFail($versionId);
            abort_unless($version->status === 'draft', 422, 'Entri hanya dapat diubah pada versi draf.');
            $entry = $entryId ? ScheduleEntryVersion::query()->where('timetable_version_id', $version->id)->lockForUpdate()->findOrFail($entryId) : null;
            $values = Validator::make($data, [
                'teaching_assignment_id' => [$entry ? 'sometimes' : 'required', 'integer', 'exists:teaching_assignments,id'],
                'lesson_slot_ids' => ['nullable', 'array', 'min:1'],
                'lesson_slot_ids.*' => ['integer', 'exists:lesson_slots,id'],
                'day_of_week' => ['nullable', 'integer', 'between:1,7'],
                'start_time' => ['nullable', 'date_format:H:i'],
                'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
                'planned_jp' => ['nullable', 'numeric', 'min:0.001', 'max:99'],
                'room' => ['nullable', 'string', 'max:100'],
                'effective_from' => ['nullable', 'date_format:Y-m-d'],
                'effective_to' => ['nullable', 'date_format:Y-m-d'],
                'reason' => self::reasonRule(),
                'expected_updated_at' => [$entry ? 'required' : 'prohibited', 'date'],
            ])->validate();
            $assignment = TeachingAssignment::query()->findOrFail($entry?->teaching_assignment_id ?? $values['teaching_assignment_id']);
            $timetable = Timetable::query()->findOrFail($version->timetable_id);
            $semester = $timetable->semester()->firstOrFail();
            abort_unless($assignment->semester_id === $semester->id, 422, 'Penugasan harus berasal dari semester jadwal yang sama.');
            $versionFrom = $version->effective_from->toDateString();
            $versionTo = $version->effective_to?->toDateString();
            $efFrom = $values['effective_from'] ?? $entry?->effective_from?->toDateString() ?? $versionFrom;
            $efTo = array_key_exists('effective_to', $values) ? $values['effective_to'] : ($entry?->effective_to?->toDateString() ?? $versionTo);
            if ($efFrom < $versionFrom || ($versionTo !== null && ($efTo ?? $versionTo) > $versionTo) || ($efTo !== null && $efTo <= $efFrom)) {
                throw ValidationException::withMessages(['effective_from' => 'Masa berlaku entri harus di dalam rentang versi dan tanggal akhir harus setelah tanggal mulai.']);
            }
            abort_unless($assignment->is_active && $assignment->effective_from->toDateString() <= $efFrom
                && ($assignment->effective_to === null || $assignment->effective_to->toDateString() >= ($efTo ?? $efFrom)), 422,
                'Masa penugasan guru tidak mencakup masa berlaku entri.');
            if (! empty($values['lesson_slot_ids'])) {
                $slots = LessonSlot::query()->whereKey($values['lesson_slot_ids'])->orderBy('sequence')->lockForUpdate()->get();
                abort_unless($slots->count() === count($values['lesson_slot_ids'])
                    && $slots->every(fn (LessonSlot $slot): bool => $slot->semester_id === $semester->id && $slot->is_active && ! $slot->is_break)
                    && $slots->pluck('day_of_week')->unique()->count() === 1, 422, 'Slot pelajaran harus dari semester yang sama, aktif, bukan istirahat, dan satu hari yang sama.');
                $day = $slots->first()->day_of_week;
                $start = $slots->min('start_time');
                $end = $slots->max('end_time');
                $jp = round((float) $slots->sum('jp_units'), 3);
                $minutes = (int) $slots->sum(fn (LessonSlot $slot): int => CarbonImmutable::parse($slot->start_time)->diffInMinutes(CarbonImmutable::parse($slot->end_time)));
            } else {
                foreach (['day_of_week', 'start_time', 'end_time', 'planned_jp'] as $required) {
                    if (($values[$required] ?? null) === null && ($entry?->{$required} ?? null) === null) {
                        throw ValidationException::withMessages([$required => 'Kolom ini wajib diisi bila slot pelajaran tidak dipilih.']);
                    }
                }
                $day = $values['day_of_week'] ?? $entry?->day_of_week;
                $start = $values['start_time'] ?? $entry?->start_time_snapshot;
                $end = $values['end_time'] ?? $entry?->end_time_snapshot;
                $jp = (float) ($values['planned_jp'] ?? $entry?->planned_jp);
                $minutes = (int) CarbonImmutable::parse($start)->diffInMinutes(CarbonImmutable::parse($end));
                abort_unless($minutes > 0, 422, 'Jam akhir harus setelah jam mulai.');
            }
            $this->assertNoConflicts($semester->id, $version, null, [
                'entry_id' => $entry?->id, 'assignment' => $assignment, 'day' => $day, 'start' => $start, 'end' => $end,
            ]);
            $payload = [
                'teaching_assignment_id' => $assignment->id, 'day_of_week' => $day,
                'start_time_snapshot' => $start, 'end_time_snapshot' => $end,
                'planned_jp' => $jp, 'planned_minutes' => $minutes,
                'room' => $values['room'] ?? null, 'effective_from' => $efFrom, 'effective_to' => $efTo,
            ];
            if ($entry) {
                MasterDataService::checkVersion($entry, $values['expected_updated_at']);
                $before = $entry->only(['day_of_week', 'start_time_snapshot', 'end_time_snapshot', 'planned_jp', 'room']);
                $entry->fill($payload)->save();
                $this->syncEntrySlots($entry, $slots ?? collect());
                $this->audit->audit($actor, 'academic.schedules.entry_updated', 'schedule_entry_version', $entry->id, $data['reason'], $before,
                    ['timetable_version_id' => $version->id], $request);

                return $entry->fresh(['teachingAssignment.employee.person', 'teachingAssignment.subject', 'teachingAssignment.classPeriod.schoolClass', 'slots']);
            }
            $parent = new ScheduleEntry(['timetable_id' => $timetable->id, 'entry_key' => (string) Str::uuid()]);
            $parent->forceFill(['created_by' => $actor->id])->save();
            $created = new ScheduleEntryVersion(['schedule_entry_id' => $parent->id, 'timetable_version_id' => $version->id] + $payload);
            $created->save();
            $this->syncEntrySlots($created, $slots ?? collect());
            $this->audit->audit($actor, 'academic.schedules.entry_created', 'schedule_entry_version', $created->id, $data['reason'], null,
                ['timetable_version_id' => $version->id, 'teaching_assignment_id' => $assignment->id], $request);

            return $created->fresh(['teachingAssignment.employee.person', 'teachingAssignment.subject', 'teachingAssignment.classPeriod.schoolClass', 'slots']);
        });
    }

    /** @param array<string, mixed> $data */
    public function removeEntry(User $actor, int $entryId, array $data, Request $request): array
    {
        return $this->mutate(function () use ($actor, $entryId, $data, $request): array {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            $values = Validator::make($data, ['reason' => self::reasonRule()])->validate();
            $entry = ScheduleEntryVersion::query()->lockForUpdate()->findOrFail($entryId);
            $version = TimetableVersion::query()->lockForUpdate()->findOrFail($entry->timetable_version_id);
            abort_unless($version->status === 'draft', 422, 'Entri pada versi terbit tidak dapat dihapus. Buat versi draf baru.');
            abort_if($entry->meetings()->exists(), 422, 'Entri sudah memiliki pertemuan yang dijadwalkan.');
            $entry->slots()->delete();
            $entry->delete();
            $this->audit->audit($actor, 'academic.schedules.entry_removed', 'schedule_entry_version', $entryId, $values['reason'],
                ['timetable_version_id' => $version->id, 'schedule_entry_id' => $entry->schedule_entry_id], null, $request);

            return ['removed' => true];
        });
    }

    /** @param array<string, mixed> $data */
    public function saveLessonSlot(User $actor, ?int $id, array $data, Request $request): LessonSlot
    {
        return $this->mutate(function () use ($actor, $id, $data, $request): LessonSlot {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            $slot = $id ? LessonSlot::query()->lockForUpdate()->findOrFail($id) : null;
            $values = Validator::make($data, [
                'semester_id' => [$slot ? 'prohibited' : 'required', 'integer', 'exists:semesters,id'],
                'day_of_week' => ['required', 'integer', 'between:1,7'],
                'label' => ['required', 'string', 'max:50'],
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
                'jp_units' => ['required', 'numeric', 'min:0', 'max:9'],
                'is_break' => ['sometimes', 'boolean'],
                'is_active' => ['sometimes', 'boolean'],
                'reason' => self::reasonRule(),
            ])->validate();
            if ($slot) {
                unset($values['semester_id'], $values['reason']);
                $before = $slot->only(['label', 'start_time', 'end_time', 'is_active']);
                $slot->fill($values)->save();
                $this->audit->audit($actor, 'academic.schedules.slot_updated', 'lesson_slot', $slot->id, $data['reason'], $before, $slot->only(['label', 'is_active']), $request);

                return $slot;
            }
            $semester = Semester::query()->lockForUpdate()->findOrFail($values['semester_id']);
            $sequence = (int) LessonSlot::query()->where('semester_id', $semester->id)->where('day_of_week', $values['day_of_week'])->lockForUpdate()->max('sequence') + 1;
            $created = new LessonSlot($values + ['sequence' => $sequence]);
            $created->save();
            $this->audit->audit($actor, 'academic.schedules.slot_created', 'lesson_slot', $created->id, $data['reason'], null,
                ['semester_id' => $semester->id, 'day_of_week' => $values['day_of_week']], $request);

            return $created;
        });
    }

    /**
     * Buat pertemuan bertanggal dari versi terbit (FR-04-03). Tanggal yang
     * diblokir kalender akademik dilewati; generator idempoten.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, int>
     */
    public function generateMeetings(User $actor, int $versionId, array $data, Request $request): array
    {
        return $this->mutate(function () use ($actor, $versionId, $data, $request): array {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            $values = Validator::make($data, [
                'date_from' => ['nullable', 'date_format:Y-m-d'],
                'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
                'reason' => self::reasonRule(),
            ])->validate();
            $version = TimetableVersion::query()->lockForUpdate()->findOrFail($versionId);
            abort_unless($version->status === 'published', 422, 'Pertemuan hanya dihasilkan dari versi terbit.');
            $timetable = Timetable::query()->lockForUpdate()->findOrFail($version->timetable_id);
            $semester = $timetable->semester()->firstOrFail();
            $from = CarbonImmutable::parse(max($values['date_from'] ?? $version->effective_from->toDateString(), $version->effective_from->toDateString(), $semester->start_date->toDateString()));
            $openEnd = $version->effective_to?->toDateString() ?? $semester->end_date->toDateString();
            $to = CarbonImmutable::parse(min($values['date_to'] ?? $openEnd, $openEnd, $semester->end_date->toDateString()));
            abort_if($to->lessThan($from), 422, 'Rentang tanggal tidak berada dalam masa berlaku versi dan semester.');
            $entries = $version->entries()->with('teachingAssignment')->get();
            $blocks = AcademicCalendarEvent::query()->where('blocks_teaching', true)
                ->where('start_date', '<=', $to->toDateString())->where('end_date', '>=', $from->toDateString())
                ->where(fn (Builder $q): Builder => $q->whereNull('semester_id')->orWhere('semester_id', $semester->id))->get();
            $existing = Meeting::query()->whereIn('schedule_entry_version_id', $entries->pluck('id'))
                ->whereBetween('meeting_date', [$from->toDateString(), $to->toDateString()])->get(['schedule_entry_id', 'meeting_date']);
            $existingKeys = $existing->map(fn (Meeting $meeting): string => $meeting->schedule_entry_id.'|'.$meeting->meeting_date->toDateString())->all();
            $created = $blocked = $skipped = 0;
            foreach ($entries as $entry) {
                $assignment = $entry->teachingAssignment;
                for ($date = $from; $date->lessThanOrEqualTo($to); $date = $date->addDay()) {
                    if ($date->dayOfWeekIso !== $entry->day_of_week
                        || $date->toDateString() < $entry->effective_from->toDateString()
                        || ($entry->effective_to !== null && $date->toDateString() > $entry->effective_to->toDateString())) {
                        continue;
                    }
                    if (in_array($entry->schedule_entry_id.'|'.$date->toDateString(), $existingKeys, true)) {
                        $skipped++;

                        continue;
                    }
                    $event = $blocks->first(fn (AcademicCalendarEvent $candidate): bool => $candidate->start_date->toDateString() <= $date->toDateString()
                        && $candidate->end_date->toDateString() >= $date->toDateString()
                        && ($candidate->class_period_id === null || $candidate->class_period_id === $assignment->class_period_id));
                    if ($event) {
                        $blocked++;

                        continue;
                    }
                    $meeting = new Meeting([
                        'schedule_entry_id' => $entry->schedule_entry_id, 'schedule_entry_version_id' => $entry->id,
                        'meeting_date' => $date->toDateString(), 'occurrence_number' => 1,
                        'semester_id' => $semester->id, 'class_period_id' => $assignment->class_period_id,
                        'subject_id' => $assignment->subject_id, 'scheduled_employee_id' => $assignment->employee_id,
                        'start_time_snapshot' => $entry->start_time_snapshot, 'end_time_snapshot' => $entry->end_time_snapshot,
                        'planned_jp_snapshot' => $entry->planned_jp, 'planned_minutes_snapshot' => $entry->planned_minutes,
                    ]);
                    $meeting->forceFill(['created_by' => $actor->id])->save();
                    $existingKeys[] = $entry->schedule_entry_id.'|'.$date->toDateString();
                    $created++;
                }
            }
            $this->audit->audit($actor, 'academic.schedules.meetings_generated', 'timetable_version', $version->id, $values['reason'],
                null, ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'created' => $created, 'blocked' => $blocked, 'skipped' => $skipped], $request);

            return ['created' => $created, 'blocked' => $blocked, 'skipped_existing' => $skipped, 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()];
        });
    }

    /** @param array<string, mixed> $data */
    public function cancelMeeting(User $actor, int $meetingId, array $data, Request $request): Meeting
    {
        return $this->mutate(function () use ($actor, $meetingId, $data, $request): Meeting {
            AcademicDataAccess::authorize($actor, 'schedules', true);
            $values = Validator::make($data, ['reason' => self::reasonRule(), 'expected_updated_at' => ['required', 'date']])->validate();
            $meeting = Meeting::query()->lockForUpdate()->findOrFail($meetingId);
            abort_if($meeting->is_cancelled, 422, 'Pertemuan sudah dibatalkan sebelumnya.');
            MasterDataService::checkVersion($meeting, $values['expected_updated_at']);
            $meeting->forceFill(['is_cancelled' => true, 'cancellation_reason' => $values['reason']])->save();
            $substitute = $meeting->activeSubstitute()->lockForUpdate()->first();
            if ($substitute) {
                $substitute->forceFill(['revoked_at' => now('UTC'), 'revoked_by' => $actor->id, 'revoke_reason' => 'Pertemuan dibatalkan: '.$values['reason']])->save();
            }
            $this->audit->audit($actor, 'academic.schedules.meeting_cancelled', 'meeting', $meeting->id, $values['reason'],
                ['is_cancelled' => false], ['is_cancelled' => true, 'substitute_revoked_id' => $substitute?->id], $request);

            return $meeting->fresh(['classPeriod.schoolClass', 'subject', 'scheduledEmployee.person', 'activeSubstitute.substituteEmployee.person']);
        });
    }

    /** @param array<string, mixed> $data */
    public function assignSubstitute(User $actor, int $meetingId, array $data, Request $request): SubstituteAssignment
    {
        return $this->mutate(function () use ($actor, $meetingId, $data, $request): SubstituteAssignment {
            AcademicDataAccess::authorize($actor, 'substitutes', true);
            $values = Validator::make($data, [
                'substitute_employee_id' => ['required', 'integer', 'exists:employees,id'],
                'reason' => self::reasonRule(),
            ])->validate();
            $meeting = Meeting::query()->lockForUpdate()->findOrFail($meetingId);
            abort_if($meeting->is_cancelled, 422, 'Pertemuan yang dibatalkan tidak dapat diberi guru pengganti.');
            $substitute = Employee::query()->lockForUpdate()->findOrFail($values['substitute_employee_id']);
            abort_unless($substitute->is_active, 422, 'Guru pengganti harus pegawai aktif.');
            abort_if($substitute->id === $meeting->scheduled_employee_id, 422, 'Guru pengganti tidak boleh sama dengan guru terjadwal.');
            $conflict = Meeting::query()->where('meeting_date', $meeting->meeting_date->toDateString())->whereKeyNot($meeting->id)
                ->where('is_cancelled', false)
                ->where(fn (Builder $q): Builder => $q->where('scheduled_employee_id', $substitute->id)
                    ->orWhereHas('substituteAssignments', fn (Builder $s): Builder => $s->whereNull('revoked_at')->where('substitute_employee_id', $substitute->id)))
                ->where(fn (Builder $q): Builder => $q->where('start_time_snapshot', '<', $meeting->end_time_snapshot)
                    ->where('end_time_snapshot', '>', $meeting->start_time_snapshot))
                ->lockForUpdate()->exists();
            abort_if($conflict, 422, 'Guru pengganti sudah memiliki pertemuan lain yang bertepatan waktu pada tanggal tersebut.');
            // Penunjukan baru menutup pengganti aktif lama; riwayat tetap utuh.
            $previousActive = SubstituteAssignment::query()->where('meeting_id', $meeting->id)->whereNull('revoked_at')->lockForUpdate()->first();
            if ($previousActive) {
                $previousActive->forceFill(['revoked_at' => now('UTC'), 'revoked_by' => $actor->id, 'revoke_reason' => 'Digantikan penunjukan guru pengganti baru.'])->save();
            }
            $created = new SubstituteAssignment(['meeting_id' => $meeting->id, 'substitute_employee_id' => $substitute->id, 'reason' => $values['reason']]);
            $created->forceFill(['assigned_by' => $actor->id, 'assigned_at' => now('UTC')])->save();
            $this->audit->audit($actor, 'academic.substitutes.assigned', 'substitute_assignment', $created->id, $values['reason'], null,
                ['meeting_id' => $meeting->id, 'substitute_employee_id' => $substitute->id, 'replaced_id' => $previousActive?->id], $request);

            return $created->fresh(['meeting.classPeriod.schoolClass', 'meeting.subject', 'substituteEmployee.person']);
        });
    }

    /** @param array<string, mixed> $data */
    public function revokeSubstitute(User $actor, int $substituteId, array $data, Request $request): SubstituteAssignment
    {
        return $this->mutate(function () use ($actor, $substituteId, $data, $request): SubstituteAssignment {
            AcademicDataAccess::authorize($actor, 'substitutes', true);
            $values = Validator::make($data, ['reason' => self::reasonRule()])->validate();
            $substitute = SubstituteAssignment::query()->lockForUpdate()->findOrFail($substituteId);
            abort_if($substitute->revoked_at !== null, 422, 'Penunjukan guru pengganti sudah dicabut sebelumnya.');
            $substitute->forceFill(['revoked_at' => now('UTC'), 'revoked_by' => $actor->id, 'revoke_reason' => $values['reason']])->save();
            $this->audit->audit($actor, 'academic.substitutes.revoked', 'substitute_assignment', $substitute->id, $values['reason'],
                ['revoked_at' => null], ['revoked_at' => $substitute->revoked_at->toIso8601String()], $request);

            return $substitute->fresh(['meeting.classPeriod.schoolClass', 'meeting.subject', 'substituteEmployee.person']);
        });
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * Tolak benturan waktu guru/kelas (FR-04-02). Saat menerbitkan versi,
     * periksa entri baru terhadap semua versi terbit lain pada semester
     * yang masih berlaku; saat menyimpan entri, periksa versi yang sama.
     *
     * @param  iterable<int, ScheduleEntryVersion>|null  $publishEntries
     * @param  array<string, mixed>|null  $single
     */
    private function assertNoConflicts(int $semesterId, TimetableVersion $version, ?iterable $publishEntries, ?array $single): void
    {
        if ($single !== null) {
            $assignment = $single['assignment'];
            $conflict = ScheduleEntryVersion::query()->where('timetable_version_id', $version->id)->whereKeyNot($single['entry_id'] ?? 0)
                ->where('day_of_week', $single['day'])
                ->whereHas('teachingAssignment', fn (Builder $a): Builder => $a->where(fn (Builder $x): Builder => $x
                    ->where('employee_id', $assignment->employee_id)->orWhere('class_period_id', $assignment->class_period_id)))
                ->where('start_time_snapshot', '<', $single['end'])->where('end_time_snapshot', '>', $single['start'])
                ->lockForUpdate()->with('teachingAssignment.employee.person', 'teachingAssignment.classPeriod.schoolClass')->first();
            if ($conflict) {
                $other = $conflict->teachingAssignment;
                abort(422, 'Benturan jadwal: '.($other->employee_id === $assignment->employee_id ? 'guru' : 'kelas')
                    .' sudah terjadwal pada '.self::DAYS[$single['day']].' '.$single['start'].'–'.$single['end']
                    .' ('.$other->employee?->person?->full_name.' · '.($other->classPeriod?->display_name ?? $other->class_period_id).').');
            }

            return;
        }
        $others = ScheduleEntryVersion::query()->whereHas('timetableVersion.timetable', fn (Builder $t): Builder => $t->where('semester_id', $semesterId))
            ->whereHas('timetableVersion', fn (Builder $v): Builder => $v->where('status', 'published')->where('id', '!=', $version->id)
                ->where(fn (Builder $x): Builder => $x->whereNull('effective_to')->orWhere('effective_to', '>=', $version->effective_from->toDateString())))
            ->with('teachingAssignment')->get();
        $byKey = [];
        foreach ($publishEntries as $entry) {
            $assignment = $entry->teachingAssignment;
            $byKey[] = ['entry' => $entry, 'assignment' => $assignment];
        }
        foreach ($byKey as $index => $item) {
            foreach ($byKey as $other => $value) {
                if ($other === $index) {
                    continue;
                }
                if ($this->entriesConflict($item, $value)) {
                    abort(422, 'Benturan jadwal pada versi ini: '.self::describeConflict($item, $value));
                }
            }
            foreach ($others as $existing) {
                if ($this->entriesConflict($item, ['entry' => $existing, 'assignment' => $existing->teachingAssignment])) {
                    abort(422, 'Benturan dengan versi terbit lain: '.self::describeConflict($item, ['entry' => $existing, 'assignment' => $existing->teachingAssignment]));
                }
            }
        }
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private function entriesConflict(array $left, array $right): bool
    {
        /** @var ScheduleEntryVersion $leftEntry */
        $leftEntry = $left['entry'];
        /** @var ScheduleEntryVersion $rightEntry */
        $rightEntry = $right['entry'];

        // Entri dengan identitas logis sama adalah kelanjutan entri lama pada
        // versi pengganti, bukan benturan (BR-03).
        if ($leftEntry->schedule_entry_id === $rightEntry->schedule_entry_id) {
            return false;
        }

        return $leftEntry->day_of_week === $rightEntry->day_of_week
            && ($left['assignment']->employee_id === $right['assignment']->employee_id || $left['assignment']->class_period_id === $right['assignment']->class_period_id)
            && $leftEntry->start_time_snapshot < $rightEntry->end_time_snapshot
            && $leftEntry->end_time_snapshot > $rightEntry->start_time_snapshot;
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private static function describeConflict(array $left, array $right): string
    {
        /** @var ScheduleEntryVersion $entry */
        $entry = $left['entry'];
        /** @var TeachingAssignment $assignment */
        $assignment = $right['assignment'];

        return self::DAYS[$entry->day_of_week].' '.$entry->start_time_snapshot.'–'.$entry->end_time_snapshot
            .' vs '.($assignment->employee?->person?->full_name ?? 'guru #'.$assignment->employee_id)
            .' · '.($assignment->classPeriod?->display_name ?? 'kelas #'.$assignment->class_period_id).'.';
    }

    /** @param iterable<int, LessonSlot> $slots */
    private function syncEntrySlots(ScheduleEntryVersion $entry, iterable $slots): void
    {
        $entry->slots()->delete();
        $position = 1;
        foreach ($slots as $slot) {
            $entry->slots()->create([
                'lesson_slot_id' => $slot->id, 'position' => $position++,
                'start_time_snapshot' => $slot->start_time, 'end_time_snapshot' => $slot->end_time, 'jp_snapshot' => $slot->jp_units,
            ]);
        }
    }

    /** @return array<int, string> */
    private static function reasonRule(): array
    {
        return ['required', 'string', 'min:10', 'max:1000'];
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
