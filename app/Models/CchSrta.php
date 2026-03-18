<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CchSrta extends Model
{
    protected $table = 't_cch_srta';
    protected $primaryKey = 'srta_id';

    protected $fillable = ['cch_id', 'treatment', 'author_comment'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function screening(): HasMany { return $this->hasMany(CchSrtaScreening::class, 'cch_id', 'cch_id'); }
    public function attachments(): HasMany { return $this->hasMany(CchSrtaAttachment::class, 'cch_id', 'cch_id'); }
}

class CchSrtaScreening extends Model
{
    protected $table = 't_cch_srta_screening';
    protected $primaryKey = 'screening_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'location', 'ng_qty', 'ok_qty', 'action_taken', 'action_result'];
    protected $casts = ['ng_qty' => 'integer', 'ok_qty' => 'integer'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
}

class CchSrtaAttachment extends Model
{
    protected $table = 't_cch_srta_attachments';
    protected $primaryKey = 'attachment_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'file_name', 'file_path', 'file_size_kb', 'uploaded_by', 'uploaded_at'];
    protected $casts = ['uploaded_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'uploaded_by', 'id'); }
}
