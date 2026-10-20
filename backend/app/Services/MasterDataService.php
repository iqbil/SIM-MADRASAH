<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Person;
use App\Models\StudentStatus;
use App\Models\StudentTransition;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasterDataService
{
    public function __construct(private AccountManagementService $audit) {}

    public static function checkVersion(Model $model, ?string $expected): void
    {
        abort_unless($expected && $model->updated_at->equalTo(CarbonImmutable::parse($expected)), 409, 'Data telah berubah. Muat ulang sebelum menyimpan.');
    }

    /** @param array<string, mixed> $data */
    public function save(User $actor, string $resource, ?int $id, array $data, Request $request): Model
    {
        try {
            return DB::transaction(function () use ($actor, $resource, $id, $data, $request): Model {
                MasterDataAccess::authorize($actor, $resource, true);
                $model = $resource === 'institution' ? Institution::query()->lockForUpdate()->first() :
                    ($id ? MasterDataCatalog::model($resource)->newQuery()->lockForUpdate()->findOrFail($id) : null);
                $creating = ! $model;
                $before = $model?->only(['status_id', 'is_active', 'archived_at', 'updated_at']);
                if ($model) {
                    self::checkVersion($model, $data['expected_updated_at'] ?? null);
                }
                $values = Validator::make($data, MasterDataCatalog::rules($resource, $model))->validate();
                $model ??= MasterDataCatalog::model($resource);
                if (MasterDataCatalog::hasPerson($resource)) {
                    $person = $creating ? $this->resolvePerson($values, $data['duplicate_confirmation'] ?? null) : $model->person()->lockForUpdate()->firstOrFail();
                    if (! $creating && isset($values['person'])) {
                        self::checkVersion($person, $data['expected_person_updated_at'] ?? null);
                        $identity = [...$person->attributesToArray(), ...$values['person']];
                        if ($identity['full_name'] !== $person->full_name || ($identity['birth_date'] ?? null) !== $person->birth_date?->toDateString()) {
                            $this->reviewIdentity($identity, $person->id, $data['duplicate_confirmation'] ?? null);
                        }
                        $person->fill(Arr::only($values['person'], MasterDataCatalog::PERSON_FIELDS))->save();
                        $model->updated_at = now('UTC');
                    }
                    $values['person_id'] = $person->id;
                    unset($values['person']);
                }
                if (! $creating) {
                    foreach (['code', 'domain', 'is_terminal'] as $field) {
                        if (array_key_exists($field, $values) && ($field === 'is_terminal' ? (bool) $values[$field] !== (bool) $model->getAttribute($field) : (string) $values[$field] !== (string) $model->getAttribute($field))) {
                            throw ValidationException::withMessages([$field => 'Kode, domain, dan sifat terminal referensi bersifat tetap. Buat referensi baru bila maknanya berubah.']);
                        }
                    }
                }
                if ($creating && $resource === 'institution') {
                    $values['singleton_key'] = 1;
                }
                if ($creating && $resource === 'students') {
                    $values['status_id'] = StudentStatus::query()->where('code', 'active')->where('is_active', true)->where('is_terminal', false)->firstOrFail()->id;
                }
                $model->fill($values)->save();
                if ($creating && $resource === 'students') {
                    StudentTransition::query()->forceCreate(['student_id' => $model->id, 'to_status_id' => $model->status_id,
                        'type' => 'admission', 'effective_date' => $model->admitted_on ?? today('Asia/Jakarta'), 'reason' => $data['reason'],
                        'operation_key' => 'master-admission:'.Str::uuid(), 'confirmed_by' => $actor->id, 'confirmed_at' => now('UTC')]);
                }
                $this->audit->audit($actor, 'master.'.($creating ? 'created' : 'updated'), $resource, $model->id, $data['reason'], $before,
                    ['fields' => array_keys($values), 'person_fields' => array_keys($data['person'] ?? []), 'duplicate_confirmation' => $data['duplicate_confirmation'] ?? null, 'state' => $model->only(['status_id', 'is_active', 'archived_at', 'updated_at'])], $request);

                return $model->fresh(MasterDataCatalog::hasPerson($resource) ? ['person'] : []);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            abort(409, 'Identitas atau kode sudah digunakan. Periksa duplikasi dan muat ulang data.');
        }
    }

    /** @param array<string, mixed> $values */
    private function resolvePerson(array $values, ?string $confirmation = null): Person
    {
        if (isset($values['person_id'])) {
            if (isset($values['person'])) {
                throw ValidationException::withMessages(['person' => 'Pilih identitas yang ada atau buat identitas baru.']);
            }

            return Person::query()->where('is_active', true)->lockForUpdate()->findOrFail($values['person_id']);
        }
        $person = $values['person'];
        $this->reviewIdentity($person, null, $confirmation);

        return Person::query()->create(Arr::only($person, MasterDataCatalog::PERSON_FIELDS));
    }

    /** @param array<string, mixed> $person */
    private function reviewIdentity(array $person, ?int $except, ?string $confirmation): void
    {
        $candidates = $this->duplicatePeople($person, $except);
        if ($candidates !== [] && ! $confirmation) {
            throw new HttpResponseException(response()->json(['message' => 'Ditemukan kandidat identitas yang sama. Gunakan person_id bila orangnya sama, atau berikan duplicate_confirmation setelah memeriksa bahwa orangnya berbeda.',
                'errors' => ['person' => ['Periksa kandidat duplikasi sebelum melanjutkan.']], 'duplicate_candidates' => $candidates], 422));
        }
    }

    /** @param array<string, mixed> $person
     * @return array<int, array<string, mixed>>
     */
    public function duplicatePeople(array $person, ?int $except = null): array
    {
        return Person::query()->when($except, fn (Builder $query): Builder => $query->whereKeyNot($except))->where('full_name', trim($person['full_name'] ?? ''))->where('birth_date', $person['birth_date'] ?? null)
            ->limit(20)->get(['id', 'full_name', 'birth_date', 'is_active'])->toArray();
    }

    /** @param array<string, mixed> $data */
    public function status(User $actor, string $resource, int $id, array $data, Request $request): Model
    {
        return DB::transaction(function () use ($actor, $resource, $id, $data, $request): Model {
            MasterDataAccess::authorize($actor, $resource, true);
            abort_if($resource === 'institution', 405);
            $model = MasterDataCatalog::model($resource)->newQuery()->lockForUpdate()->findOrFail($id);
            self::checkVersion($model, $data['expected_updated_at']);
            $before = $model->only(['is_active', 'archived_at']);
            if ($resource === 'student-statuses' && $model->code === 'active' && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'Status active diperlukan untuk penerimaan siswa.']);
            }
            if ($resource === 'students') {
                $model->archived_at = $data['is_active'] ? null : now('UTC');
            } else {
                $model->is_active = $data['is_active'];
            }
            $model->save();
            $this->audit->audit($actor, 'master.status', $resource, $id, $data['reason'], $before, ['is_active' => (bool) $data['is_active']], $request);

            return $model->fresh(MasterDataCatalog::hasPerson($resource) ? ['person'] : []);
        }, 3);
    }
}
