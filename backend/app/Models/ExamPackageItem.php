<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('exam_package_items')]
#[Fillable([
    'exam_package_id',
    'question_version_id',
    'position',
    'points',
    'question_type_snapshot',
    'body_snapshot',
    'format_snapshot',
    'metadata_snapshot',
])]
#[Hidden(['key'])]
class ExamPackageItem extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'exam_package_id' => 'integer',
            'question_version_id' => 'integer',
            'position' => 'integer',
            'points' => 'decimal:2',
            'metadata_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<ExamPackage, $this> */
    public function examPackage(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id', 'id');
    }

    /** @return BelongsTo<QuestionVersion, $this> */
    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class, 'question_version_id', 'id');
    }

    /** @return HasMany<ExamPackageOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(ExamPackageOption::class, 'package_item_id', 'id');
    }

    /** @return HasOne<ExamPackageKey, $this> */
    public function key(): HasOne
    {
        return $this->hasOne(ExamPackageKey::class, 'package_item_id', 'id');
    }
}
