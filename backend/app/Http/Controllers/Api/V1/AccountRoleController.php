<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\SaveRoleRequest;
use App\Models\Role;
use App\Services\AccountAccess;
use App\Services\AccountManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountRoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        AccountAccess::authorize($request->user(), 'accounts.view');

        return response()->json(['data' => Role::query()->with('permissionAssignments.permission')->orderBy('name')->get()->map(fn (Role $role): array => [
            ...$role->only(['id', 'code', 'name', 'description', 'is_active']),
            'permission_codes' => $role->permissionAssignments->pluck('permission.code')->filter(fn ($code): bool => array_key_exists($code, AccountAccess::PERMISSIONS))->values()->all(),
        ]), 'permissions' => collect(AccountAccess::PERMISSIONS)->map(fn ($name, $code): array => ['code' => $code, 'name' => $name])->values()]);
    }

    public function store(SaveRoleRequest $request, AccountManagementService $service): JsonResponse
    {
        $role = $service->saveRole($request->user(), null, $request->validated(), $request);

        return response()->json(['data' => $role->only(['id', 'code', 'name', 'is_active'])], 201);
    }

    public function update(SaveRoleRequest $request, Role $role, AccountManagementService $service): JsonResponse
    {
        $role = $service->saveRole($request->user(), $role, $request->validated(), $request);

        return response()->json(['data' => $role->only(['id', 'code', 'name', 'is_active'])]);
    }
}
