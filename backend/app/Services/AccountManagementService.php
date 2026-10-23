<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountManagementService
{
    /** @return array{user: User, temporary_password: ?string} */
    public function saveUser(User $actor, ?User $target, array $data, Request $request): array
    {
        return $this->mutate($actor, $target ? 'accounts.update' : 'accounts.create', function () use ($actor, $target, $data, $request): array {
            $new = $target === null;
            if ($target) {
                $target = User::query()->lockForUpdate()->findOrFail($target->id);
                abort_unless(isset($data['lock_version']) && (int) $data['lock_version'] === $target->lock_version, 409, 'Akun telah berubah. Muat ulang sebelum menyimpan.');
                $this->protect($actor, $target);
            }
            $password = $new ? $this->password() : null;
            $before = $target ? $this->snapshot($target) : null;
            if ($new) {
                $person = isset($data['person_id']) ? Person::query()->lockForUpdate()->findOrFail($data['person_id']) : Person::query()->create(['full_name' => $data['name'], 'contact_email' => $data['email']]);
                abort_unless($person->is_active && ! $person->user()->exists(), 422, 'Identitas tidak aktif atau sudah memiliki akun.');
                abort_if($person->student()->exists() && ! $person->employee()->exists() && ! $person->guardian()->exists(), 422, 'Login siswa belum diaktifkan pada rilis ini.');
                $target = new User;
                $target->forceFill(['person_id' => $person->id, 'password' => $password, 'must_change_password' => true,
                    'temporary_password_expires_at' => now('UTC')->addDay()]);
            }
            if (! $new && $target->email !== $data['email']) {
                $target->forceFill(['email_verified_at' => null]);
                $target->oauthAccounts()->delete();
            }
            $target->forceFill(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'], 'lock_version' => $target->lock_version + 1])->save();
            if (array_key_exists('role_ids', $data)) {
                $current = $this->currentRoleIds($target);
                $requested = array_map('intval', $data['role_ids']);
                sort($current);
                sort($requested);
                if ($current !== $requested) {
                    AccountAccess::authorize($actor, 'accounts.assign');
                    $this->syncRoles($actor, $target, $requested, $data['reason']);
                }
            }
            $this->audit($actor, $new ? 'accounts.user_created' : 'accounts.user_updated', 'user', $target->id, $data['reason'], $before, $this->snapshot($target), $request);

            return ['user' => $target, 'temporary_password' => $password];
        });
    }

    public function action(User $actor, User $target, string $action, array $data, Request $request): ?string
    {
        $permission = match ($action) {
            'status' => 'accounts.status', 'reset-password' => 'accounts.reset', 'verify-email' => 'accounts.verify', default => 'accounts.sessions'
        };

        return $this->mutate($actor, $permission, function () use ($actor, $target, $action, $data, $request): ?string {
            $target = User::query()->lockForUpdate()->findOrFail($target->id);
            abort_unless((int) $data['lock_version'] === $target->lock_version, 409, 'Akun telah berubah. Muat ulang sebelum melanjutkan.');
            $this->protect($actor, $target);
            $before = $this->snapshot($target);
            $password = null;
            if ($action === 'status') {
                abort_unless(isset($data['status']), 422, 'Status akun wajib diisi.');
                abort_if($target->id === $actor->id && $data['status'] === 'disabled', 422, 'Anda tidak dapat menonaktifkan akun sendiri.');
                if ($data['status'] === 'disabled') {
                    $this->keepSuperAdmin($target);
                }
                $target->forceFill(['status' => $data['status'], 'disabled_at' => $data['status'] === 'disabled' ? now('UTC') : null]);
            } elseif ($action === 'reset-password') {
                abort_if($target->id === $actor->id, 422, 'Gunakan menu ganti password untuk akun sendiri.');
                abort_unless(($data['identity_confirmed'] ?? false) === true, 422, 'Konfirmasi pemeriksaan identitas diperlukan.');
                $password = $this->password();
                $target->forceFill(['password' => $password, 'must_change_password' => true, 'temporary_password_expires_at' => now('UTC')->addDay(), 'temporary_password_used_at' => null]);
            } elseif ($action === 'verify-email') {
                abort_unless(($data['identity_confirmed'] ?? false) === true && filled($target->email), 422, 'Email dan konfirmasi pemeriksaan identitas diperlukan.');
                $target->forceFill(['email_verified_at' => now('UTC')]);
            } elseif ($action === 'unlink-google') {
                $target->oauthAccounts()->where('provider', 'google')->delete();
                $target->forceFill(['email_verified_at' => null]);
            }
            $target->forceFill(['lock_version' => $target->lock_version + 1])->save();
            $target->authSessions()->whereNull('revoked_at')->update(['revoked_at' => now('UTC')]);
            $this->audit($actor, 'accounts.'.$action, 'user', $target->id, $data['reason'], $before, $this->snapshot($target), $request);

            return $password;
        });
    }

    public function saveRole(User $actor, ?Role $role, array $data, Request $request): Role
    {
        return $this->mutate($actor, 'accounts.roles', function () use ($actor, $role, $data, $request): Role {
            if ($role) {
                $role = Role::query()->lockForUpdate()->findOrFail($role->id);
            }
            abort_if($role?->code === 'super_admin' || $data['code'] === 'super_admin', 422, 'Role Super Admin dilindungi dan dibuat melalui seeder awal.');
            abort_if(in_array($data['code'], ['siswa', 'student'], true), 422, 'Role login siswa belum diaktifkan.');
            $allowed = AccountAccess::grantablePermissions($actor);
            abort_if(array_diff($data['permission_codes'], $allowed) !== [], 403, 'Anda tidak dapat memberikan izin yang tidak Anda miliki.');
            $before = $role ? [...$role->only(['code', 'name', 'is_active']), 'permission_codes' => $role->permissionAssignments()->with('permission')->get()->pluck('permission.code')->all()] : null;
            $role ??= new Role;
            $role->fill(collect($data)->only(['code', 'name', 'description', 'is_active'])->all())->save();
            $ids = Permission::query()->whereIn('code', $data['permission_codes'])->pluck('id')->all();
            $catalog = Permission::query()->whereIn('code', array_keys(AccountAccess::managedPermissions()))->pluck('id');
            $role->permissionAssignments()->whereIn('permission_id', $catalog)->whereNotIn('permission_id', $ids)->delete();
            foreach ($ids as $id) {
                $grant = RolePermission::query()->firstOrNew(['role_id' => $role->id, 'permission_id' => $id]);
                if (! $grant->exists) {
                    $grant->forceFill(['created_by' => $actor->id])->save();
                }
            }
            $this->audit($actor, 'accounts.role_saved', 'role', $role->id, $data['reason'], $before, ['code' => $role->code, 'name' => $role->name, 'is_active' => $role->is_active, 'permission_codes' => $data['permission_codes']], $request);

            return $role;
        });
    }

    public function audit(User $actor, string $event, string $type, int $id, string $reason, ?array $before, ?array $after, Request $request): void
    {
        AuditLog::query()->forceCreate(['actor_user_id' => $actor->id, 'actor_label_snapshot' => $actor->name,
            'event' => $event, 'object_type' => $type, 'object_id' => (string) $id, 'reason' => $reason,
            'occurred_at' => now('UTC'), 'request_id' => (string) Str::uuid(), 'ip_address' => $request->ip(),
            'classification' => 'internal', 'before_values' => $before, 'after_values' => $after]);
    }

    private function mutate(User $actor, string $permission, Closure $callback): mixed
    {
        return DB::transaction(function () use ($actor, $permission, $callback): mixed {
            Role::query()->where('code', 'super_admin')->lockForUpdate()->firstOrFail();
            AccountAccess::authorize($actor->fresh(), $permission);

            return $callback();
        }, 3);
    }

    private function protect(User $actor, User $target): void
    {
        abort_if(AccountAccess::isSuper($target) && ! AccountAccess::isSuper($actor), 403, 'Akun Super Admin hanya dapat dikelola oleh Super Admin.');
    }

    private function keepSuperAdmin(User $target): void
    {
        if (! AccountAccess::isSuper($target)) {
            return;
        }
        $others = User::query()->whereKeyNot($target->id)->where('status', 'active')->whereHas('person', fn (Builder $query): Builder => $query->where('is_active', true))->get();
        abort_unless($others->contains(fn (User $user): bool => AccountAccess::isSuper($user)), 422, 'Minimal satu Super Admin aktif harus dipertahankan.');
    }

    /** @return array<int, int> */
    private function currentRoleIds(User $user): array
    {
        return AccountAccess::activeGrants($user)->pluck('role_id')->map(fn ($id): int => (int) $id)->all();
    }

    private function syncRoles(User $actor, User $target, array $ids, string $reason): void
    {
        $super = Role::query()->where('code', 'super_admin')->firstOrFail();
        abort_if(Role::query()->whereIn('id', $ids)->whereIn('code', ['siswa', 'student'])->exists(), 422, 'Login siswa belum diaktifkan.');
        $current = $this->currentRoleIds($target);
        if (in_array($super->id, $ids, true) && ! in_array($super->id, $current, true)) {
            abort_unless(AccountAccess::isSuper($actor), 403, 'Hanya Super Admin dapat memberikan role Super Admin.');
        }
        if (in_array($super->id, $current, true) && ! in_array($super->id, $ids, true)) {
            abort_if($actor->id === $target->id, 422, 'Anda tidak dapat mencabut role Super Admin sendiri.');
            $this->keepSuperAdmin($target);
        }
        $now = now('UTC');
        $at = $now->format('Y-m-d H:i:s.u');
        foreach (array_diff($current, $ids) as $id) {
            foreach ($target->roleAssignments()->where('role_id', $id)->whereNull('revoked_by')->where('effective_from', '<=', $at)->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $at))->get() as $grant) {
                $grant->forceFill(['effective_to' => $now->greaterThan($grant->effective_from) ? $now : $grant->effective_from->addMicrosecond(), 'revoked_by' => $actor->id, 'revoke_reason' => $reason])->save();
            }
        }
        if (! AccountAccess::isSuper($actor)) {
            $grantedPermissions = RolePermission::query()->whereIn('role_id', array_diff($ids, $current))->with('permission')->get()->pluck('permission.code')->all();
            abort_if(array_diff($grantedPermissions, AccountAccess::permissions($actor)) !== [], 403, 'Anda tidak dapat memberikan role dengan izin yang tidak Anda miliki.');
        }
        foreach (array_diff($ids, $current) as $id) {
            abort_if($target->roleAssignments()->where('role_id', $id)->whereNull('revoked_by')->where(fn (Builder $q): Builder => $q->whereNull('effective_to')->orWhere('effective_to', '>', $at))->exists(), 422, 'Ada penugasan role yang bertumpang tindih.');
            UserRole::query()->forceCreate(['user_id' => $target->id, 'role_id' => $id, 'effective_from' => $now, 'granted_by' => $actor->id]);
        }
    }

    private function snapshot(User $user): array
    {
        return [...$user->only(['id', 'name', 'username', 'email', 'status', 'must_change_password', 'lock_version']), 'role_ids' => $this->currentRoleIds($user), 'email_verified' => $user->email_verified_at !== null, 'google_linked' => $user->oauthAccounts()->where('provider', 'google')->exists()];
    }

    private function password(): string
    {
        return 'Aa1!'.Str::random(20);
    }
}
