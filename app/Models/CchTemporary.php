<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CchTemporary extends Model
{
    protected $table = 't_cch_temporary';
    protected $primaryKey = 'temporary_id';

    protected $fillable = ['cch_id', 'author_comment'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function attachments(): HasMany { return $this->hasMany(CchTemporaryAttachment::class, 'cch_id', 'cch_id'); }
}

class CchTemporaryAttachment extends Model
{
    protected $table = 't_cch_temporary_attachments';
    protected $primaryKey = 'attachment_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'file_name', 'file_path', 'file_size_kb', 'uploaded_by', 'uploaded_at'];
    protected $casts = ['uploaded_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'uploaded_by', 'id'); }
}
