<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\MasterStatusRequest;
use App\Http\Requests\Master\SaveMasterRequest;
use App\Http\Resources\MasterDataResource;
use App\Models\Institution;
use App\Models\Person;
use App\Services\MasterDataAccess;
use App\Services\MasterDataCatalog;
use App\Services\MasterDataService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterDataController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        $options = MasterDataAccess::options($request->user());
        abort_unless($options['resources'] !== [], 403);

        return response()->json(['data' => $options]);
    }

    public function institution(Request $request): JsonResponse|MasterDataResource
    {
        MasterDataAccess::authorize($request->user(), 'institution');
        $institution = Institution::query()->first();

        return $institution ? new MasterDataResource($institution) : response()->json(['data' => null]);
    }

    public function saveInstitution(SaveMasterRequest $request, MasterDataService $service): MasterDataResource
    {
        return new MasterDataResource($service->save($request->user(), 'institution', null, $request->validated(), $request));
    }

    public function index(Request $request, string $resource): AnonymousResourceCollection
    {
        MasterDataAccess::authorize($request->user(), $resource);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'is_active' => ['nullable', 'boolean'], 'domain' => ['nullable', 'in:employee,student']]);
        $query = $this->query($request, $resource);
        if (filled($data['q'] ?? null)) {
            $search = '%'.addcslashes($data['q'], '%_\\').'%';
            if (MasterDataCatalog::hasPerson($resource)) {
                $code = $resource === 'employees' ? 'employee_number' : 'internal_code';
                $query->where(fn (Builder $q): Builder => $q->where($code, 'like', $search)->orWhereHas('person', fn (Builder $p): Builder => $p->where('full_name', 'like', $search)));
            } else {
                $query->where(fn (Builder $q): Builder => $q->where('name', 'like', $search)->orWhere('code', 'like', $search));
            }
        }
        if (isset($data['is_active'])) {
            $resource === 'students' ? ($data['is_active'] ? $query->whereNull('archived_at') : $query->whereNotNull('archived_at')) : $query->where('is_active', $data['is_active']);
        }
        if ($resource === 'attendance-statuses' && isset($data['domain'])) {
            $query->where('domain', $data['domain']);
        }

        return MasterDataResource::collection($query->orderBy('id')->paginate($data['per_page'] ?? 25)->withQueryString());
    }

    public function show(Request $request, string $resource, int $record): MasterDataResource
    {
        MasterDataAccess::authorize($request->user(), $resource);

        return new MasterDataResource($this->query($request, $resource)->findOrFail($record));
    }

    public function store(SaveMasterRequest $request, string $resource, MasterDataService $service): JsonResponse
    {
        return (new MasterDataResource($service->save($request->user(), $resource, null, $request->validated(), $request)))->response()->setStatusCode(201);
    }

    public function update(SaveMasterRequest $request, string $resource, int $record, MasterDataService $service): MasterDataResource
    {
        return new MasterDataResource($service->save($request->user(), $resource, $record, $request->validated(), $request));
    }

    public function status(MasterStatusRequest $request, string $resource, int $record, MasterDataService $service): MasterDataResource
    {
        MasterDataAccess::authorize($request->user(), $resource, true, 'status');
        $data = $request->validated();

        return new MasterDataResource($service->status($request->user(), $resource, $record, $data, $request));
    }

    public function people(Request $request): JsonResponse
    {
        abort_unless(MasterDataAccess::manages($request->user()), 403);
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100'], 'birth_date' => ['nullable', 'date_format:Y-m-d']]);
        $query = Person::query()->where('full_name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        if (isset($data['birth_date'])) {
            $query->where('birth_date', $data['birth_date']);
        }

        return response()->json(['data' => $query->orderBy('full_name')->limit(30)->get(['id', 'full_name', 'birth_date', 'is_active'])]);
    }

    private function query(Request $request, string $resource): Builder
    {
        $query = MasterDataCatalog::model($resource)->newQuery();
        if (MasterDataCatalog::hasPerson($resource)) {
            $query->with('person');
        }

        return $resource === 'students' ? MasterDataAccess::scopeStudents($query, $request->user()) : $query;
    }
}
