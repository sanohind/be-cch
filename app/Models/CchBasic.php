<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CchBasic extends Model
{
    protected $table = 't_cch_basic';
    protected $primaryKey = 'basic_id';

    protected $fillable = [
        'cch_id', 'subject', 'division_id', 'report_category', 'customer_id',
        'plant_of_customer',
        'defect_class', 'line_stop', 'count_by_customer',
        'month_of_counted', 'importance_internal', 'importance_internal_class',
        'importance_customer', 'toyota_rank',
    ];

    protected $casts = [
        'month_of_counted' => 'date',
    ];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function division(): BelongsTo { return $this->belongsTo(Division::class, 'division_id', 'id'); }
    public function customer(): BelongsTo { return $this->belongsTo(BusinessPartner::class, 'customer_id', 'bp_code'); }
    public function plant(): BelongsTo { return $this->belongsTo(Plant::class, 'plant_of_customer', 'plant_id'); }
}

class CchBasicAttachment extends Model
{
    protected $table = 't_cch_basic_attachments';
    protected $primaryKey = 'attachment_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'file_name', 'file_path', 'file_size_kb', 'uploaded_by', 'uploaded_at'];
    protected $casts = ['uploaded_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'uploaded_by', 'id'); }
}
