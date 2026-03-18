<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Block 7 - Defective Factor Analysis
 * Only applicable for Japan division or Japanese supplier.
 */
class CchDfa extends Model
{
    protected $table = 't_cch_dfa';
    protected $primaryKey = 'dfa_id';

    protected $fillable = ['cch_id', 'occurrence_mechanism', 'outflow_mechanism', 'author_comment'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function attachments(): HasMany { return $this->hasMany(CchDfaAttachment::class, 'cch_id', 'cch_id'); }
}
