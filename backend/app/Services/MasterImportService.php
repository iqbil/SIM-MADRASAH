<?php

namespace App\Services;

use App\Jobs\ProcessMasterImport;
use App\Models\File;
use App\Models\ImportBatche;
use App\Models\ImportRow;
use App\Models\SourceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class MasterImportService
{
    public const RESOURCES = ['students' => 'student', 'guardians' => 'guardian', 'employees' => 'employee', 'subjects' => 'subject'];

    public function __construct(private MasterDataService $master, private AccountManagementService $audit) {}

    public static function requirePayload(ImportBatche $batch): void
    {
        $file = $batch->sourceFile;
        abort_unless(! $file->deleted_at && $file->expires_at?->isFuture(), 410, 'Payload impor telah kedaluwarsa. Unggah berkas baru untuk melanjutkan.');
    }

    /** @return array<int, string> */
    public static function columns(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return ['external_id', ...match ($resource) {
            'students' => ['person_id', ...MasterDataCatalog::PERSON_FIELDS, 'internal_code', 'nis', 'nisn', 'admitted_on'],
            'guardians' => ['person_id', ...MasterDataCatalog::PERSON_FIELDS, 'internal_code', 'occupation'],
            'employees' => ['person_id', ...MasterDataCatalog::PERSON_FIELDS, 'employee_number', 'nip', 'nuptk', 'joined_on', 'left_on'],
            'subjects' => ['code', 'name', 'group_name'],
        }];
    }

    /** @param array<string, mixed> $data */
    public function upload(User $actor, UploadedFile $upload, array $data, Request $request): ImportBatche
    {
        MasterDataAccess::authorizeImport($actor, $data['resource']);
        $hash = hash_file('sha256', $upload->getRealPath());
        $path = null;
        try {
            return DB::transaction(function () use ($actor, $upload, $data, $request, $hash, &$path): ImportBatche {
                User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
                $existing = ImportBatche::query()->where('requested_by', $actor->id)->where('operation_key', $data['operation_key'])->first();
                if ($existing) {
                    abort_unless($existing->file_hash === $hash && $existing->module === 'master.'.$data['resource'] && $existing->source_system === $data['source_system'], 409, 'operation_key sudah digunakan untuk berkas atau sumber lain.');

                    return $existing;
                }
                $rows = $this->parse($upload, self::columns($data['resource']));
                $path = 'master-imports/'.Str::uuid().'.csv';
                abort_unless(Storage::disk('local')->put($path, file_get_contents($upload->getRealPath())), 503, 'Penyimpanan berkas tidak tersedia.');
                $file = File::query()->forceCreate(['storage_disk' => 'local', 'storage_path' => $path, 'original_name' => 'master-import.csv',
                    'mime_type' => 'text/csv', 'extension' => 'csv', 'size_bytes' => $upload->getSize(), 'sha256' => $hash,
                    'classification' => 'internal', 'scan_status' => 'pending', 'uploaded_by' => $actor->id, 'expires_at' => now('UTC')->addDays(30)]);
                $batch = ImportBatche::query()->forceCreate(['requested_by' => $actor->id, 'module' => 'master.'.$data['resource'],
                    'source_system' => $data['source_system'], 'source_file_id' => $file->id, 'file_hash' => $hash, 'template_version' => 'master-csv-v1',
                    'status' => 'uploaded', 'operation_key' => $data['operation_key'], 'atomicity_mode' => 'file', 'total_rows' => count($rows),
                    'valid_rows' => 0, 'failed_rows' => 0, 'inserted_rows' => 0, 'updated_rows' => 0]);
                foreach ($rows as $index => $values) {
                    ImportRow::query()->create(['import_batch_id' => $batch->id, 'row_number' => $index + 2, 'external_id' => $values['external_id'],
                        'normalized_values' => $values, 'status' => 'pending']);
                }
                $this->audit->audit($actor, 'master.import.uploaded', 'import_batch', $batch->id, $data['reason'], null, ['total_rows' => count($rows)], $request);
                ProcessMasterImport::dispatch($batch->id, 'validate')->afterCommit();

                return $batch;
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    /** @param array<int, string> $columns
     * @return array<int, array<string, mixed>>
     */
    private function parse(UploadedFile $file, array $columns): array
    {
        $contents = file_get_contents($file->getRealPath());
        if (! mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            throw ValidationException::withMessages(['file' => 'Gunakan CSV UTF-8 tanpa karakter biner.']);
        }
        $handle = fopen($file->getRealPath(), 'rb');
        try {
            $header = fgetcsv($handle, null, ',', '"', '');
            if ($header) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            }
            if ($header !== $columns) {
                throw ValidationException::withMessages(['file' => 'Kolom CSV harus mengikuti urutan template master-csv-v1.']);
            }
            $rows = [];
            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                if (count($row) !== count($columns) || count($rows) >= 5000) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris dan setiap baris harus memiliki jumlah kolom sesuai template.']);
                }
                $values = array_map(fn (?string $value): ?string => filled(trim($value ?? '')) ? trim($value) : null, $row);
                $record = array_combine($columns, $values);
                if (mb_strlen($record['external_id'] ?? '') > 150) {
                    throw ValidationException::withMessages(['file' => 'external_id maksimal 150 karakter.']);
                }
                $rows[] = $record;
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'Berkas harus berisi sedikitnya satu baris data.']);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    public function validateBatch(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $batch = ImportBatche::query()->lockForUpdate()->findOrFail($id);
            if ($batch->status !== 'uploaded') {
                return;
            }
            self::requirePayload($batch);
            $actor = $batch->requester;
            $resource = substr($batch->module, 7);
            MasterDataAccess::authorizeImport($actor, $resource);
            $seen = [];
            $failed = 0;
            foreach ($batch->rows()->orderBy('row_number')->get() as $row) {
                $raw = $row->normalized_values;
                $source = $this->source($batch, $resource, $raw['external_id'] ?? '');
                $target = $source ? MasterDataCatalog::model($resource)->newQuery()->find($source->getAttribute(self::RESOURCES[$resource].'_id')) : null;
                $values = Arr::except($raw, ['external_id', ...MasterDataCatalog::PERSON_FIELDS]);
                if (MasterDataCatalog::hasPerson($resource)) {
                    if (empty($values['person_id'])) {
                        unset($values['person_id']);
                        $values['person'] = Arr::only($raw, MasterDataCatalog::PERSON_FIELDS);
                    } else {
                        $values['person_id'] = (int) $values['person_id'];
                    }
                }
                if ($target) {
                    foreach ($values as $field => $value) {
                        if ($field === 'person') {
                            $values['person'] = array_replace($target->person->only(MasterDataCatalog::PERSON_FIELDS), array_filter($value, fn ($item): bool => $item !== null));
                            $values['person']['birth_date'] = isset($value['birth_date']) ? $value['birth_date'] : $target->person->birth_date?->toDateString();
                        } elseif ($value === null) {
                            $existing = $target->getAttribute($field);
                            $values[$field] = $existing instanceof \Carbon\CarbonInterface ? $existing->toDateString() : $existing;
                        }
                    }
                }
                $validator = Validator::make(['external_id' => $raw['external_id'], ...$values], ['external_id' => ['required', 'string', 'max:150'], ...MasterDataCatalog::rules($resource, $target)]);
                $errors = $validator->errors()->toArray();
                if ($target && isset($values['person_id']) && $target->person_id !== $values['person_id']) {
                    $errors['person_id'][] = 'Pemetaan sumber tidak boleh mengganti identitas orang. Periksa external_id dan person_id.';
                }
                $duplicates = [];
                foreach (['external_id', 'internal_code', 'employee_number', 'nis', 'nisn', 'nip', 'nuptk', 'code', 'person_id'] as $field) {
                    if (filled($raw[$field] ?? null)) {
                        $key = $field.':'.mb_strtolower($raw[$field]);
                        if (isset($seen[$key])) {
                            $errors[$field][] = 'Nilai duplikat dalam berkas yang sama (baris '.$seen[$key].').';
                        }
                        $seen[$key] = $row->row_number;
                    }
                }
                if (isset($values['person']) && ! isset($errors['person.full_name'])) {
                    $duplicates = $this->master->duplicatePeople($values['person'], $target?->person_id);
                    $key = 'person:'.mb_strtolower($values['person']['full_name'] ?? '').':'.($values['person']['birth_date'] ?? '');
                    if ($duplicates || isset($seen[$key])) {
                        $errors['person'][] = 'Kandidat identitas ganda ditemukan. Gunakan person_id atau perbaiki berkas.';
                    }
                    $seen[$key] = $row->row_number;
                }
                if ($target && $resource === 'subjects' && $values['code'] !== $target->code) {
                    $errors['code'][] = 'Kode referensi bersifat tetap. Buat referensi baru bila maknanya berubah.';
                }
                $hash = hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
                $action = ! $target ? 'insert' : ($this->matchesTarget($target, $values) ? 'unchanged' : 'update');
                if ($target) {
                    $values['expected_updated_at'] = $target->updated_at->toISOString();
                    if (isset($values['person'])) {
                        $values['expected_person_updated_at'] = $target->person->updated_at->toISOString();
                    }
                }
                $row->update(['source_record_id' => $source?->id, 'status' => $errors ? 'invalid' : 'valid', 'validation_errors' => $errors,
                    'duplicate_candidates' => $duplicates, 'result_action' => $action, 'normalized_values' => ['values' => $values, 'content_hash' => $hash]]);
                $failed += $errors ? 1 : 0;
            }
            $batch->update(['status' => 'preview_ready', 'valid_rows' => $batch->total_rows - $failed, 'failed_rows' => $failed]);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function confirm(User $actor, int $id, array $data, Request $request): ImportBatche
    {
        return DB::transaction(function () use ($actor, $id, $data, $request): ImportBatche {
            $batch = ImportBatche::query()->where('module', 'like', 'master.%')->lockForUpdate()->findOrFail($id);
            MasterDataAccess::authorizeImport($actor, substr($batch->module, 7));
            if (in_array($batch->status, ['importing', 'completed'], true)) {
                return $batch;
            }
            self::requirePayload($batch);
            MasterDataService::checkVersion($batch, $data['expected_updated_at']);
            abort_unless($batch->status === 'preview_ready' && $batch->failed_rows === 0 && $batch->valid_rows > 0, 422, 'Konfirmasi memerlukan pratinjau yang seluruh barisnya valid.');
            $batch->forceFill(['status' => 'importing', 'confirmed_by' => $actor->id, 'confirmed_at' => now('UTC')])->save();
            $this->audit->audit($actor, 'master.import.confirmed', 'import_batch', $batch->id, $data['reason'], null, ['total_rows' => $batch->total_rows], $request);
            ProcessMasterImport::dispatch($batch->id, 'confirm', $data['reason'])->afterCommit();

            return $batch;
        }, 3);
    }

    public function importBatch(int $id, string $reason): void
    {
        DB::transaction(function () use ($id, $reason): void {
            $batch = ImportBatche::query()->lockForUpdate()->findOrFail($id);
            if ($batch->status !== 'importing') {
                return;
            }
            self::requirePayload($batch);
            $actor = $batch->confirmer;
            $resource = substr($batch->module, 7);
            MasterDataAccess::authorizeImport($actor, $resource);
            $inserted = $updated = 0;
            foreach ($batch->rows()->orderBy('row_number')->get() as $row) {
                $source = $this->source($batch, $resource, $row->external_id);
                $values = $row->normalized_values['values'];
                $hash = $row->normalized_values['content_hash'];
                $targetId = $source?->getAttribute(self::RESOURCES[$resource].'_id');
                abort_unless($source?->id === $row->source_record_id, 409, 'Pemetaan sumber berubah sejak pratinjau. Unggah ulang berkas.');
                if ($targetId) {
                    $current = MasterDataCatalog::model($resource)->newQuery()->lockForUpdate()->findOrFail($targetId);
                    MasterDataService::checkVersion($current, $values['expected_updated_at']);
                    if (isset($values['person'])) {
                        MasterDataService::checkVersion($current->person()->lockForUpdate()->firstOrFail(), $values['expected_person_updated_at']);
                    }
                }
                if ($row->result_action !== 'unchanged') {
                    $target = $this->master->save($actor, $resource, $targetId, [...$values, 'reason' => $reason], Request::create('/queue/master-import', 'POST'));
                    if (! $source) {
                        $source = SourceRecord::query()->create(['source_system' => $batch->source_system, 'entity_type' => self::RESOURCES[$resource],
                            'external_id' => $row->external_id, self::RESOURCES[$resource].'_id' => $target->id, 'content_hash' => $hash, 'last_batch_id' => $batch->id]);
                        $inserted++;
                    } else {
                        $source->update(['content_hash' => $hash, 'last_batch_id' => $batch->id]);
                        $updated++;
                    }
                }
                $row->update(['source_record_id' => $source?->id, 'status' => $row->result_action === 'unchanged' ? 'skipped' : 'imported']);
            }
            $batch->update(['status' => 'completed', 'started_at' => $batch->confirmed_at, 'finished_at' => now('UTC'), 'inserted_rows' => $inserted, 'updated_rows' => $updated]);
        }, 3);
    }

    /** @param array<string, mixed> $values */
    private function matchesTarget(\Illuminate\Database\Eloquent\Model $target, array $values): bool
    {
        foreach ($values as $field => $value) {
            if ($field === 'person') {
                if (! $this->matchesTarget($target->person, $value)) {
                    return false;
                }
            } else {
                $existing = $target->getAttribute($field);
                if ($existing instanceof \Carbon\CarbonInterface) {
                    $existing = $existing->toDateString();
                }
                if ($existing !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    private function source(ImportBatche $batch, string $resource, string $externalId): ?SourceRecord
    {
        return SourceRecord::query()->where('source_system', $batch->source_system)->where('entity_type', self::RESOURCES[$resource])->where('external_id', $externalId)->first();
    }
}
