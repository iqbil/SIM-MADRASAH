<?php

namespace Tests\Feature;

use App\Jobs\ProcessMasterImport;
use App\Models\ImportBatche;
use App\Models\Person;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserRole;
use App\Services\MasterImportService;
use Database\Seeders\AccountAccessSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\MysqlAuthTestCase;

class MysqlMasterImportTest extends MysqlAuthTestCase
{
    private User $operator;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AccountAccessSeeder::class, ReferenceDataSeeder::class]);
        Storage::fake('local');
        Bus::fake([ProcessMasterImport::class]);
        $this->operator = User::factory()->create();
        UserRole::query()->forceCreate(['user_id' => $this->operator->id, 'role_id' => Role::query()->where('code', 'operator')->firstOrFail()->id,
            'effective_from' => now('UTC')->subMinute(), 'granted_by' => $this->operator->id]);
        $this->headers = ['Authorization' => 'Bearer '.$this->loginUser($this->operator)['access_token'], 'Accept' => 'application/json'];
    }

    private function csv(string $resource, array $rows): UploadedFile
    {
        $columns = MasterImportService::columns($resource);
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, $columns, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $column) => $row[$column] ?? '', $columns), ',', '"', '');
        }
        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        return UploadedFile::fake()->createWithContent('master.csv', $contents);
    }

    private function upload(string $key, string $resource, array $rows): ImportBatche
    {
        $response = $this->post('/api/v1/master/imports', ['resource' => $resource, 'source_system' => 'emis', 'operation_key' => $key,
            'reason' => 'Migrasi data master terverifikasi', 'file' => $this->csv($resource, $rows)], $this->headers)->assertAccepted();

        return ImportBatche::query()->findOrFail($response->json('data.id'));
    }

    private function preview(ImportBatche $batch): ImportBatche
    {
        (new ProcessMasterImport($batch->id, 'validate'))->handle(app(MasterImportService::class));

        return $batch->fresh();
    }

    private function confirm(ImportBatche $batch): ImportBatche
    {
        $this->postJson('/api/v1/master/imports/'.$batch->id.'/confirm', ['reason' => 'Pratinjau telah diperiksa operator',
            'expected_updated_at' => $batch->updated_at->toISOString()], $this->headers)->assertAccepted();
        (new ProcessMasterImport($batch->id, 'confirm', 'Pratinjau telah diperiksa operator'))->handle(app(MasterImportService::class));

        return $batch->fresh();
    }

    public function test_import_requires_preview_confirmation_and_retries_do_not_duplicate_data(): void
    {
        $rows = [['external_id' => 'mapel-1', 'code' => 'MTK', 'name' => 'Matematika']];
        $this->get('/api/v1/master/imports/templates/subjects', $this->headers)->assertOk()->assertStreamedContent("external_id,code,name,group_name\n");
        $batch = $this->upload('attempt-1', 'subjects', $rows);
        $this->assertSame('uploaded', $batch->status);
        $this->assertDatabaseCount('subjects', 0);
        Bus::assertDispatched(ProcessMasterImport::class, fn ($job) => $job->stage === 'validate' && $job->afterCommit);
        $this->getJson('/api/v1/master/imports/'.$batch->id, $this->headers)->assertOk()->assertJsonMissingPath('data.source_file_id');
        $this->postJson('/api/v1/master/imports/'.$batch->id.'/confirm', ['reason' => 'Belum dapat dikonfirmasi', 'expected_updated_at' => $batch->updated_at->toISOString()], $this->headers)->assertUnprocessable();
        $batch = $this->preview($batch);
        $this->assertSame('preview_ready', $batch->status);
        $this->getJson('/api/v1/master/imports/'.$batch->id.'/rows', $this->headers)->assertOk()->assertJsonPath('data.0.result_action', 'insert');
        $same = $this->upload('attempt-1', 'subjects', $rows);
        $this->assertSame($batch->id, $same->id);
        $batch = $this->confirm($batch);
        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->inserted_rows);
        $this->confirm($batch);
        $repeat = $this->confirm($this->preview($this->upload('attempt-2', 'subjects', $rows)));
        $this->assertSame(0, $repeat->inserted_rows);
        $this->assertSame(0, $repeat->updated_rows);
        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseCount('source_records', 1);
        $this->assertDatabaseCount('files', 2);
    }

    public function test_invalid_and_duplicate_rows_block_the_entire_file_and_produce_error_report(): void
    {
        $batch = $this->preview($this->upload('bad-file', 'subjects', [
            ['external_id' => 'mapel-1', 'code' => 'MTK', 'name' => 'Matematika'],
            ['external_id' => 'mapel-1', 'code' => 'MTK', 'name' => 'Duplikat'],
            ['external_id' => '=HYPERLINK(1)', 'code' => 'BIO', 'name' => ''],
        ]));
        $this->assertSame(2, $batch->failed_rows);
        $this->postJson('/api/v1/master/imports/'.$batch->id.'/confirm', ['reason' => 'Periksa seluruh berkas dahulu', 'expected_updated_at' => $batch->updated_at->toISOString()], $this->headers)->assertUnprocessable();
        $this->getJson('/api/v1/master/imports/'.$batch->id.'/rows?status=invalid', $this->headers)->assertOk()->assertJsonCount(2, 'data');
        $response = $this->get('/api/v1/master/imports/'.$batch->id.'/errors', $this->headers)->assertOk();
        $this->assertStringContainsString("'=HYPERLINK(1)", $response->streamedContent());
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_person_import_validates_duplicates_and_updates_using_source_identity(): void
    {
        $rows = [['external_id' => 'siswa-1', 'internal_code' => 'S-01', 'full_name' => 'Siswa Impor', 'birth_date' => '2012-01-01', 'nisn' => '1234567890']];
        $first = $this->confirm($this->preview($this->upload('student-1', 'students', $rows)));
        $this->assertSame('completed', $first->status);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_transitions', 1);
        $rows[0]['full_name'] = 'Siswa Setelah Koreksi';
        $second = $this->confirm($this->preview($this->upload('student-2', 'students', $rows)));
        $this->assertSame(1, $second->updated_rows);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('student_transitions', 1);
        $this->assertDatabaseHas('persons', ['full_name' => 'Siswa Setelah Koreksi']);
    }

    public function test_changes_after_preview_roll_back_all_rows_atomically(): void
    {
        $rows = [['external_id' => 'existing', 'code' => 'MTK', 'name' => 'Matematika']];
        $this->confirm($this->preview($this->upload('initial', 'subjects', $rows)));
        $rows = [['external_id' => 'new', 'code' => 'BIO', 'name' => 'Biologi'], ['external_id' => 'existing', 'code' => 'MTK', 'name' => 'Nama dari impor']];
        $batch = $this->preview($this->upload('stale', 'subjects', $rows));
        Subject::query()->where('code', 'MTK')->firstOrFail()->update(['name' => 'Koreksi manual']);
        $this->postJson('/api/v1/master/imports/'.$batch->id.'/confirm', ['reason' => 'Pratinjau diperiksa operator', 'expected_updated_at' => $batch->updated_at->toISOString()], $this->headers)->assertAccepted();
        $job = new ProcessMasterImport($batch->id, 'confirm', 'Pratinjau diperiksa operator');
        try {
            $job->handle(app(MasterImportService::class));
            $this->fail('Stale imports must conflict.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $job->failed($exception);
        }
        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseCount('source_records', 1);
        $this->assertDatabaseHas('subjects', ['code' => 'MTK', 'name' => 'Koreksi manual']);
        $this->assertSame('failed', $batch->fresh()->status);
    }

    public function test_key_reuse_with_different_payload_is_rejected_and_queued_authorization_is_rechecked(): void
    {
        $rows = [['external_id' => 'mapel-1', 'code' => 'MTK', 'name' => 'Matematika']];
        $batch = $this->preview($this->upload('one-key', 'subjects', $rows));
        $rows[0]['name'] = 'Berbeda';
        $this->post('/api/v1/master/imports', ['resource' => 'subjects', 'source_system' => 'emis', 'operation_key' => 'one-key',
            'reason' => 'Unggah ulang dengan data berbeda', 'file' => $this->csv('subjects', $rows)], $this->headers)->assertConflict();
        $this->postJson('/api/v1/master/imports/'.$batch->id.'/confirm', ['reason' => 'Konfirmasi sebelum pencabutan', 'expected_updated_at' => $batch->updated_at->toISOString()], $this->headers)->assertAccepted();
        $this->operator->roleAssignments()->update(['revoked_by' => $this->operator->id, 'revoke_reason' => 'Izin dicabut']);
        try {
            (new ProcessMasterImport($batch->id, 'confirm', 'Konfirmasi sebelum pencabutan'))->handle(app(MasterImportService::class));
            $this->fail('Revoked operators must not import.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_templates_reject_invalid_encoding_headers_and_excess_rows(): void
    {
        foreach (["wrong,columns\n1,2\n", "external_id,code,name,group_name\nx,y,\xFF,\n"] as $content) {
            $this->post('/api/v1/master/imports', ['resource' => 'subjects', 'source_system' => 'emis', 'operation_key' => 'bad-csv',
                'reason' => 'Pemeriksaan berkas invalid', 'file' => UploadedFile::fake()->createWithContent('bad.csv', $content)], $this->headers)->assertUnprocessable();
        }
        $rows = array_fill(0, 5001, ['external_id' => 'x', 'code' => 'X', 'name' => 'Contoh']);
        $this->post('/api/v1/master/imports', ['resource' => 'subjects', 'source_system' => 'emis', 'operation_key' => 'too-many',
            'reason' => 'Pemeriksaan jumlah baris', 'file' => $this->csv('subjects', $rows)], $this->headers)->assertUnprocessable();
        $this->assertDatabaseCount('import_batches', 0);
    }

    public function test_expired_import_payloads_are_unavailable_and_purged_idempotently(): void
    {
        $batch = $this->preview($this->upload('expired', 'subjects', [['external_id' => 'mapel-1', 'code' => 'MTK', 'name' => 'Matematika']]));
        $file = $batch->sourceFile;
        Storage::disk('local')->assertExists($file->storage_path);
        $file->forceFill(['created_at' => now('UTC')->subDays(31), 'expires_at' => now('UTC')->subSecond()])->save();
        $this->getJson('/api/v1/master/imports/'.$batch->id.'/rows', $this->headers)->assertGone();
        $this->postJson('/api/v1/master/imports/'.$batch->id.'/confirm', ['reason' => 'Berkas sudah tidak berlaku', 'expected_updated_at' => $batch->updated_at->toISOString()], $this->headers)->assertGone();
        $this->artisan('master:purge-import-payloads')->assertSuccessful();
        Storage::disk('local')->assertMissing($file->storage_path);
        $this->assertSame('cancelled', $batch->fresh()->status);
        $this->assertNull($batch->rows()->firstOrFail()->normalized_values);
        $this->assertNotNull($file->fresh()->deleted_at);
        $this->artisan('master:purge-import-payloads')->assertSuccessful();
        $this->assertDatabaseCount('import_batches', 1);
    }

    public function test_import_can_reuse_existing_person_and_repeat_without_rebinding_identity(): void
    {
        $person = Person::factory()->create();
        $other = Person::factory()->create();
        $rows = [['external_id' => 'wali-shared', 'person_id' => $person->id, 'internal_code' => 'W-SHARED']];
        $first = $this->confirm($this->preview($this->upload('reuse-1', 'guardians', $rows)));
        $this->assertSame(1, $first->inserted_rows);
        $second = $this->confirm($this->preview($this->upload('reuse-2', 'guardians', $rows)));
        $this->assertSame(0, $second->updated_rows);
        $this->assertDatabaseCount('guardians', 1);
        $this->assertDatabaseHas('guardians', ['person_id' => $person->id]);
        $rows[0]['person_id'] = $other->id;
        $invalid = $this->preview($this->upload('reuse-3', 'guardians', $rows));
        $this->assertSame(1, $invalid->failed_rows);
        $this->assertArrayHasKey('person_id', $invalid->rows()->firstOrFail()->validation_errors);
    }
}
