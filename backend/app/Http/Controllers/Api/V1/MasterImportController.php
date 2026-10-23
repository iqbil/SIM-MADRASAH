<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\MasterImportRequest;
use App\Http\Requests\Master\MasterStatusRequest;
use App\Http\Resources\MasterImportResource;
use App\Models\ImportBatche;
use App\Models\ImportRow;
use App\Services\MasterDataAccess;
use App\Services\MasterImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterImportController extends Controller
{
    public function template(Request $request, string $resource): StreamedResponse
    {
        MasterDataAccess::authorizeImport($request->user(), $resource);
        $columns = MasterImportService::columns($resource);

        return response()->streamDownload(function () use ($columns): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $columns, ',', '"', '');
            fclose($handle);
        }, 'template-'.$resource.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $resources = MasterDataAccess::options($request->user())['import_resources'];
        abort_unless($resources !== [], 403);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        return MasterImportResource::collection(ImportBatche::query()->whereIn('module', array_map(fn (string $resource): string => 'master.'.$resource, $resources))->latest('id')->paginate(25));
    }

    public function store(MasterImportRequest $request, MasterImportService $service): JsonResponse
    {
        return (new MasterImportResource($service->upload($request->user(), $request->file('file'), $request->validated(), $request)))->response()->setStatusCode(202);
    }

    public function show(Request $request, int $batch): MasterImportResource
    {

        return new MasterImportResource($this->batch($request, $batch));
    }

    public function rows(Request $request, int $batch): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'status' => ['nullable', 'in:pending,valid,invalid,imported,skipped,failed']]);
        $model = $this->batch($request, $batch);
        MasterImportService::requirePayload($model);
        $query = $model->rows()->orderBy('row_number');
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $page = $query->paginate(25);

        return response()->json(['data' => $page->getCollection()->map(fn (ImportRow $row): array => ['id' => $row->id, 'row_number' => $row->row_number,
            'external_id' => $row->external_id, 'status' => $row->status, 'result_action' => $row->result_action,
            'values' => $row->normalized_values['values'] ?? $row->normalized_values, 'errors' => $row->validation_errors, 'duplicate_candidates' => $row->duplicate_candidates]),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]]);
    }

    public function errors(Request $request, int $batch): StreamedResponse
    {
        $model = $this->batch($request, $batch);
        MasterImportService::requirePayload($model);

        return response()->streamDownload(function () use ($model): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['row_number', 'external_id', 'errors'], ',', '"', '');
            foreach ($model->rows()->whereIn('status', ['invalid', 'failed'])->orderBy('row_number')->cursor() as $row) {
                $values = [(string) $row->row_number, (string) $row->external_id, json_encode($row->validation_errors, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
                $values = array_map(fn (string $value): string => preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $values);
                fputcsv($handle, $values, ',', '"', '');
            }
            fclose($handle);
        }, 'import-'.$batch.'-errors.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function confirm(MasterStatusRequest $request, int $batch, MasterImportService $service): JsonResponse
    {
        $data = $request->validated();

        return (new MasterImportResource($service->confirm($request->user(), $batch, $data, $request)))->response()->setStatusCode(202);
    }

    private function batch(Request $request, int $id): ImportBatche
    {
        $batch = ImportBatche::query()->where('module', 'like', 'master.%')->findOrFail($id);
        MasterDataAccess::authorizeImport($request->user(), substr($batch->module, 7));

        return $batch;
    }
}
