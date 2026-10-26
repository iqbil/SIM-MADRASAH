<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\SubstituteAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Otorisasi modul Realisasi Mengajar (SRS v1.3 Bab II + FR-06). Guru
 * pelaksana mengajukan realisasi pada pertemuan yang menjadi tugasnya —
 * guru terjadwal atau pengganti aktif; verifikasi hanya oleh pemegang
 * mandat realizations.verify, tidak boleh pengaju yang sama.
 */
class TeachingDataAccess
{
    /** Izin yang dapat diberikan lewat mandat (menu teaching.verify). @return array<string, string> */
    public static function managedPermissions(): array
    {
        return [
            'realizations.verify' => 'Menyetujui atau menolak realisasi mengajar (mandat verifikator)',
        ];
    }

    /** @return array<int, string> */
    public static function roles(User $user): array
    {
        return MasterDataAccess::roles($user);
    }

    public static function authorizeOwn(User $user): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(in_array('teaching.own', NavigationAccess::forUser($user), true), 403,
            'Akses realisasi mengajar tidak diberikan untuk peran Anda.');
    }

    /**
     * Mandat verifikasi aktif milik pengguna (scope apa pun pada baseline);
     * null bila tidak memegang mandat realizations.verify.
     *
     * @return array{id: int}|null
     */
    public static function verifyMandate(User $user): ?array
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        $at = now('UTC')->format('Y-m-d H:i:s.u');
        $mandate = $user->authorizationMandates()
            ->whereNull('revoked_at')->whereNull('revoked_by')
            ->where('effective_from', '<=', $at)
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at))
            ->whereHas('permission', fn (Builder $query): Builder => $query->where('code', 'realizations.verify'))
            ->first(['id']);

        return $mandate ? ['id' => $mandate->id] : null;
    }

    /** @return array{id: int} */
    public static function authorizeVerify(User $user): array
    {
        $mandate = self::verifyMandate($user);
        abort_unless($mandate !== null, 403, 'Verifikasi realisasi memerlukan mandat verifikator.');

        return $mandate;
    }

    /**
     * Pertemuan hanya boleh diakses pelaksananya: guru terjadwal atau
     * pengganti aktif (FR-06-01 "pertemuan yang menjadi tugasnya").
     */
    public static function assertMeetingDuty(Meeting $meeting, User $user): void
    {
        $employeeId = AcademicDataAccess::employeeId($user);
        abort_unless($employeeId !== null, 403, 'Akun tidak terhubung ke profil pegawai aktif.');
        if ($meeting->scheduled_employee_id === $employeeId) {
            return;
        }
        $substitute = SubstituteAssignment::query()
            ->where('meeting_id', $meeting->id)->whereNull('revoked_at')
            ->where('substitute_employee_id', $employeeId)->exists();
        abort_unless($substitute, 404);
    }

    /** Batasi daftar pertemuan ke tugas pengguna (terjadwal atau mengganti). */
    public static function scopeOwnMeetings(Builder $query, User $user): Builder
    {
        $employeeId = AcademicDataAccess::employeeId($user) ?? -1;

        return $query->where(fn (Builder $q): Builder => $q
            ->where('scheduled_employee_id', $employeeId)
            ->orWhereHas('substituteAssignments', fn (Builder $s): Builder => $s
                ->whereNull('revoked_at')->where('substitute_employee_id', $employeeId)));
    }
}
