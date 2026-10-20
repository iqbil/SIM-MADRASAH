<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('exam_package_keys')]
#[Fillable([
    'package_item_id',
    'correct_package_option_ids',
    'correct_labels_snapshot',
    'answer_snapshot',
    'explanation_snapshot',
    'rubric_snapshot',
])]
#[Hidden(['correct_package_option_ids', 'correct_labels_snapshot', 'answer_snapshot', 'explanation_snapshot', 'rubric_snapshot'])]
class ExamPackageKey extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'package_item_id' => 'integer',
            'correct_package_option_ids' => 'array',
            'correct_labels_snapshot' => 'array',
            'rubric_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<ExamPackageItem, $this> */
    public function packageItem(): BelongsTo
    {
        return $this->belongsTo(ExamPackageItem::class, 'package_item_id', 'id');
    }
}
