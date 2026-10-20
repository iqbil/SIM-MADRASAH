<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SaveMasterRelationRequest;
use App\Http\Resources\MasterDataResource;
use App\Services\MasterDataAccess;
use App\Services\MasterRelationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterRelationController extends Controller
{
    public function index(Request $request, int $owner, string $relation): AnonymousResourceCollection
    {
        MasterDataAccess::authorize($request->user(), 'relations', true);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $model = MasterRelationService::owner($relation, $owner);
        $page = MasterRelationService::query($relation, $owner)->orderByDesc('effective_from')->orderByDesc('id')->paginate(25);

        return MasterDataResource::collection($page)->additional(['meta' => ['owner_updated_at' => $model->updated_at->toISOString()]]);
    }

    public function store(SaveMasterRelationRequest $request, int $owner, string $relation, MasterRelationService $service): JsonResponse
    {
        $link = $service->save($request->user(), $relation, $owner, null, null, $request->validated(), $request);

        return (new MasterDataResource($link))->additional(['meta' => ['owner_updated_at' => MasterRelationService::owner($relation, $owner)->updated_at->toISOString()]])->response()->setStatusCode(201);
    }

    public function action(SaveMasterRelationRequest $request, int $owner, string $relation, int $link, string $action, MasterRelationService $service): JsonResponse
    {
        abort_unless($relation === 'guardian-links' || $action === 'end', 404);
        $record = $service->save($request->user(), $relation, $owner, $link, $action, $request->validated(), $request);

        return (new MasterDataResource($record))->additional(['meta' => ['owner_updated_at' => MasterRelationService::owner($relation, $owner)->updated_at->toISOString()]])->response();
    }
}
