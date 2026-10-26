<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Otorisasi modul Absensi Pegawai (SRS v1.3 Bab II + FR-05). Sumber kebenaran
 * tunggal token navigasi: NavigationAccess memancangkan attendance.own untuk
 * akun yang terhubung pegawai aktif, attendance.manage untuk
 * operator/super admin, attendance.correct hanya lewat mandat, dan
 * attendance.reports untuk kepala madrasah. Super admin bukan bypass mandat.
 */
class AttendanceDataAccess
{
    /** Izin absensi yang dapat diberikan lewat role/mandat (Bab II). @return array<string, string> */
    public static function managedPermissions(): array
    {
        return [
            'attendance.manage' => 'Mencatat izin/sakit/cuti/dinas luar dan fallback administratif absensi pegawai',
            'attendance.correct' => 'Menyetujui atau menolak koreksi absensi (mandat petugas)',
            'attendance.reports' => 'Membaca rekap kehadiran pegawai',
        ];
    }

    /** @return array<int, string> */
    public static function roles(User $user): array
    {
        return MasterDataAccess::roles($user);
    }

    /** @return array<int, string> */
    public static function navigation(User $user): array
    {
        return NavigationAccess::forUser($user);
    }

    /** Pegawai aktif milik akun; null bila akun bukan pegawai. */
    public static function employee(User $user): ?Employee
    {
        $employee = $user->person?->employee;
        if (! $employee || ! $employee->is_active) {
            return null;
        }
        $today = today('Asia/Jakarta')->toDateString();
        if (($employee->joined_on && $employee->joined_on->toDateString() > $today)
            || ($employee->left_on && $employee->left_on->toDateString() <= $today)) {
            return null;
        }

        return $employee;
    }

    public static function authorizeOwn(User $user): Employee
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        $employee = self::employee($user);
        abort_unless($employee !== null, 403, 'Akun tidak terhubung ke profil pegawai aktif.');

        return $employee;
    }

    public static function allows(User $user, string $token): bool
    {
        return in_array($token, self::navigation($user), true);
    }

    public static function authorizeManage(User $user): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(self::allows($user, 'attendance.manage'), 403, 'Akses pengelolaan absensi tidak diberikan untuk peran Anda.');
    }

    /** Mandat aktif permission attendance.correct milik pengguna; null bila tidak ada. @return array{id: int}|null */
    public static function correctMandate(User $user): ?array
    {
        $at = now('UTC')->format('Y-m-d H:i:s.u');
        $mandate = $user->authorizationMandates()
            ->whereNull('revoked_at')->whereNull('revoked_by')
            ->where('effective_from', '<=', $at)
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at))
            ->whereHas('permission', fn (Builder $query): Builder => $query->where('code', 'attendance.correct'))
            ->first(['id']);

        return $mandate !== null ? ['id' => $mandate->id] : null;
    }

    public static function authorizeCorrect(User $user): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(self::allows($user, 'attendance.correct'), 403, 'Persetujuan koreksi absensi memerlukan mandat petugas.');
    }

    public static function authorizeRead(User $user): void
    {
        abort_unless($user->canSignIn() && ! $user->must_change_password, 403);
        abort_unless(self::allows($user, 'attendance.manage') || self::allows($user, 'attendance.reports'), 403, 'Akses rekap kehadiran tidak diberikan untuk peran Anda.');
    }
}
