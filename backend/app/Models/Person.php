<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('persons')]
#[Fillable([
    'full_name',
    'sex',
    'birth_place',
    'birth_date',
    'address',
    'phone',
    'contact_email',
    'is_active',
])]
class Person extends DomainModel
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => 1,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'birth_date' => 'immutable_date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'person_id', 'id');
    }

    /** @return HasOne<Student, $this> */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'person_id', 'id');
    }

    /** @return HasOne<Guardian, $this> */
    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class, 'person_id', 'id');
    }

    /** @return HasOne<Employee, $this> */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'person_id', 'id');
    }

    /** @return HasMany<SourceRecord, $this> */
    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class, 'person_id', 'id');
    }
}
