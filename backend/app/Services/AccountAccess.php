<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountAccess
{
    public const PERMISSIONS = [
        'accounts.view' => 'Melihat akun dan role',
        'accounts.create' => 'Membuat akun pengguna',
        'accounts.update' => 'Mengubah informasi akun',
        'accounts.status' => 'Mengaktifkan atau menonaktifkan akun',
        'accounts.verify' => 'Memverifikasi email dengan bukti pemeriksaan identitas',
        'accounts.reset' => 'Mereset password sementara',
        'accounts.sessions' => 'Mencabut sesi dan tautan Google',
        'accounts.assign' => 'Memberikan dan mencabut role pengguna',
        'accounts.roles' => 'Mengelola role dan izin pengelolaan akun',
        'accounts.audit' => 'Melihat jejak audit pengelolaan akun',
    ];

    /** @return array<string, string> */
    public static function managedPermissions(): array
    {
        return [...self::PERMISSIONS, ...MasterDataAccess::catalog(), ...AttendanceDataAccess::managedPermissions(), ...TeachingDataAccess::managedPermissions()];
    }

    /** @return array<int, string> */
    public static function grantablePermissions(User $user): array
    {
        return array_values(array_unique([...self::permissions($user), ...MasterDataAccess::permissions($user),
            ...(self::isSuper($user) ? array_keys(MasterDataAccess::catalog()) : [])]));
    }

    public static function activeGrants(User $user): HasMany
    {
        $at = now('UTC')->format('Y-m-d H:i:s.u');

        return $user->roleAssignments()->where('effective_from', '<=', $at)->whereNull('revoked_by')
            ->where(fn (Builder $query): Builder => $query->whereNull('effective_to')->orWhere('effective_to', '>', $at));
    }

    /** @return array<int, string> */
    public static function permissions(User $user): array
    {
        return self::activeGrants($user)
            ->whereHas('role', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with('role.permissionAssignments.permission')->get()
            ->flatMap(fn (UserRole $grant) => $grant->role->permissionAssignments->pluck('permission.code'))
            ->filter(fn (string $code): bool => array_key_exists($code, self::PERMISSIONS))->unique()->values()->all();
    }

    public static function authorize(User $user, string $permission): void
    {
        abort_unless($user->canSignIn() && in_array($permission, self::permissions($user), true), 403, 'Anda tidak memiliki izin untuk tindakan ini.');
    }

    public static function isSuper(User $user): bool
    {
        return self::activeGrants($user)
            ->whereHas('role', fn (Builder $query): Builder => $query->where('code', 'super_admin')->where('is_active', true))->exists();
    }
}
