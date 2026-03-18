<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CchPrimary extends Model
{
    protected $table = 't_cch_primary';
    protected $primaryKey = 'primary_id';

    protected $fillable = [
        'cch_id', 'failure_mode_id', 'defect_found_date', 'defect_found_date_end', 'defect_qty', 'comment',
        'part_number', 'part_name', 'product_category_id', 'product_family_id',
        'phase', 'product_supply_form', 'estimation_occurrence_outflow',
        'possibility_spreading', 'spreading_detail', 'qa_director_comment', 'author_comment',
    ];

    protected $casts = [
        'defect_found_date' => 'date',
        'defect_found_date_end' => 'date',
        'defect_qty' => 'integer',
    ];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function failureMode(): BelongsTo { return $this->belongsTo(FailureMode::class, 'failure_mode_id', 'failure_mode_id'); }
    public function productCategory(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'product_category_id', 'category_id'); }
    public function productFamily(): BelongsTo { return $this->belongsTo(ProductFamily::class, 'product_family_id', 'family_id'); }
}
