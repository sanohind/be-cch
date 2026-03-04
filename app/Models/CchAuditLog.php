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

class CchNotification extends Model
{
    protected $table = 't_cch_notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'notification_type', 'sent_to', 'message', 'is_sent', 'sent_at', 'created_at'];
    protected $casts = ['is_sent' => 'boolean', 'sent_at' => 'datetime', 'created_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(CchUser::class, 'sent_to', 'id'); }

    /** True if this is a broadcast notification (sent to all QA users) */
    public function isBroadcast(): bool { return is_null($this->sent_to); }
}
