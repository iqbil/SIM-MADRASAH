<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('exam_package_options')]
#[Fillable([
    'package_item_id',
    'source_option_id',
    'position',
    'label_snapshot',
    'body_snapshot',
])]
class ExamPackageOption extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'package_item_id' => 'integer',
            'source_option_id' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<ExamPackageItem, $this> */
    public function packageItem(): BelongsTo
    {
        return $this->belongsTo(ExamPackageItem::class, 'package_item_id', 'id');
    }

    /** @return BelongsTo<QuestionOption, $this> */
    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'source_option_id', 'id');
    }
}
