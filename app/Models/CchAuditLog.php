<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CchAuditLog extends Model
{
    protected $table = 't_cch_audit_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'action', 'block_name', 'changed_by', 'old_value', 'new_value', 'changed_at'];
    protected $casts = ['changed_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'changed_by', 'id'); }
}
