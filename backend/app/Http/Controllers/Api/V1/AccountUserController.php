<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\AccountActionRequest;
use App\Http\Requests\Accounts\SaveUserRequest;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\User;
use App\Services\AccountAccess;
use App\Services\AccountManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        AccountAccess::authorize($request->user(), 'accounts.view');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:active,disabled'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = User::query()->orderByDesc('id');
        if (filled($data['q'] ?? null)) {
            $search = '%'.$data['q'].'%';
            $query->where(fn (Builder $q): Builder => $q->where('name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('username', 'like', $search));
        }
        if (filled($data['status'] ?? null)) {
            $query->where('status', $data['status']);
        }
        $page = $query->paginate(15);

        return response()->json(['data' => $page->getCollection()->map(fn (User $user): array => $this->present($user)),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'stats' => ['total' => User::query()->count(), 'active' => User::query()->where('status', 'active')->count(), 'disabled' => User::query()->where('status', 'disabled')->count()]]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        AccountAccess::authorize($request->user(), 'accounts.view');

        return response()->json(['data' => $this->present($user, true)]);
    }

    public function store(SaveUserRequest $request, AccountManagementService $service): JsonResponse
    {
        $result = $service->saveUser($request->user(), null, $request->validated(), $request);

        return response()->json(['data' => $this->present($result['user']), 'temporary_password' => $result['temporary_password']], 201);
    }

    public function update(SaveUserRequest $request, User $user, AccountManagementService $service): JsonResponse
    {
        $result = $service->saveUser($request->user(), $user, $request->validated(), $request);

        return response()->json(['data' => $this->present($result['user'])]);
    }

    public function action(AccountActionRequest $request, User $user, string $action, AccountManagementService $service): JsonResponse
    {
        abort_unless(in_array($action, ['status', 'reset-password', 'revoke-sessions', 'unlink-google', 'verify-email'], true), 404);
        $password = $service->action($request->user(), $user, $action, $request->validated(), $request);

        return response()->json(['data' => $this->present($user->fresh(), true), 'temporary_password' => $password]);
    }

    public function people(Request $request): JsonResponse
    {
        AccountAccess::authorize($request->user(), 'accounts.create');
        $query = Person::query()->where('is_active', true)->whereDoesntHave('user')
            ->where(fn (Builder $q): Builder => $q->whereDoesntHave('student')->orWhereHas('employee')->orWhereHas('guardian'));
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        if ($request->filled('q')) {
            $query->where('full_name', 'like', '%'.$request->string('q').'%');
        }

        return response()->json(['data' => $query->orderBy('full_name')->limit(50)->get(['id', 'full_name'])]);
    }

    public function audit(Request $request): JsonResponse
    {
        AccountAccess::authorize($request->user(), 'accounts.audit');
        $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'user_id' => ['nullable', 'integer', 'min:1']]);
        $query = AuditLog::query()->where(fn (Builder $q): Builder => $q->where('event', 'like', 'accounts.%')->orWhere('event', 'like', 'auth.%'))->with('actor:id,name')->orderByDesc('occurred_at')->orderByDesc('id');
        if ($request->filled('user_id')) {
            $query->where('object_type', 'user')->where('object_id', (string) $request->integer('user_id'));
        }
        $page = $query->paginate(20);

        return response()->json(['data' => $page->getCollection()->map(fn (AuditLog $log): array => [
            'id' => $log->id, 'actor' => $log->actor_label_snapshot, 'event' => $log->event, 'object_type' => $log->object_type,
            'object_id' => $log->object_id, 'occurred_at' => $log->occurred_at->toIso8601String(), 'reason' => $log->reason,
        ]), 'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]]);
    }

    public function present(User $user, bool $detail = false): array
    {
        $now = now('UTC');
        $grants = AccountAccess::activeGrants($user)->with('role')->whereHas('role', fn (Builder $q): Builder => $q->where('is_active', true))->get();
        $data = [...$user->only(['id', 'person_id', 'name', 'username', 'email', 'status', 'lock_version', 'must_change_password']),
            'email_verified' => $user->email_verified_at !== null, 'google_linked' => $user->oauthAccounts()->where('provider', 'google')->exists(),
            'last_login_at' => $user->last_login_at?->toIso8601String(), 'created_at' => $user->created_at?->toIso8601String(),
            'roles' => $grants->map(fn ($grant): array => $grant->role->only(['id', 'code', 'name']))->values()->all(),
            'active_sessions' => $user->authSessions()->whereNull('revoked_at')->where('expires_at', '>', $now)->count()];
        if ($detail) {
            $data['sessions'] = $user->authSessions()->whereNull('revoked_at')->where('expires_at', '>', $now)->orderByDesc('id')->limit(50)->get()->map(fn ($s): array => ['id' => $s->id, 'login_method' => $s->login_method, 'last_used_at' => $s->last_used_at?->toIso8601String(), 'expires_at' => $s->expires_at->toIso8601String()])->all();
            $data['role_history'] = $user->roleAssignments()->with(['role', 'grantor:id,name', 'revoker:id,name'])->orderByDesc('effective_from')->limit(100)->get()->map(fn ($g): array => ['id' => $g->id, 'role' => $g->role->name, 'effective_from' => $g->effective_from->toIso8601String(), 'effective_to' => $g->effective_to?->toIso8601String(), 'granted_by' => $g->grantor?->name, 'revoked_by' => $g->revoker?->name, 'reason' => $g->revoke_reason])->all();
        }

        return $data;
    }
}
