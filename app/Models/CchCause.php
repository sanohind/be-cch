<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel t_cch_causes — gabungan Occurrence Causes dan Outflow Causes.
 *
 * Kolom `cause_type`:
 *   - 'occurrence' → Root cause dari Block 8 (Occurrence Analysis)
 *   - 'outflow'    → Root cause dari Block 9 (Outflow Analysis)
 *
 * Filter saat ditampilkan:
 *   CchCause::where('cch_id', $id)->occurrence()->get()
 *   CchCause::where('cch_id', $id)->outflow()->get()
 */
class CchCause extends Model
{
    protected $table = 't_cch_causes';
    protected $primaryKey = 'cause_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'cch_id',
        'pic_user_id',
        'cause_type',
        'primary_factor',
        'master_cause_id',   // FK ke m_causes.id (nullable)
        'cause_description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function cch(): BelongsTo
    {
        return $this->belongsTo(Cch::class, 'cch_id', 'cch_id');
    }

    /**
     * Master cause dari m_occurrence_causes.
     * Nullable — user boleh mengisi cause_description tanpa memilih master.
     */
    public function cause(): BelongsTo
    {
        return $this->belongsTo(Cause::class, 'master_cause_id', 'id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOccurrence($query)
    {
        return $query->where('cause_type', 'occurrence');
    }

    public function scopeOutflow($query)
    {
        return $query->where('cause_type', 'outflow');
    }
}
