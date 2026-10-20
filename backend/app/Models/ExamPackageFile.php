<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('exam_package_files')]
#[Fillable([
    'exam_package_id',
    'file_id',
    'purpose',
    'position',
])]
class ExamPackageFile extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'exam_package_id' => 'integer',
            'file_id' => 'integer',
            'position' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<ExamPackage, $this> */
    public function examPackage(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
