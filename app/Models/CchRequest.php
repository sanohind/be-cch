<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CchRequest extends Model
{
    protected $table = 't_cch_request';
    protected $primaryKey = 'request_id';

    protected $fillable = ['cch_id', 'department', 'due_date', 'description', 'status'];
    protected $casts = ['due_date' => 'date'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }

    /** Check if this request is overdue */
    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->due_date < now()->toDateString();
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')->where('due_date', '<', now()->toDateString());
    }
}
