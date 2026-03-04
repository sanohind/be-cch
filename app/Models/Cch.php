<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cch extends Model
{
    protected $table = 't_cch';
    protected $primaryKey = 'cch_id';

    protected $fillable = [
        'cch_number',
        'status',
        'input_by',
        'admin_in_charge',
        'division_id',
        'submitted_at',
        'closed_at',
        'b1_status', 'b2_status', 'b3_status', 'b4_status', 'b5_status',
        'b6_status', 'b7_status', 'b8_status', 'b9_status', 'b10_status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'closed_at'    => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function inputBy(): BelongsTo
    {
        return $this->belongsTo(CchUser::class, 'input_by', 'id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }

    public function basic(): HasOne
    {
        return $this->hasOne(CchBasic::class, 'cch_id', 'cch_id');
    }

    public function basicAttachments(): HasMany
    {
        return $this->hasMany(CchBasicAttachment::class, 'cch_id', 'cch_id');
    }

    public function primary(): HasOne
    {
        return $this->hasOne(CchPrimary::class, 'cch_id', 'cch_id');
    }

    public function primaryPhotos(): HasMany
    {
        return $this->hasMany(CchPrimaryPhoto::class, 'cch_id', 'cch_id');
    }

    public function srta(): HasOne
    {
        return $this->hasOne(CchSrta::class, 'cch_id', 'cch_id');
    }

    public function srtaScreening(): HasMany
    {
        return $this->hasMany(CchSrtaScreening::class, 'cch_id', 'cch_id');
    }

    public function srtaAttachments(): HasMany
    {
        return $this->hasMany(CchSrtaAttachment::class, 'cch_id', 'cch_id');
    }

    public function temporary(): HasOne
    {
        return $this->hasOne(CchTemporary::class, 'cch_id', 'cch_id');
    }

    public function temporaryAttachments(): HasMany
    {
        return $this->hasMany(CchTemporaryAttachment::class, 'cch_id', 'cch_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CchRequest::class, 'cch_id', 'cch_id');
    }

    public function ra(): HasOne
    {
        return $this->hasOne(CchRa::class, 'cch_id', 'cch_id');
    }

    public function raAttachments(): HasMany
    {
        return $this->hasMany(CchRaAttachment::class, 'cch_id', 'cch_id');
    }

    public function dfa(): HasOne
    {
        return $this->hasOne(CchDfa::class, 'cch_id', 'cch_id');
    }

    public function dfaAttachments(): HasMany
    {
        return $this->hasMany(CchDfaAttachment::class, 'cch_id', 'cch_id');
    }

    public function occurrence(): HasOne
    {
        return $this->hasOne(CchOccurrence::class, 'cch_id', 'cch_id');
    }

    public function occurrenceCauses(): HasMany
    {
        return $this->hasMany(CchCause::class, 'cch_id', 'cch_id')
                    ->where('cause_type', 'occurrence');
    }

    public function outflow(): HasOne
    {
        return $this->hasOne(CchOutflow::class, 'cch_id', 'cch_id');
    }

    public function outflowCauses(): HasMany
    {
        return $this->hasMany(CchCause::class, 'cch_id', 'cch_id')
                    ->where('cause_type', 'outflow');
    }

    public function closing(): HasOne
    {
        return $this->hasOne(CchClosing::class, 'cch_id', 'cch_id');
    }

    public function closingAttachments(): HasMany
    {
        return $this->hasMany(CchClosingAttachment::class, 'cch_id', 'cch_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CchQuestion::class, 'cch_id', 'cch_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(CchAuditLog::class, 'cch_id', 'cch_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CchNotification::class, 'cch_id', 'cch_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'in_progress']);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'close_requested';
    }

    /**
     * Generate CCH number format: CCH-{YYYY}-{NNNNN}
     */
    public static function generateCchNumber(int $year): string
    {
        $last = static::whereRaw("cch_number LIKE 'CCH-{$year}-%'")
            ->orderByDesc('cch_id')
            ->value('cch_number');

        $seq = $last ? intval(explode('-', $last)[2]) + 1 : 1;

        return sprintf('CCH-%d-%05d', $year, $seq);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
