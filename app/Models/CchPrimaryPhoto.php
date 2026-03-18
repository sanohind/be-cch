<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CchPrimaryPhoto extends Model
{
    protected $table = 't_cch_primary_photos';
    protected $primaryKey = 'photo_id';
    public $timestamps = false;

    protected $fillable = ['cch_id', 'photo_type', 'file_name', 'file_path', 'file_size_kb', 'uploaded_by', 'uploaded_at'];
    protected $casts = ['uploaded_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'uploaded_by', 'id'); }
}
