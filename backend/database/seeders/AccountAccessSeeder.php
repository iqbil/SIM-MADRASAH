<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AccountAccess;
use Illuminate\Database\Seeder;

class AccountAccessSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'super_admin' => 'Super Admin', 'operator' => 'Admin/Operator',
            'kepala_madrasah' => 'Kepala Madrasah', 'guru' => 'Guru', 'wali_kelas' => 'Wali Kelas',
            'guru_bk' => 'Guru BK', 'tenaga_kependidikan' => 'Tenaga Kependidikan',
            'bendahara' => 'Bendahara', 'petugas_tu' => 'Petugas TU', 'orang_tua_wali' => 'Orang Tua/Wali',
        ] as $code => $name) {
            Role::query()->firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
        foreach (AccountAccess::managedPermissions() as $code => $description) {
            Permission::query()->firstOrCreate(['code' => $code], [
                'module' => explode('.', $code)[0], 'action' => substr($code, strrpos($code, '.') + 1),
                'description' => $description, 'is_sensitive' => $code !== 'accounts.view',
            ]);
        }
    }
    public static function seedOperatorMasterPermissions(\App\Models\User $actor): void
    {
        $role = Role::query()->where('code', 'operator')->firstOrFail();
        foreach (Permission::query()->whereIn('code', array_keys(\App\Services\MasterDataAccess::catalog()))->get() as $permission) {
            \App\Models\RolePermission::query()->firstOrCreate(['role_id' => $role->id, 'permission_id' => $permission->id], ['created_by' => $actor->id]);
        }
    }

}
