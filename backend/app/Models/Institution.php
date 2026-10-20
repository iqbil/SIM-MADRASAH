<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('institutions')]
#[Fillable([
    'singleton_key',
    'name',
    'npsn',
    'nsm',
    'address',
    'phone',
    'email',
    'website',
    'timezone',
    'logo_file_id',
])]
class Institution extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'singleton_key' => 'integer',
            'logo_file_id' => 'integer',
        ];
    }

    /** @return BelongsTo<File, $this> */
    public function logoFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'logo_file_id', 'id');
    }
}
