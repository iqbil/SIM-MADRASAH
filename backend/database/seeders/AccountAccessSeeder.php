<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\AccountAccess;
use App\Services\MasterDataAccess;
use Illuminate\Database\Seeder;

class AccountAccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedPermissions();
    }

    private function seedRoles(): void
    {
        foreach ([
            'super_admin' => 'Super Admin', 'operator' => 'Admin/Operator',
            'kepala_madrasah' => 'Kepala Madrasah', 'guru' => 'Guru', 'wali_kelas' => 'Wali Kelas',
            'guru_bk' => 'Guru BK', 'tenaga_kependidikan' => 'Tenaga Kependidikan',
            'bendahara' => 'Bendahara', 'petugas_tu' => 'Petugas TU', 'orang_tua_wali' => 'Orang Tua/Wali',
        ] as $code => $name) {
            Role::query()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
    }

    private function seedPermissions(): void
    {
        foreach (AccountAccess::managedPermissions() as $code => $description) {
            Permission::query()->firstOrCreate(['code' => $code], [
                'module' => explode('.', $code)[0], 'action' => substr($code, strrpos($code, '.') + 1),
                'description' => $description, 'is_sensitive' => $code !== 'accounts.view',
            ]);
        }
    }

    public static function seedOperatorMasterPermissions(User $actor): void
    {
        $role = Role::query()->where('code', 'operator')->firstOrFail();
        foreach (Permission::query()->whereIn('code', array_keys(MasterDataAccess::catalog()))->get() as $permission) {
            $grant = RolePermission::query()->firstOrNew(['role_id' => $role->id, 'permission_id' => $permission->id]);
            if (! $grant->exists) {
                $grant->forceFill(['created_by' => $actor->id])->save();
            }
        }
    }
}
