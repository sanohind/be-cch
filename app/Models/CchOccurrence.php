<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CchOccurrence extends Model
{
    protected $table = 't_cch_occurrence';
    protected $primaryKey = 'occurrence_id';

    protected $fillable = [
        'cch_id', 'defect_made_by',
        'responsible_plant_id', 'responsible_office', 'responsible_plant_detail',
        'process_id', 'process_comment',
        'supplier_id', 'supplier_process_id', 'supplier_process_comment',
        'author_comment',
    ];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function responsiblePlant(): BelongsTo { return $this->belongsTo(Plant::class, 'responsible_plant_id', 'plant_id'); }
    public function process(): BelongsTo { return $this->belongsTo(Process::class, 'process_id', 'process_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(BusinessPartner::class, 'supplier_id', 'bp_code'); }
    public function supplierProcess(): BelongsTo { return $this->belongsTo(Process::class, 'supplier_process_id', 'process_id'); }

    /** Causes Block 8 — diambil dari t_cch_causes dengan filter cause_type='occurrence' */
    public function causes(): HasMany
    {
        return $this->hasMany(CchCause::class, 'cch_id', 'cch_id')
                    ->where('cause_type', 'occurrence')
                    ->orderBy('sort_order');
    }
}
