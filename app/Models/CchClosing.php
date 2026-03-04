<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CchClosing extends Model
{
    protected $table = 't_cch_closing';
    protected $primaryKey = 'closing_id';

    protected $fillable = [
        'cch_id',
        // Item 1 - Level of Importance by Customer
        'importance_customer_final',
        // Item 2 - Count by Customer
        'count_by_customer_final',
        // Item 3 - Countermeasure Against Occurrence
        'countermeasure_occurrence',
        // Item 4 - Countermeasure Against Outflow
        'countermeasure_outflow',
        // Item 6 - Total Claim Costs
        'currency_id',
        'cost_to_customer',
        'cost_to_external',
        'cost_internal',
        // cost_total is GENERATED COLUMN — do NOT include here
        // Item 7 - Recurrence or Non-recurrence
        'is_recurrence',
        // Item 8 - Request for Horizontal Deployment
        'horizontal_deployment',
        // Item 9 - Author Comment
        'author_comment',
        // Approval workflow
        'submitted_by', 'submitted_at',
        'approved_by', 'approved_at',
    ];

    // cost_total is a GENERATED COLUMN — never set it manually
    protected $guarded = ['cost_total'];

    protected $casts = [
        'cost_to_customer' => 'decimal:2',
        'cost_to_external' => 'decimal:2',
        'cost_internal'    => 'decimal:2',
        'cost_total'       => 'decimal:2',
        'submitted_at'     => 'datetime',
        'approved_at'      => 'datetime',
    ];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class, 'currency_id', 'currency_id'); }
    public function submittedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'submitted_by', 'id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'approved_by', 'id'); }
    public function attachments(): HasMany { return $this->hasMany(CchClosingAttachment::class, 'cch_id', 'cch_id'); }

    public function isApproved(): bool { return !is_null($this->approved_at); }
}
