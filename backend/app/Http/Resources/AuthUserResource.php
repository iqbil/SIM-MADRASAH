<?php

namespace App\Http\Resources;

use App\Models\UserRole;
use App\Services\AccountAccess;
use App\Services\NavigationAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $assignments = AccountAccess::activeGrants($this->resource)->with('role')->get();

        return [
            'id' => $this->id, 'username' => $this->username, 'name' => $this->name,
            'email' => $this->email, 'email_verified' => $this->email_verified_at !== null,
            'must_change_password' => $this->must_change_password,
            'permissions' => AccountAccess::permissions($this->resource),
            'navigation' => NavigationAccess::forUser($this->resource),
            'roles' => $assignments->filter(fn (UserRole $assignment): bool => $assignment->role->is_active)
                ->map(fn (UserRole $assignment): array => ['code' => $assignment->role->code, 'name' => $assignment->role->name])->unique('code')->values()->all(),
        ];
    }
}
