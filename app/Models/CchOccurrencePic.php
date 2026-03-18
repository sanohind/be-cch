<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CchOccurrencePic extends Model
{
    protected $table = 't_cch_occurrence_pic';
    protected $primaryKey = 'occurrence_pic_id';

    protected $fillable = [
        'cch_id',
        'pic_user_id',
        'defect_made_by',
        'division_id',
        'responsible_office',
        'process_id',
        'process_comment',
        'supplier_id',
        'supplier_process_id',
        'supplier_process_comment',
    ];

    public function picUser(): BelongsTo
    {
        return $this->belongsTo(CchUser::class, 'pic_user_id', 'id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'process_id', 'process_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'supplier_id', 'bp_code');
    }

    public function supplierProcess(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'supplier_process_id', 'process_id');
    }

    // Causes are stored in t_cch_causes and queried in controller (scoped by pic_user_id).
}

